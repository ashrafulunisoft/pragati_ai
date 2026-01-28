<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Visitor;
use App\Models\Visit;
use App\Models\User;

class MCPService
{
    private array $tools = [];
    private array $toolsConfig = [];

    public function __construct()
    {
        $this->initializeTools();
    }

    /**
     * Initialize available tools with their definitions
     */
    private function initializeTools(): void
    {
        $this->toolsConfig = [
            // VISITOR TOOLS
            'get_visitor' => [
                'description' => 'Get visitor details by ID',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visitor ID', 'required' => true]
                ],
                'handler' => 'handleGetVisitor'
            ],
            'search_visitors' => [
                'description' => 'Search visitors by name, email, or phone',
                'parameters' => [
                    'search' => ['type' => 'string', 'description' => 'Search term', 'required' => true],
                    'limit' => ['type' => 'integer', 'description' => 'Max results', 'required' => false]
                ],
                'handler' => 'handleSearchVisitors'
            ],
            'create_visitor' => [
                'description' => 'Register a new visitor',
                'parameters' => [
                    'name' => ['type' => 'string', 'description' => 'Visitor name', 'required' => true],
                    'phone' => ['type' => 'string', 'description' => 'Phone number', 'required' => true],
                    'email' => ['type' => 'string', 'description' => 'Email address', 'required' => false],
                    'address' => ['type' => 'string', 'description' => 'Address', 'required' => false]
                ],
                'handler' => 'handleCreateVisitor'
            ],
            'block_visitor' => [
                'description' => 'Block a visitor from the system',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visitor ID', 'required' => true],
                    'reason' => ['type' => 'string', 'description' => 'Reason for blocking', 'required' => false]
                ],
                'handler' => 'handleBlockVisitor'
            ],

            // VISIT TOOLS
            'get_visit' => [
                'description' => 'Get visit details by ID',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visit ID', 'required' => true]
                ],
                'handler' => 'handleGetVisit'
            ],
            'search_visits' => [
                'description' => 'Search visits with filters',
                'parameters' => [
                    'status' => ['type' => 'string', 'description' => 'Status filter', 'required' => false],
                    'visitor_name' => ['type' => 'string', 'description' => 'Visitor name filter', 'required' => false],
                    'limit' => ['type' => 'integer', 'description' => 'Max results', 'required' => false]
                ],
                'handler' => 'handleSearchVisits'
            ],
            'create_visit' => [
                'description' => 'Create a new visit appointment',
                'parameters' => [
                    'visitor_id' => ['type' => 'integer', 'description' => 'Visitor ID', 'required' => true],
                    'host_id' => ['type' => 'integer', 'description' => 'Host/staff ID', 'required' => true],
                    'purpose' => ['type' => 'string', 'description' => 'Purpose of visit', 'required' => true],
                    'scheduled_at' => ['type' => 'string', 'description' => 'Scheduled date/time (Y-m-d H:i:s)', 'required' => true],
                    'visit_type_id' => ['type' => 'integer', 'description' => 'Visit type ID', 'required' => false]
                ],
                'handler' => 'handleCreateVisit'
            ],
            'approve_visit' => [
                'description' => 'Approve a pending visit',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visit ID', 'required' => true]
                ],
                'handler' => 'handleApproveVisit'
            ],
            'reject_visit' => [
                'description' => 'Reject a pending visit',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visit ID', 'required' => true],
                    'reason' => ['type' => 'string', 'description' => 'Rejection reason', 'required' => false]
                ],
                'handler' => 'handleRejectVisit'
            ],
            'checkin_visit' => [
                'description' => 'Check in a visitor for their visit',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visit ID', 'required' => true]
                ],
                'handler' => 'handleCheckinVisit'
            ],
            'checkout_visit' => [
                'description' => 'Check out a visitor after their visit',
                'parameters' => [
                    'id' => ['type' => 'integer', 'description' => 'Visit ID', 'required' => true]
                ],
                'handler' => 'handleCheckoutVisit'
            ],

            // DASHBOARD TOOLS
            'get_dashboard_stats' => [
                'description' => 'Get dashboard statistics',
                'parameters' => [
                    'period' => ['type' => 'string', 'description' => 'Time period (today, this_week, this_month)', 'required' => false]
                ],
                'handler' => 'handleGetDashboardStats'
            ],

            // USER/HOST TOOLS
            'search_hosts' => [
                'description' => 'Search for staff/hosts by name or email',
                'parameters' => [
                    'search' => ['type' => 'string', 'description' => 'Search term', 'required' => true],
                    'role' => ['type' => 'string', 'description' => 'Filter by role', 'required' => false]
                ],
                'handler' => 'handleSearchHosts'
            ],
        ];
    }

    /**
     * Get list of available tools
     */
    public function getTools(): array
    {
        return array_keys($this->toolsConfig);
    }

    /**
     * Get tool schema for MCP protocol
     */
    public function getToolSchema(): array
    {
        return array_map(function ($name, $config) {
            return [
                'name' => $name,
                'description' => $config['description'],
                'parameters' => [
                    'type' => 'object',
                    'properties' => array_map(fn($p) => [
                        'type' => $p['type'],
                        'description' => $p['description']
                    ], $config['parameters']),
                    'required' => array_values(array_filter(
                        $config['parameters'],
                        fn($p) => $p['required'] ?? false
                    ))
                ]
            ];
        }, array_keys($this->toolsConfig), $this->toolsConfig);
    }

    /**
     * Chat with the MCP service
     */
    public function chat(array $messages): array
    {
        $lastMessage = end($messages);
        
        if ($lastMessage['role'] === 'user') {
            $userMessage = $lastMessage['content'];
            
            // Detect intent from message
            $intent = $this->detectIntent($userMessage);
            $parameters = $this->extractParameters($userMessage, $intent);
            
            // Execute the tool
            if (isset($this->toolsConfig[$intent])) {
                $result = $this->executeTool($intent, $parameters);
                
                return [
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'intent' => $intent,
                                    'data' => $result,
                                    'success' => true
                                ])
                            ]
                        ]
                    ]
                ];
            }
        }
        
        return [
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'intent' => 'unknown',
                            'data' => ['message' => 'I could not understand your request.'],
                            'success' => false
                        ])
                    ]
                ]
            ]
        ];
    }

    /**
     * Detect intent from user message
     */
    private function detectIntent(string $message): string
    {
        $message = strtolower($message);
        
        // Visitor intents
        if (preg_match('/(register|create|add)\s+(a\s+)?(new\s+)?visitor/i', $message)) {
            return 'create_visitor';
        }
        if (preg_match('/(block|banned|ban)\s+(the\s+)?visitor/i', $message)) {
            return 'block_visitor';
        }
        if (preg_match('/get\s+(the\s+)?visitor\s+detail/i', $message) || preg_match('/visitor\s+#?(\d+)/i', $message, $m)) {
            return 'get_visitor';
        }
        if (preg_match('/(search|find|show|list)\s+(all\s+)?visitor/i', $message)) {
            return 'search_visitors';
        }
        if (preg_match('/how many visitors/i', $message)) {
            return 'search_visitors';
        }
        
        // Visit intents
        if (preg_match('/(register|create|add|schedule|book)\s+(a\s+)?(new\s+)?(visit|appointment)/i', $message)) {
            return 'create_visit';
        }
        if (preg_match('/(approve|accept)\s+(the\s+)?(pending\s+)?visit/i', $message)) {
            return 'approve_visit';
        }
        if (preg_match('/(reject|decline)\s+(the\s+)?visit/i', $message)) {
            return 'reject_visit';
        }
        if (preg_match('/check\s*(in|out)\s+(the\s+)?visitor/i', $message, $m)) {
            return $m[1] === 'in' ? 'checkin_visit' : 'checkout_visit';
        }
        if (preg_match('/get\s+(the\s+)?visit\s+detail/i', $message) || preg_match('/visit\s+#?(\d+)/i', $message, $m)) {
            return 'get_visit';
        }
        if (preg_match('/(search|find|show|list)\s+(all\s+|pending\s+|approved\s+)?visits?/i', $message)) {
            return 'search_visits';
        }
        
        // Dashboard intents
        if (preg_match('/(dashboard|stats|statistics|summary|overview)/i', $message)) {
            return 'get_dashboard_stats';
        }
        
        // Host intents
        if (preg_match('/(search|find|show|list)\s+(staff|host|employee)/i', $message)) {
            return 'search_hosts';
        }
        
        return 'unknown';
    }

    /**
     * Extract parameters from message
     */
    private function extractParameters(string $message, string $intent): array
    {
        $params = [];
        $message = strtolower($message);
        
        // Extract IDs
        if (preg_match('/#?(\d+)/', $message, $matches)) {
            $params['id'] = (int)$matches[1];
        }
        
        // Extract visitor_id
        if (preg_match('/visitor\s*(id|#)?\s*(\d+)/i', $message, $matches)) {
            $params['visitor_id'] = (int)$matches[2];
        }
        
        // Extract host_id
        if (preg_match('/host\s*(id|#)?\s*(\d+)|to\s+(.*?)(?=\s+for|\s+on|\s+at|$)/i', $message, $matches)) {
            if (isset($matches[2])) {
                $params['host_id'] = (int)$matches[2];
            }
        }
        
        // Extract name
        if (preg_match('/name\s*[=:]\s*([a-z\s]+)/i', $message, $matches)) {
            $params['name'] = trim($matches[1]);
        }
        
        // Extract phone
        if (preg_match('/phone\s*[=:]\s*([+\d\s]+)/i', $message, $matches)) {
            $params['phone'] = trim($matches[1]);
        }
        
        // Extract email
        if (preg_match('/email\s*[=:]\s*([^\s]+)/i', $message, $matches)) {
            $params['email'] = trim($matches[1]);
        }
        
        // Extract purpose
        if (preg_match('/purpose\s*[=:]\s*([^\n]+)/i', $message, $matches)) {
            $params['purpose'] = trim($matches[1]);
        }
        
        // Extract scheduled date
        if (preg_match('/(on|at|date)\s*[=:]\s*([^\n]+)/i', $message, $matches)) {
            $params['scheduled_at'] = trim($matches[2]);
        }
        
        // Extract reason
        if (preg_match('/reason\s*[=:]\s*([^\n]+)/i', $message, $matches)) {
            $params['reason'] = trim($matches[1]);
        }
        
        // Extract search term
        if (preg_match('/(search|for)\s*[=:]\s*([^\n]+)/i', $message, $matches)) {
            $params['search'] = trim($matches[2]);
        }
        
        // Extract period
        if (preg_match('/(today|this week|this month)/i', $message, $matches)) {
            $params['period'] = str_replace(' ', '_', strtolower($matches[1]));
        }
        
        // Extract limit
        if (preg_match('/limit\s*[=:]\s*(\d+)/i', $message, $matches)) {
            $params['limit'] = (int)$matches[1];
        }
        
        return $params;
    }

    /**
     * Execute a tool by name
     */
    private function executeTool(string $tool, array $params): array
    {
        $handler = $this->toolsConfig[$tool]['handler'] ?? null;
        
        if ($handler && method_exists($this, $handler)) {
            return $this->$handler($params);
        }
        
        return ['message' => 'Tool execution failed'];
    }

    // ===== VISITOR HANDLERS =====

    private function handleGetVisitor(array $params): array
    {
        $visitor = Visitor::with(['blocks', 'otps'])->find($params['id'] ?? 0);
        
        if (!$visitor) {
            return ['error' => 'Visitor not found'];
        }
        
        return $visitor->toArray();
    }

    private function handleSearchVisitors(array $params): array
    {
        $search = $params['search'] ?? '';
        $limit = $params['limit'] ?? 10;
        
        $visitors = Visitor::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->limit($limit)
            ->get();
        
        return ['count' => $visitors->count(), 'visitors' => $visitors->toArray()];
    }

    private function handleCreateVisitor(array $params): array
    {
        $visitor = Visitor::create([
            'name' => $params['name'] ?? 'Unknown',
            'phone' => $params['phone'] ?? '',
            'email' => $params['email'] ?? null,
            'address' => $params['address'] ?? null,
        ]);
        
        return ['message' => 'Visitor created successfully', 'visitor' => $visitor->toArray()];
    }

    private function handleBlockVisitor(array $params): array
    {
        $visitor = Visitor::find($params['id'] ?? 0);
        
        if (!$visitor) {
            return ['error' => 'Visitor not found'];
        }
        
        $visitor->update(['is_blocked' => true]);
        
        return ['message' => 'Visitor blocked successfully', 'visitor' => $visitor->toArray()];
    }

    // ===== VISIT HANDLERS =====

    private function handleGetVisit(array $params): array
    {
        $visit = Visit::with(['visitor', 'host', 'visitType'])->find($params['id'] ?? 0);
        
        if (!$visit) {
            return ['error' => 'Visit not found'];
        }
        
        return $visit->toArray();
    }

    private function handleSearchVisits(array $params): array
    {
        $status = $params['status'] ?? null;
        $visitorName = $params['visitor_name'] ?? null;
        $limit = $params['limit'] ?? 10;
        
        $query = Visit::with(['visitor', 'host']);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($visitorName) {
            $query->whereHas('visitor', function ($q) use ($visitorName) {
                $q->where('name', 'like', "%{$visitorName}%");
            });
        }
        
        $visits = $query->orderBy('created_at', 'desc')->limit($limit)->get();
        
        return ['count' => $visits->count(), 'visits' => $visits->toArray()];
    }

    private function handleCreateVisit(array $params): array
    {
        $visit = Visit::create([
            'visitor_id' => $params['visitor_id'] ?? 0,
            'host_id' => $params['host_id'] ?? 0,
            'purpose' => $params['purpose'] ?? 'General',
            'scheduled_at' => $params['scheduled_at'] ?? now(),
            'visit_type_id' => $params['visit_type_id'] ?? 1,
            'status' => 'pending',
        ]);
        
        return ['message' => 'Visit created successfully', 'visit' => $visit->toArray()];
    }

    private function handleApproveVisit(array $params): array
    {
        $visit = Visit::find($params['id'] ?? 0);
        
        if (!$visit) {
            return ['error' => 'Visit not found'];
        }
        
        $visit->update(['status' => 'approved']);
        
        return ['message' => 'Visit approved successfully', 'visit' => $visit->toArray()];
    }

    private function handleRejectVisit(array $params): array
    {
        $visit = Visit::find($params['id'] ?? 0);
        
        if (!$visit) {
            return ['error' => 'Visit not found'];
        }
        
        $visit->update(['status' => 'rejected']);
        
        return ['message' => 'Visit rejected', 'visit' => $visit->toArray()];
    }

    private function handleCheckinVisit(array $params): array
    {
        $visit = Visit::find($params['id'] ?? 0);
        
        if (!$visit) {
            return ['error' => 'Visit not found'];
        }
        
        $visit->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);
        
        return ['message' => 'Visitor checked in successfully', 'visit' => $visit->toArray()];
    }

    private function handleCheckoutVisit(array $params): array
    {
        $visit = Visit::find($params['id'] ?? 0);
        
        if (!$visit) {
            return ['error' => 'Visit not found'];
        }
        
        $visit->update([
            'status' => 'completed',
            'checked_out_at' => now(),
        ]);
        
        return ['message' => 'Visitor checked out successfully', 'visit' => $visit->toArray()];
    }

    // ===== DASHBOARD HANDLERS =====

    private function handleGetDashboardStats(array $params): array
    {
        $period = $params['period'] ?? 'this_week';
        
        switch ($period) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'this_week':
                $startDate = now()->startOfWeek();
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                break;
            default:
                $startDate = now()->subDays(30);
        }
        
        // Try to get checked_in_today, fallback if column doesn't exist
        try {
            $checkedInToday = Visit::whereDate('checked_in_at', today())->count();
        } catch (\Exception $e) {
            $checkedInToday = Visit::where('status', 'checked_in')->count();
        }
        
        return [
            'total_visitors' => Visitor::count(),
            'blocked_visitors' => Visitor::where('is_blocked', true)->count(),
            'total_visits' => Visit::count(),
            'visits_in_period' => Visit::where('created_at', '>=', $startDate)->count(),
            'pending_visits' => Visit::where('status', 'pending')->count(),
            'approved_visits' => Visit::where('status', 'approved')->count(),
            'completed_visits' => Visit::where('status', 'completed')->count(),
            'checked_in_today' => $checkedInToday,
            'period' => $period,
        ];
    }

    // ===== HOST HANDLERS =====

    private function handleSearchHosts(array $params): array
    {
        $search = $params['search'] ?? '';
        $role = $params['role'] ?? null;
        
        $query = User::query();
        
        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }
        
        if ($role) {
            $query->role($role);
        }
        
        $hosts = $query->limit(10)->get();
        
        return ['count' => $hosts->count(), 'hosts' => $hosts->toArray()];
    }
}
