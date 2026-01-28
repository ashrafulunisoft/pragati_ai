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
        $message = trim($request->message);
        $user = Auth::user();

        if (!$user) {
            return response()->json(['reply' => 'Please login first to file a claim or purchase a policy.']);
        }

        // Handle "yes" confirmation
        if (strtolower($message) === 'yes') {
            // Could add last action tracking here
        }

        // Check if user just sent a number (likely responding to order/claim question)
        if (preg_match('/^(\d+)$/', $message, $numMatch)) {
            $num = (int)$numMatch[1];
            
            $recentOrders = Order::where('user_id', $user->id)
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $matchedOrder = $recentOrders->where('id', $num)->first();
            if ($matchedOrder) {
                $claimResult = $this->createClaim($user->id, $num, 0, 'Claim requested via chat');
                return response()->json(['reply' => $claimResult]);
            }
        }

        // FIRST: Check for claim creation command
        if (preg_match('/(file|create|submit|make)\s+(a\s+)?(claim|claims)/i', $message)) {
            if (preg_match('/(?:order|policy)\s*[#]?(\d+)/i', $message, $orderMatch)) {
                $orderId = (int)$orderMatch[1];
                
                $amount = 0;
                if (preg_match('/(\d+(?:\.\d+)?)\s*(tk|৳|taka|BDT)/i', $message, $amountMatch)) {
                    $amount = (float)$amountMatch[1];
                }
                
                $reason = 'Claim requested via chat';
                if (preg_match('/(?:for|because|due to|reason)\s+(.+)/i', $message, $reasonMatch)) {
                    $reason = trim($reasonMatch[1]);
                }
                
                $claimResult = $this->createClaim($user->id, $orderId, $amount, $reason);
                return response()->json(['reply' => $claimResult]);
            } else {
                $orders = Order::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->with('package')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                
                if ($orders->count() > 0) {
                    $ordersList = $orders->map(function($order) {
                        return "Order #{$order->id}: {$order->package->name} (Policy: {$order->policy_number})";
                    })->implode("\n");
                    
                    return response()->json(['reply' => "কোন অর্ডারের জন্য ক্লেইম করতে চান? অর্ডার নম্বর বলুন:\n\n" . $ordersList . "\n\nউদাহরণ: \"Order #5\" বা শুধু \"5\""]);
                }
                return response()->json(['reply' => 'আপনার কোনো সক্রিয় পলিসি নেই। প্রথমে একটি প্যাকেজ কিনুন।']);
            }
        }

        // SECOND: Check for package number in message (for buying)
        if (preg_match('/(?:package|pkg|pack)(?:\s+number)?\s*(\d+)/i', $message, $matches)) {
            $packageId = (int)$matches[1];
            $orderResult = $this->createOrder($user->id, $packageId);
            return response()->json(['reply' => $orderResult]);
        }

        // THIRD: Check for direct buy/purchase with package number
        if (preg_match('/(?:buy|purchase|order|get)\s+(?:package\s+)?(\d+)/i', $message, $matches)) {
            $packageId = (int)$matches[1];
            $orderResult = $this->createOrder($user->id, $packageId);
            return response()->json(['reply' => $orderResult]);
        }

        // FOURTH: Check for "want to buy" or similar intent
        if (preg_match('/(want|need|like)\s+(to\s+)?(buy|purchase|get)/i', $message)) {
            $packages = InsurancePackage::where('is_active', true)->orderBy('id')->get();
            $packagesList = $packages->map(function($pkg) {
                return "Package {$pkg->id}: {$pkg->name} | Price: ৳{$pkg->price} | Coverage: ৳{$pkg->coverage_amount} | {$pkg->duration_months} months";
            })->implode("\n");
            
            return response()->json(['reply' => "Great! Here are our packages:\n\n" . $packagesList . "\n\nWhich package would you like? Just say the number (like \"1\" or \"Package 1\")."]);
        }

        // Build user context
        if ($user) {
            $orders = Order::where('user_id', $user->id)
                ->with('package')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $claims = Claim::where('user_id', $user->id)
                ->with(['package', 'order'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $ordersInfo = $orders->map(function($order) {
                return "Order #{$order->id}: {$order->package->name} | Policy: {$order->policy_number} | Status: {$order->status}";
            })->implode("\n");

            $claimsInfo = $claims->map(function($claim) {
                return "Claim #{$claim->claim_number}: {$claim->package->name} | Amount: ৳{$claim->claim_amount} | Status: {$claim->status}";
            })->implode("\n");

            $userContext = "User ID: {$user->id}, Name: {$user->name}\n\nYOUR POLICIES:\n" . ($ordersInfo ?: "No policies yet.") . "\n\nYOUR CLAIMS:\n" . ($claimsInfo ?: "No claims yet.") . "\n\nUser asks in Bengali or English. Respond in same language.";
        } else {
            $userContext = "User is NOT logged in. Ask to login first.";
        }

        $packages = InsurancePackage::where('is_active', true)->orderBy('id')->get();
        $packagesInfo = $packages->map(function($pkg) {
            return "Package {$pkg->id}: {$pkg->name} | Price: ৳{$pkg->price} | Coverage: ৳{$pkg->coverage_amount} | {$pkg->duration_months} months";
        })->implode("\n");

        $allContext = $userContext . "\n\nAVAILABLE PACKAGES:\n" . $packagesInfo;

        $reply = $this->callMiniMax($message, $allContext);
        return response()->json(['reply' => $reply]);
    }

    private function createOrder($userId, $packageId)
    {
        DB::beginTransaction();
        try {
            $package = InsurancePackage::find($packageId);
            if (!$package) {
                return 'Package not found. Please select a valid package number (1, 2, or 3).';
            }

            $policyNumber = 'PL-' . date('Y') . '-' . strtoupper(uniqid());
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

            return "Policy Created Successfully!

Policy Number: {$policyNumber}
Package: {$package->name}
Coverage: ৳{$package->coverage_amount}
Price: ৳{$package->price}
Valid: {$startDate->format('d M Y')} - {$endDate->format('d M Y')}
Status: Active

Congratulations! Your policy is now active.";

        } catch (\Exception $e) {
            DB::rollBack();
            return 'Error: ' . $e->getMessage();
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
                return 'Order not found. Please provide a valid order number.';
            }

            if ($order->status !== 'active') {
                return 'This policy is not active. You can only file claims for active policies.';
            }

            if ($amount <= 0) {
                $amount = $order->package->coverage_amount;
            }

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

            return "Claim Filed Successfully!

Claim Number: {$claimNumber}
Policy: {$order->package->name}
Policy Number: {$order->policy_number}
Claim Amount: ৳{$amount}
Reason: {$reason}
Status: Submitted

Your claim has been submitted for review. We will contact you within 2-3 business days.";

        } catch (\Exception $e) {
            DB::rollBack();
            return 'Error: ' . $e->getMessage();
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
                        'content' => "You are Pragati Life Insurance Assistant. Be friendly and helpful. Give SHORT, DIRECT answers. Never show internal thinking or notes. Never explain what you are doing. Just give the answer. Speak naturally like a human. " . $userContext
                    ],
                    [
                        'role' => 'user',
                        'content' => $message
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 500
            ]);

            $status = $response->status();

            if ($status === 200) {
                $data = $response->json();
                if (isset($data['choices']) && count($data['choices']) > 0) {
                    $content = $data['choices'][0]['message']['content'];
                    return $this->cleanResponse($content);
                }
                return 'Error: Empty response.';
            }

            return 'Server temporarily unavailable. Please try again.';
            
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    private function cleanResponse($content)
    {
        // Remove all thinking blocks
        $content = str_replace(['<think>', ']', '[THINKING]', '[/THINKING]'], '', $content);
        
        // Remove lines starting with internal references or meta-comments
        $lines = explode("\n", $content);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip lines that are internal notes or meta-comments about user intent
            if (preg_match('/^(The user just said|The user is|I should|I will|They have|They already|I need|Based on|The system|I understand|Since the user|Looking at|So I|If the user|I should respond|per my instructions|as per my)/i', $trimmed)) {
                continue;
            }
            // Skip if line contains any internal thinking phrases
            if (stripos($trimmed, 'The user just said') !== false || 
                stripos($trimmed, 'I should respond') !== false ||
                stripos($trimmed, 'as Pragati') !== false ||
                stripos($trimmed, 'The user is identified') !== false ||
                stripos($trimmed, 'Let me see') !== false || 
                stripos($trimmed, 'Let me check') !== false ||
                stripos($trimmed, 'I will respond') !== false ||
                stripos($trimmed, 'Since they said') !== false ||
                stripos($trimmed, 'They said') !== false ||
                stripos($trimmed, 'In English') !== false ||
                stripos($trimmed, 'in Bengali') !== false ||
                stripos($trimmed, 'in English') !== false ||
                stripos($trimmed, 'without a clear') !== false ||
                stripos($trimmed, 'quite ambiguous') !== false ||
                stripos($trimmed, 'naturally and offer') !== false ||
                stripos($trimmed, 'welcoming manner') !== false ||
                stripos($trimmed, 'identified as') !== false ||
                stripos($trimmed, 'friendly') !== false) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $content = implode("\n", $cleanLines);
        
        // Clean up multiple newlines
        $content = preg_replace("/\n{3,}/", "\n\n", $content);
        
        return trim($content);
    }
}
