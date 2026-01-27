<?php

namespace App\Http\Controllers;

use App\Mail\VisitorCsvExport;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class VisitorExportController extends Controller
{
    /**
     * Send visitor data as CSV via email (GET route for testing)
     */
    public function sendVisitorCsv(Request $request)
    {
        // Static email for testing purposes
        $staticEmail = 'abdul.alim@uslbd.com';

        try {
            // Build query based on filters
            $query = Visitor::query();

            // Apply date filter if provided
            if ($request->has('date_from') || $request->has('date_to')) {
                $query->whereHas('visits', function ($q) use ($request) {
                    if ($request->date_from) {
                        $q->where('created_at', '>=', $request->date_from);
                    }
                    if ($request->date_to) {
                        $q->where('created_at', '<=', $request->date_to . ' 23:59:59');
                    }
                });
            }

            // Get visitors with optional relations
            $visitors = $query->with(['visits', 'visits.type', 'visits.meetingUser'])->get();

            // Generate CSV content
            $csvContent = $this->generateVisitorCsv(
                $visitors,
                $request->boolean('include_visits', false),
                $request->boolean('include_logs', false)
            );

            // Create temporary CSV file
            $fileName = 'visitors_export_' . time() . '.csv';
            $filePath = storage_path('app/temp/' . $fileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            // Write CSV content to file
            file_put_contents($filePath, $csvContent);

            // Prepare date range string for email
            $dateRange = null;
            if ($request->date_from || $request->date_to) {
                $dateRange = ($request->date_from ?? 'All time') . ' to ' . ($request->date_to ?? 'Present');
            }

            // Send email with CSV attachment to static email
            Mail::to($staticEmail)->send(new VisitorCsvExport($filePath, $staticEmail, $dateRange));

            // Clean up temporary file after sending
            // Note: You might want to use a queue job for cleanup
            unlink($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Visitor CSV has been sent to ' . $staticEmail,
                'data' => [
                    'email' => $staticEmail,
                    'visitor_count' => $visitors->count(),
                    'date_range' => $dateRange
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send visitor CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate CSV content from visitor data
     */
    private function generateVisitorCsv($visitors, $includeVisits = false, $includeLogs = false)
    {
        $csv = fopen('php://temp', 'r+');

        // Write CSV headers
        $headers = [
            'ID',
            'Name',
            'Phone',
            'Email',
            'Address',
            'Is Blocked',
            'Created At',
            'Updated At'
        ];

        if ($includeVisits) {
            $headers = array_merge($headers, [
                'Visit ID',
                'Visit Type',
                'Meeting User',
                'Purpose',
                'Schedule Time',
                'Status',
                'Approved At'
            ]);
        }

        if ($includeLogs) {
            $headers = array_merge($headers, [
                'Check-in Time',
                'Check-out Time',
                'Total Minutes'
            ]);
        }

        fputcsv($csv, $headers);

        // Write visitor data
        foreach ($visitors as $visitor) {
            $row = [
                $visitor->id,
                $visitor->name,
                $visitor->phone,
                $visitor->email,
                $visitor->address,
                $visitor->is_blocked ? 'Yes' : 'No',
                $visitor->created_at->format('Y-m-d H:i:s'),
                $visitor->updated_at->format('Y-m-d H:i:s')
            ];

            if ($includeVisits && $visitor->visits->isNotEmpty()) {
                foreach ($visitor->visits as $visit) {
                    $visitRow = array_merge($row, [
                        $visit->id,
                        $visit->type ? $visit->type->name : 'N/A',
                        $visit->meetingUser ? $visit->meetingUser->name : 'N/A',
                        $visit->purpose,
                        $visit->schedule_time ? $visit->schedule_time->format('Y-m-d H:i:s') : 'N/A',
                        $visit->status,
                        $visit->approved_at ? $visit->approved_at->format('Y-m-d H:i:s') : 'N/A'
                    ]);

                    if ($includeLogs) {
                        $visitLog = $visit->visitLog;
                        $visitRow = array_merge($visitRow, [
                            $visitLog ? $visitLog->checkin_time?->format('Y-m-d H:i:s') : 'N/A',
                            $visitLog ? $visitLog->checkout_time?->format('Y-m-d H:i:s') : 'N/A',
                            $visitLog ? $visitLog->total_minutes : 'N/A'
                        ]);
                    }

                    fputcsv($csv, $visitRow);
                }
            } else {
                fputcsv($csv, $row);
            }
        }

        // Get CSV content
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }

    /**
     * Preview visitor data before export (optional)
     */
    public function previewVisitorData(Request $request)
    {
        $query = Visitor::query();

        if ($request->has('date_from') || $request->has('date_to')) {
            $query->whereHas('visits', function ($q) use ($request) {
                if ($request->date_from) {
                    $q->where('created_at', '>=', $request->date_from);
                }
                if ($request->date_to) {
                    $q->where('created_at', '<=', $request->date_to . ' 23:59:59');
                }
            });
        }

        $count = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_visitors' => $count,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to
            ]
        ]);
    }
}
