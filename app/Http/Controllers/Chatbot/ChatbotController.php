<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Models\pragati\Order;
use App\Models\pragati\InsurancePackage;
use App\Models\pragati\Claim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function index()
    {
        return view('chatbot.ai-chatbot');
    }

    public function chat(Request $request)
    {
        $message = $request->message;
        $user = Auth::user();

        if (!$user) {
            return response()->json(['reply' => 'Please login first to file a claim or purchase a policy.']);
        }

        // FIRST: Check for claim creation command (BEFORE order pattern)
        if (preg_match('/(file|create|submit|make)\s+(a\s+)?(claim|claims)/i', $message)) {
            // Extract order ID from message
            if (preg_match('/(order|policy)\s*[#]?(\d+)/i', $message, $orderMatch)) {
                $orderId = (int)$orderMatch[2];
                
                // Extract amount if mentioned (e.g., "10tk", "100 BDT", "৳50")
                $amount = 0;
                if (preg_match('/(\d+(?:\.\d+)?)\s*(tk|৳|taka|BDT)/i', $message, $amountMatch)) {
                    $amount = (float)$amountMatch[1];
                }
                
                // Extract reason (everything after "for" or "due to")
                $reason = '';
                if (preg_match('/(?:for|due to|because|reason)\s+(.+)/i', $message, $reasonMatch)) {
                    $reason = trim($reasonMatch[1]);
                } else {
                    $reason = 'General claim request';
                }
                
                $claimResult = $this->createClaim($user->id, $orderId, $amount, $reason);
                return response()->json(['reply' => $claimResult]);
            } else {
                // List user's orders and ask which one to claim
                $orders = Order::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->with('package')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                
                if ($orders->count() > 0) {
                    $ordersList = $orders->map(function($order) {
                        return "- Order #{$order->id}: {$order->package->name} (Policy: {$order->policy_number})";
                    })->implode("\n");
                    
                    return response()->json(['reply' => "Which policy would you like to file a claim for? Please specify the order number.\n\nYour active policies:\n" . $ordersList . "\n\nExample: \"File claim for order #1 due to medical expenses\""]);
                } else {
                    return response()->json(['reply' => 'You have no active policies to file a claim for. Please purchase a policy first.']);
                }
            }
        }

        // SECOND: Check for order creation command (buy package X)
        if (preg_match('/(buy|purchase|order|create policy|get policy)\s+(?:package|plan)?\s*(\d+)/i', $message, $matches)) {
            $packageId = (int)$matches[2];
            $orderResult = $this->createOrder($user->id, $packageId);
            return response()->json(['reply' => $orderResult]);
        }

        // Build user context
        if ($user) {
            // Fetch user's orders
            $orders = Order::where('user_id', $user->id)
                ->with('package')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // Fetch user's claims
            $claims = Claim::where('user_id', $user->id)
                ->with(['package', 'order'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $ordersInfo = $orders->map(function($order) {
                return "- Order #{$order->id}: {$order->package->name} | Policy: {$order->policy_number} | Status: {$order->status} | Valid: {$order->start_date} to {$order->end_date}";
            })->implode("\n");

            $claimsInfo = $claims->map(function($claim) {
                return "- Claim #{$claim->claim_number}: {$claim->package->name} | Amount: {$claim->claim_amount} | Status: {$claim->status} | Reason: {$claim->reason}";
            })->implode("\n");

            $userContext = "
User is LOGGED IN.
User ID: {$user->id}
Name: {$user->name}
Email: {$user->email}

USER ORDERS (Policies):
" . ($ordersInfo ?: "No orders found.") . "

USER CLAIMS:
" . ($claimsInfo ?: "No claims found.") . "

If user asks:
- 'Am I logged in?' → say YES
- 'What is my user id?' → show user id
- 'My policies / orders?' → list their orders above
- 'My claims?' → list their claims above
- 'Show packages / plans' → list available packages (see below)
- 'Buy package X' → Create an order for that package
- 'File claim for order #X' → Create a claim for that order
";
        } else {
            $userContext = "
User is NOT logged in.

If user asks about:
- policy
- order
- claim
- personal data

Tell them politely to login first.
";
        }

        // Get all available packages
        $packages = InsurancePackage::where('is_active', true)
            ->orderBy('name')
            ->get();

        $packagesInfo = $packages->map(function($pkg) {
            return "- Package ID {$pkg->id}: {$pkg->name} | Price: {$pkg->price} | Coverage: {$pkg->coverage_amount} | Duration: {$pkg->duration_months} months";
        })->implode("\n");

        $allContext = $userContext . "

AVAILABLE INSURANCE PACKAGES:
" . ($packagesInfo ?: "No packages available.");

        $reply = $this->callMiniMax($message, $allContext);
        return response()->json(['reply' => $reply]);
    }

    private function createOrder($userId, $packageId)
    {
        DB::beginTransaction();
        try {
            $package = InsurancePackage::find($packageId);
            
            if (!$package) {
                return '❌ Package not found. Please select a valid package number (1, 2, or 3).';
            }

            // Generate policy number
            $policyNumber = 'PL-' . date('Y') . '-' . strtoupper(uniqid());

            // Calculate dates
            $startDate = now();
            $endDate = now()->addMonths($package->duration_months);

            $order = Order::create([
                'user_id' => $userId,
                'insurance_package_id' => $packageId,
                'policy_number' => $policyNumber,
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            DB::commit();

            return "✅ Policy Created Successfully!

**Policy Details:**
- Policy Number: {$policyNumber}
- Package: {$package->name}
- Coverage: ৳{$package->coverage_amount}
- Price: ৳{$package->price}
- Valid From: {$startDate->format('d M Y')}
- Valid Until: {$endDate->format('d M Y')}
- Status: Active

Your policy is now active! Congratulations on securing your future with Pragati Life Insurance!";

        } catch (\Exception $e) {
            DB::rollBack();
            return 'Error creating order: ' . $e->getMessage();
        }
    }

    private function createClaim($userId, $orderId, $amount, $reason)
    {
        DB::beginTransaction();
        try {
            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->with('package')
                ->first();
            
            if (!$order) {
                return '❌ Order not found. Please provide a valid order number. Your order should be like "order #6".';
            }

            if ($order->status !== 'active') {
                return '❌ This policy is not active. You can only file claims for active policies.';
            }

            // Use package coverage amount if no amount specified
            if ($amount <= 0) {
                $amount = $order->package->coverage_amount;
            }

            // Generate claim number
            $claimNumber = 'CLM-' . date('Y') . '-' . strtoupper(uniqid());

            $claim = Claim::create([
                'user_id' => $userId,
                'insurance_package_id' => $order->insurance_package_id,
                'order_id' => $order->id,
                'claim_number' => $claimNumber,
                'claim_amount' => $amount,
                'reason' => $reason,
                'status' => 'submitted',
            ]);

            DB::commit();

            return "✅ Claim Filed Successfully!

**Claim Details:**
- Claim Number: {$claimNumber}
- Policy: {$order->package->name}
- Policy Number: {$order->policy_number}
- Claim Amount: ৳{$amount}
- Reason: {$reason}
- Status: Submitted

Your claim has been submitted for review. Our team will contact you within 2-3 business days. Thank you for choosing Pragati Life Insurance!";

        } catch (\Exception $e) {
            DB::rollBack();
            return 'Error filing claim: ' . $e->getMessage();
        }
    }

    private function callMiniMax($message, $userContext)
    {
        $apiKey = config('services.minimax.api_key');
        $host = config('services.minimax.host', 'https://api.minimax.io');
        $model = config('services.minimax.model', 'MiniMax-M2.1');
        
        if (empty($apiKey)) {
            return 'Error: MiniMax API key not configured.';
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($host . '/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are Pragati Life Insurance AI Assistant. Be friendly and professional. Give direct answers only. Never include internal notes or system references. Answer in Bangla or English based on user language. Be helpful and conversational like a human assistant." . $userContext
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 1000
            ]);

            $status = $response->status();

            if ($status === 200) {
                $data = $response->json();
                if (isset($data['choices']) && count($data['choices']) > 0) {
                    $content = $data['choices'][0]['message']['content'];
                    return $this->cleanResponse($content);
                }
                return 'Error: Empty response from MiniMax.';
            }

            if ($status === 500 || $status === 520 || $status === 796) {
                return 'MiniMax server is temporarily unavailable. Please try again in a moment.';
            }

            return 'Error: MiniMax returned status ' . $status;
            
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    private function cleanResponse($content)
    {
        // Remove thinking blocks
        $content = str_replace('<think>', '', $content);
        $content = str_replace('</think>', '', $content);
        $content = str_replace('[THINKING]', '', $content);
        $content = str_replace('[/THINKING]', '', $content);
        
        // Remove lines that start with internal notes or system references
        $lines = explode("\n", $content);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip lines that are internal notes
            if (preg_match('/^(The user wants|The user is|I will|I should|The system message|System message|Order Created Successfully|\*\*Order Created|\*\*Policy Created|\*\*Claim)/i', $trimmed)) {
                continue;
            }
            if (preg_match('/^(Your new policy|You can view|Your policy has)/i', $trimmed)) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $content = implode("\n", $cleanLines);
        
        // Remove any remaining internal notes
        $content = preg_replace('/The system message says.*?(\.|")/', '', $content);
        $content = preg_replace('/The user wants to.*?(\.)/', '', $content);
        
        $content = preg_replace("/\n{3,}/", "\n\n", $content);
        
        return trim($content);
    }
}
