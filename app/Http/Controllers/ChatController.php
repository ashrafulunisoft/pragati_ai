<?php

namespace App\Http\Controllers;

use App\Mcp\Tools\AskAiTool;
use App\Services\AiQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    private AskAiTool $askAiTool;

    public function __construct(AskAiTool $askAiTool)
    {
        $this->askAiTool = $askAiTool;
    }

    /**
     * Display the chat page
     */
    public function index()
    {
        return view('chat.index');
    }

    /**
     * Format database result for UI display
     */
    private function formatDatabaseResponse(array $result): string
    {
        $queryType = $result['query_type'] ?? 'unknown';
        $data = $result['result'] ?? [];
        $interpretation = $result['interpretation'] ?? '';
        $database = $result['database'] ?? 'unknown';

        $response = "**Query Result**\n\n";
        
        if (is_array($data)) {
            if (isset($data['count'])) {
                // Count query
                $response .= "📊 **Count:** {$data['count']}\n";
            } elseif (isset($data['total_visitors'])) {
                // Stats query
                $response .= "📊 **Dashboard Statistics** (from: {$database})\n\n";
                $response .= "| Metric | Count |\n";
                $response .= "|--------|-------|\n";
                $response .= "| Total Visitors | {$data['total_visitors']} |\n";
                $response .= "| Blocked Visitors | {$data['blocked_visitors']} |\n";
                $response .= "| Total Visits | {$data['total_visits']} |\n";
                $response .= "| Pending Visits | {$data['pending_visits']} |\n";
                $response .= "| Approved Visits | {$data['approved_visits']} |\n";
                $response .= "| Rejected Visits | {$data['rejected_visits']} |\n";
                $response .= "| Completed Visits | {$data['completed_visits']} |\n";
                $response .= "| Today's Visits | {$data['today_visits']} |\n";
                $response .= "| This Week | {$data['this_week_visits']} |\n";
                $response .= "| Total Hosts | {$data['total_hosts']} |\n";
            } elseif (isset($data['error'])) {
                // Error message
                $response .= "❌ {$data['error']}\n";
                if (isset($data['available_tables'])) {
                    $response .= "Available tables: " . implode(', ', $data['available_tables']) . "\n";
                }
            } elseif (empty($data)) {
                $response .= "No records found.\n";
            } else {
                // List query - format as table
                $response .= "📋 **Results** (Query: {$interpretation})\n\n";
                
                // Handle array of records
                if (is_array($data) && count($data) > 0) {
                    // Convert stdClass to array if needed
                    $firstItem = is_object($data[0]) ? (array)$data[0] : ($data[0] ?? []);
                    $keys = array_keys($firstItem);
                    
                    // Build table header
                    $response .= "| " . implode(" | ", array_map('ucfirst', $keys)) . " |\n";
                    $response .= "|" . str_repeat("---|", count($keys)) . "\n";
                    
                    // Build table rows (limit to 10)
                    $count = 0;
                    foreach ($data as $item) {
                        if ($count >= 10) {
                            $response .= "| ... and " . (count($data) - 10) . " more |\n";
                            break;
                        }
                        // Convert stdClass to array
                        $itemArray = is_object($item) ? (array)$item : $item;
                        $values = array_map(function($value) {
                            if (is_null($value)) return '-';
                            if (is_array($value)) return json_encode($value);
                            if (is_object($value)) return json_encode($value);
                            return substr((string)$value, 0, 30);
                        }, array_values($itemArray));
                        $response .= "| " . implode(" | ", $values) . " |\n";
                        $count++;
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Handle chat message from the user
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $request->input('message');

        try {
            // Check if this is a database query
            if (AiQueryService::isDatabaseQuery($message)) {
                // Use pragati_ai_db_2 for database queries
                AiQueryService::useExternalDatabase(true);
                $result = AiQueryService::answerFromDatabase($message);
                
                $response = $this->formatDatabaseResponse($result);
                
                return response()->json([
                    'success' => true,
                    'response' => $response,
                    'provider' => 'database',
                    'database' => $result['database'] ?? 'unknown',
                    'query_type' => $result['query_type'] ?? 'unknown',
                    'data' => $result['result'] ?? [],
                    'confidence' => $result['confidence'] ?? 1.0,
                ]);
            }

            // Regular AI chat
            $result = $this->askAiTool->chatAi($message);

            return response()->json([
                'success' => true,
                'response' => $result['response'] ?? 'No response received',
                'provider' => $result['provider'] ?? 'unknown',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to process your message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle database chat message (always queries database)
     */
    public function chatDatabase(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message = $request->input('message');

        try {
            // Always use pragati_ai_db_2 for database queries
            AiQueryService::useExternalDatabase(true);
            $result = AiQueryService::answerFromDatabase($message);
            
            $response = $this->formatDatabaseResponse($result);
            
            return response()->json([
                'success' => true,
                'response' => $response,
                'provider' => 'database',
                'database' => $result['database'] ?? 'unknown',
                'query_type' => $result['query_type'] ?? 'unknown',
                'action' => $result['action'] ?? 'query',
                'data' => $result['result'] ?? [],
                'confidence' => $result['confidence'] ?? 1.0,
                'interpretation' => $result['interpretation'] ?? '',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to query database: ' . $e->getMessage(),
            ], 500);
        }
    }
}
