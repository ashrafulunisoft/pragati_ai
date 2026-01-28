<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Visitor;
use App\Models\Visit;
use App\Models\User;

class AiQueryService
{
    private static bool $useExternalDb = false;
    private static string $externalDbName = 'pragati_ai_db_2';

    /**
     * Available tables in the database
     */
    private static array $localTables = [
        'visitors' => Visitor::class,
        'visits' => Visit::class,
        'users' => User::class,
        'visit_types' => null,
    ];

    private static array $externalTables = [
        'visitors' => 'visitors',
        'visits' => 'visits',
        'users' => 'users',
        'visit_types' => 'visit_types',
        'visit_logs' => 'visit_logs',
        'visitor_blocks' => 'visitor_blocks',
        'visitor__otps' => 'visitor__otps',
        'rfids' => 'rfids',
        'roles' => 'roles',
        'permissions' => 'permissions',
    ];

    /**
     * Enable querying external database (pragati_ai_db_2)
     */
    public static function useExternalDatabase(bool $useExternal = true): void
    {
        self::$useExternalDb = $useExternal;
    }

    /**
     * Check if the question is asking about the database using AI
     */
    public static function isDatabaseQuery(string $question): bool
    {
        // Always treat as database query for now - AI will determine the actual intent
        // This allows any natural language question to be processed
        return true;
    }

    /**
     * Answer a natural language question about the database
     */
    public static function answerFromDatabase(string $question): array
    {
        // Use AI to interpret the question and determine what to query
        $interpretation = self::interpretQuestion($question);
        
        // Execute the query based on AI interpretation
        $result = self::executeInterpretedQuery($interpretation);
        
        return [
            'question' => $question,
            'interpretation' => $interpretation['explanation'],
            'query_type' => $interpretation['table'],
            'action' => $interpretation['action'],
            'result' => $result,
            'database' => self::$useExternalDb ? self::$externalDbName : 'pragati_ai_db_2',
        ];
    }

    /**
     * Use AI to interpret natural language question
     */
    private static function interpretQuestion(string $question): array
    {
        // Build a prompt for AI interpretation
        $tables = implode(', ', array_keys(self::$externalTables));
        
        $prompt = "You are a database query interpreter for a Visitor Management System.

Available tables in pragati_ai_db_2: {$tables}

Analyze this question and respond with ONLY a JSON object:
{\"table\": \"table_name\", \"action\": \"count|list|stats\", \"filters\": {}, \"explanation\": \"what you're doing\"}

Rules:
1. Detect if they're asking about visitors, visits, users, roles, permissions, etc.
2. Detect if they want to count, list, or get statistics
3. Apply filters: status (pending, approved, rejected, completed), time (today, this week, this month), blocked
4. If the question is NOT about the database (e.g., general knowledge), set table to \"none\"

Example questions:
- \"How many visitors today?\" → {\"table\": \"visitors\", \"action\": \"count\", \"filters\": {\"today\": true}, \"explanation\": \"Counting visitors created today\"}
- \"Show me all roles\" → {\"table\": \"roles\", \"action\": \"list\", \"filters\": {}, \"explanation\": \"Listing all roles\"}
- \"What is Laravel?\" → {\"table\": \"none\", \"action\": \"none\", \"filters\": {}, \"explanation\": \"Not a database question\"}
- \"Total pending visits\" → {\"table\": \"visits\", \"action\": \"count\", \"filters\": {\"status\": \"pending\"}, \"explanation\": \"Counting pending visits\"}
- \"List all blocked visitors\" → {\"table\": \"visitors\", \"action\": \"list\", \"filters\": {\"is_blocked\": true}, \"explanation\": \"Listing blocked visitors\"}

Question: {$question}

Respond with ONLY valid JSON:";

        // Call AI to interpret
        $aiResponse = self::callAiForInterpretation($prompt);
        
        // Parse the response
        $data = json_decode($aiResponse, true);
        
        if (!$data || isset($data['table']) && $data['table'] === 'none') {
            // Not a database question
            return [
                'table' => 'none',
                'action' => 'none',
                'filters' => [],
                'explanation' => $data['explanation'] ?? 'Not a database question',
            ];
        }
        
        return [
            'table' => $data['table'] ?? 'visitors',
            'action' => $data['action'] ?? 'list',
            'filters' => $data['filters'] ?? [],
            'explanation' => $data['explanation'] ?? 'Querying database',
        ];
    }

    /**
     * Call AI to interpret the question
     */
    private static function callAiForInterpretation(string $prompt): string
    {
        // Try MiniMax first
        $apiKey = config('services.minimax.api_key', env('MINIMAX_API_KEY'));
        
        if ($apiKey) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post('https://api.minimax.io/v1/chat/completions', [
                    'model' => config('services.minimax.model', 'minimax-2.1'),
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.1,
                ]);

                $content = $response->json('choices.0.message.content');
                
                // Clean up response
                $content = preg_replace('/```json\s*/', '', $content);
                $content = preg_replace('/```\s*/', '', $content);
                $content = trim($content);
                
                if (json_decode($content, true)) {
                    return $content;
                }
            } catch (\Exception $e) {
                // Fall back to local parsing
            }
        }
        
        // Fall back to local parsing
        return self::localParseQuestion($prompt);
    }

    /**
     * Local parsing as fallback
     */
    private static function localParseQuestion(string $prompt): string
    {
        // Extract the question
        preg_match('/Question: (.+)/s', $prompt, $matches);
        $question = strtolower(trim($matches[1] ?? $prompt));
        
        // Detect table
        $table = 'visitors';
        if (preg_match('/(roles|permissions)/i', $question)) {
            $table = preg_match('/roles/i', $question) ? 'roles' : 'permissions';
        } elseif (preg_match('/(visit|appointment|meeting|schedule|checkin|checkout)/i', $question)) {
            $table = 'visits';
        } elseif (preg_match('/(user|staff|employee|host|admin)/i', $question)) {
            $table = 'users';
        } elseif (preg_match('/(rfid|block|log|otp|type)/i', $question)) {
            if (preg_match('/rfid/i', $question)) $table = 'rfids';
            elseif (preg_match('/block/i', $question)) $table = 'visitor_blocks';
            elseif (preg_match('/log/i', $question)) $table = 'visit_logs';
            elseif (preg_match('/otp/i', $question)) $table = 'visitor__otps';
            elseif (preg_match('/type/i', $question)) $table = 'visit_types';
        }
        
        // Detect action
        $action = 'list';
        if (preg_match('/(how many|count|total|number of|how much)/i', $question)) {
            $action = 'count';
        } elseif (preg_match('/(stats|statistics|dashboard|summary|report)/i', $question)) {
            $action = 'stats';
        }
        
        // Detect filters
        $filters = ['limit' => 10];
        
        // Status filter
        if (preg_match('/pending/i', $question)) $filters['status'] = 'pending';
        elseif (preg_match('/approved/i', $question)) $filters['status'] = 'approved';
        elseif (preg_match('/rejected/i', $question)) $filters['status'] = 'rejected';
        elseif (preg_match('/completed/i', $question)) $filters['status'] = 'completed';
        
        // Time filters
        if (preg_match('/today/i', $question)) $filters['today'] = true;
        elseif (preg_match('/this week|week/i', $question)) $filters['this_week'] = true;
        elseif (preg_match('/this month|month/i', $question)) $filters['this_month'] = true;
        
        // Blocked filter
        if (preg_match('/block/i', $question)) $filters['is_blocked'] = true;
        
        // Limit
        if (preg_match('/(last|top|first|recent|show only)\s*(\d+)/i', $question, $limitMatch)) {
            $filters['limit'] = (int)$limitMatch[2];
        }
        
        return json_encode([
            'table' => $table,
            'action' => $action,
            'filters' => $filters,
            'explanation' => "Querying {$table} table, action: {$action}",
        ]);
    }

    /**
     * Execute the query based on AI interpretation
     */
    private static function executeInterpretedQuery(array $interpretation): mixed
    {
        if ($interpretation['table'] === 'none') {
            return ['message' => 'This is not a database question.'];
        }
        
        $table = $interpretation['table'];
        $action = $interpretation['action'];
        $filters = $interpretation['filters'];
        
        if (self::$useExternalDb) {
            return self::executeExternalQuery($table, $action, $filters);
        }
        
        return self::executeLocalQuery($table, $action, $filters);
    }

    /**
     * Execute query on local database
     */
    private static function executeLocalQuery(string $table, string $action, array $filters): mixed
    {
        $modelClass = self::$localTables[$table] ?? null;
        
        if (!$modelClass) {
            return [
                'error' => "Table '{$table}' not found",
                'available_tables' => array_keys(self::$localTables),
            ];
        }
        
        $query = $modelClass::query();
        $limit = min($filters['limit'] ?? 10, 100);
        
        // Apply filters
        foreach ($filters as $key => $value) {
            if (in_array($key, ['status', 'is_blocked', 'today', 'this_week', 'this_month', 'name', 'phone', 'email'])) {
                if ($key === 'today') {
                    $query->whereDate('created_at', today());
                } elseif ($key === 'this_week') {
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                } elseif ($key === 'this_month') {
                    $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                } else {
                    $model = new $modelClass;
                    if (in_array($key, $model->getFillable()) || $key === 'id') {
                        if (in_array($key, ['name', 'phone', 'email'])) {
                            $query->where($key, 'like', '%' . $value . '%');
                        } else {
                            $query->where($key, $value);
                        }
                    }
                }
            }
        }
        
        return match ($action) {
            'count' => ['count' => $query->count()],
            'stats' => self::getLocalStats($table),
            'list', 'search' => $query->orderBy('created_at', 'desc')->limit($limit)->get()->toArray(),
            'get' => $query->first()?->toArray(),
            default => $query->orderBy('created_at', 'desc')->limit($limit)->get()->toArray(),
        };
    }

    /**
     * Execute query on external database
     */
    private static function executeExternalQuery(string $table, string $action, array $filters): mixed
    {
        $db = DB::connection('mysql_external');
        $limit = min($filters['limit'] ?? 10, 100);
        
        if (!in_array($table, self::$externalTables)) {
            return [
                'error' => "Table '{$table}' not found in database",
                'available_tables' => array_keys(self::$externalTables),
            ];
        }
        
        $query = $db->table($table);
        
        // Apply filters
        if ($table === 'visits' && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['today'])) {
            $query->whereDate('created_at', today());
        }
        if (!empty($filters['this_week'])) {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        if (!empty($filters['this_month'])) {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        }
        
        foreach (['name', 'phone', 'email'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, 'like', '%' . $filters[$field] . '%');
            }
        }
        
        return match ($action) {
            'count' => ['count' => $query->count()],
            'stats' => self::getExternalStats($table, $db),
            'list', 'search' => $query->orderBy('created_at', 'desc')->limit($limit)->get()->toArray(),
            'get' => $query->first(),
            default => $query->orderBy('created_at', 'desc')->limit($limit)->get()->toArray(),
        };
    }

    /**
     * Get stats for local database
     */
    private static function getLocalStats(string $table): array
    {
        return [
            'total_in_table' => call_user_func([self::$localTables[$table] ?? Visitor::class, 'count']),
            'table' => $table,
            'database' => 'pragati_ai_db_2',
        ];
    }

    /**
     * Get stats for external database
     */
    private static function getExternalStats(string $table, $db): array
    {
        return [
            'total_in_table' => $db->table($table)->count(),
            'table' => $table,
            'database' => self::$externalDbName,
        ];
    }
}
