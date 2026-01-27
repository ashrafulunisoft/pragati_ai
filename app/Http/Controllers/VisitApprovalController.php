<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Rfid;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitApprovalController extends Controller
{
    /**
     * Get pending visits for approval with timeout info
     */
    public function pendingVisits()
    {
        $pendingVisits = Visit::with(['visitor', 'type', 'meetingUser'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($visit) {
                // Calculate time remaining (5 minutes = 300 seconds)
                $timeElapsed = now()->diffInSeconds($visit->created_at);
                $timeRemaining = max(0, 300 - $timeElapsed);
                $isExpired = $timeRemaining === 0;

                return [
                    'id' => $visit->id,
                    'visitor_name' => $visit->visitor->name,
                    'visitor_email' => $visit->visitor->email,
                    'visitor_phone' => $visit->visitor->phone,
                    'purpose' => $visit->purpose,
                    'schedule_time' => $visit->schedule_time->format('F j, Y - g:i A'),
                    'visit_type' => $visit->type->name,
                    'host_name' => $visit->meetingUser->name,
                    'created_at' => $visit->created_at->format('F j, Y - g:i A'),
                    'time_remaining' => $timeRemaining,
                    'is_expired' => $isExpired,
                ];
            });

        return response()->json([
            'success' => true,
            'pending_visits' => $pendingVisits,
        ]);
    }

    /**
     * Get live visits with RFID information for dashboard
     */
    public function liveVisitsWithRfid()
    {
        $liveVisits = Visit::with(['visitor', 'type', 'meetingUser', 'rfid.generatedBy'])
            ->whereIn('status', ['approved', 'completed'])
            ->orderBy('schedule_time', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($visit) {
                return [
                    'id' => $visit->id,
                    'visitor_name' => $visit->visitor->name,
                    'visitor_email' => $visit->visitor->email,
                    'purpose' => $visit->purpose,
                    'schedule_time' => $visit->schedule_time->format('F j, Y - g:i A'),
                    'visit_type' => $visit->type->name,
                    'host_name' => $visit->meetingUser->name,
                    'status' => $visit->status,
                    'rfid_tag' => $visit->rfid ? $visit->rfid->tag_uid : null,
                    'rfid_generated_at' => $visit->rfid ? $visit->rfid->assigned_at?->format('F j, Y - g:i A') : null,
                    'rfid_generated_by' => $visit->rfid ? $visit->rfid->generatedBy?->name : null,
                    'approved_at' => $visit->approved_at?->format('F j, Y - g:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'live_visits' => $liveVisits,
        ]);
    }

    /**
     * Approve a visit request and generate RFID
     */
    public function approveVisit(Request $request, $id)
    {
        $request->validate([
            'rfid_tag' => 'required|string|max:255|unique:rfids,tag_uid',
        ]);

        $visit = Visit::with('visitor')->findOrFail($id);

        if ($visit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not in pending status.',
            ], 400);
        }

        // Check if visit has expired (5 minutes timeout)
        $timeElapsed = now()->diffInSeconds($visit->created_at);
        if ($timeElapsed > 300) {
            // Auto-reject expired visit
            $visit->update([
                'status' => 'rejected',
                'rejected_reason' => 'Visit request timed out (exceeded 5 minutes)',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Visit request has expired (exceeded 5 minutes).',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update visit status
            $visit->update([
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            // Generate RFID
            $rfid = Rfid::create([
                'tag_uid' => $request->rfid_tag,
                'visit_id' => $visit->id,
                'generated_by' => Auth::id(),
                'is_active' => true,
                'assigned_at' => now(),
            ]);

            // Send approval notification to visitor
            $emailData = [
                'visitor_name' => $visit->visitor->name,
                'visitor_email' => $visit->visitor->email,
                'visitor_company' => $visit->visitor->address,
                'visit_date' => $visit->schedule_time->format('F j, Y - g:i A'),
                'visit_type' => $visit->type->name,
                'purpose' => $visit->purpose,
                'host_name' => $visit->meetingUser->name,
                'status' => 'approved',
                'rfid_tag' => $rfid->tag_uid,
            ];

            $emailService = new EmailNotificationService();
            $emailService->sendVisitStatusEmail($emailData);

            // Send SMS if phone exists
            if (!empty($visit->visitor->phone)) {
                $smsMessage = "Dear {$visit->visitor->name}, Your visit to UCB Bank has been APPROVED for " .
                              $visit->schedule_time->format('F j, Y - g:i A') .
                              ". Your RFID Tag: {$rfid->tag_uid}. Host: {$visit->meetingUser->name}. Thank you!";

                $phone = preg_replace('/[^0-9]/', '', $visit->visitor->phone);
                if (strpos($phone, '880') !== 0) {
                    $phone = '88' . $phone;
                }

                $smsService = new SmsNotificationService();
                $smsService->send($phone, $smsMessage);
            }

            DB::commit();

            Log::info('Visit approved and RFID generated', [
                'visit_id' => $visit->id,
                'visitor_name' => $visit->visitor->name,
                'rfid_tag' => $rfid->tag_uid,
                'approved_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Visit approved successfully! RFID generated.',
                'rfid' => $rfid->tag_uid,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving visit', [
                'error' => $e->getMessage(),
                'visit_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a visit request with reason
     */
    public function rejectVisit(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $visit = Visit::with('visitor')->findOrFail($id);

        if ($visit->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Visit is not in pending status.',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Update visit status with rejection reason
            $visit->update([
                'status' => 'rejected',
                'rejected_reason' => $request->rejected_reason,
            ]);

            // Send rejection notification to visitor
            $emailData = [
                'visitor_name' => $visit->visitor->name,
                'visitor_email' => $visit->visitor->email,
                'visitor_company' => $visit->visitor->address,
                'visit_date' => $visit->schedule_time->format('F j, Y - g:i A'),
                'visit_type' => $visit->type->name,
                'purpose' => $visit->purpose,
                'host_name' => $visit->meetingUser->name,
                'status' => 'rejected',
                'rejected_reason' => $request->rejected_reason,
            ];

            $emailService = new EmailNotificationService();
            $emailService->sendVisitStatusEmail($emailData);

            // Send SMS if phone exists
            if (!empty($visit->visitor->phone)) {
                $smsMessage = "Dear {$visit->visitor->name}, Your visit to UCB Bank has been REJECTED. " .
                              "Reason: {$request->rejected_reason}. Thank you!";

                $phone = preg_replace('/[^0-9]/', '', $visit->visitor->phone);
                if (strpos($phone, '880') !== 0) {
                    $phone = '88' . $phone;
                }

                $smsService = new SmsNotificationService();
                $smsService->send($phone, $smsMessage);
            }

            DB::commit();

            Log::info('Visit rejected', [
                'visit_id' => $visit->id,
                'visitor_name' => $visit->visitor->name,
                'rejected_reason' => $request->rejected_reason,
                'rejected_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Visit rejected successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rejecting visit', [
                'error' => $e->getMessage(),
                'visit_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get visit details for approval/rejection
     */
    public function getVisitDetails($id)
    {
        $visit = Visit::with(['visitor', 'type', 'meetingUser'])->findOrFail($id);

        $timeElapsed = now()->diffInSeconds($visit->created_at);
        $timeRemaining = max(0, 300 - $timeElapsed);
        $isExpired = $timeRemaining === 0;

        return response()->json([
            'success' => true,
            'visit' => [
                'id' => $visit->id,
                'visitor_name' => $visit->visitor->name,
                'visitor_email' => $visit->visitor->email,
                'visitor_phone' => $visit->visitor->phone,
                'visitor_company' => $visit->visitor->address,
                'purpose' => $visit->purpose,
                'schedule_time' => $visit->schedule_time->format('F j, Y - g:i A'),
                'visit_type' => $visit->type->name,
                'host_name' => $visit->meetingUser->name,
                'host_email' => $visit->meetingUser->email,
                'status' => $visit->status,
                'created_at' => $visit->created_at->format('F j, Y - g:i A'),
                'time_remaining' => $timeRemaining,
                'is_expired' => $isExpired,
                'rejected_reason' => $visit->rejected_reason,
            ],
        ]);
    }
}
