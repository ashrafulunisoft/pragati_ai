<?php

namespace App\Mcp\Tools;

use App\Models\Visit;

class VisitTools
{
    /**
     * Get all visits with optional filters
     */
    public function listVisits(?string $status = null, int $limit = 10): array
    {
        $query = Visit::with(['visitor', 'host'])
            ->orderBy('created_at', 'desc');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Get a specific visit by ID
     */
    public function getVisit(int $id): array
    {
        return Visit::with(['visitor', 'host', 'visitType'])
            ->findOrFail($id)
            ->toArray();
    }

    /**
     * Get today's visits
     */
    public function getTodayVisits(): array
    {
        return Visit::with(['visitor', 'host'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Create a new visit
     */
    public function createVisit(array $data): array
    {
        $visit = Visit::create([
            'visitor_id' => $data['visitor_id'],
            'host_id' => $data['host_id'],
            'visit_type_id' => $data['visit_type_id'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
        ]);
        
        return [
            'success' => true,
            'message' => 'Visit created successfully',
            'visit' => $visit->load(['visitor', 'host'])->toArray(),
        ];
    }

    /**
     * Update an existing visit
     */
    public function updateVisit(int $id, array $data): array
    {
        $visit = Visit::findOrFail($id);
        
        $visit->update(array_filter([
            'visitor_id' => $data['visitor_id'] ?? null,
            'host_id' => $data['host_id'] ?? null,
            'visit_type_id' => $data['visit_type_id'] ?? null,
            'purpose' => $data['purpose'] ?? null,
            'status' => $data['status'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ], fn($v) => $v !== null));
        
        return [
            'success' => true,
            'message' => 'Visit updated successfully',
            'visit' => $visit->fresh()->load(['visitor', 'host'])->toArray(),
        ];
    }

    /**
     * Delete a visit
     */
    public function deleteVisit(int $id): array
    {
        $visit = Visit::findOrFail($id);
        $visit->delete();
        
        return [
            'success' => true,
            'message' => 'Visit deleted successfully',
            'deleted_id' => $id,
        ];
    }

    /**
     * Update visit status (approve, reject, complete, check-in, check-out)
     */
    public function updateVisitStatus(int $id, string $status): array
    {
        $visit = Visit::findOrFail($id);
        
        $allowedStatuses = ['pending', 'approved', 'rejected', 'completed', 'checked_in', 'checked_out'];
        if (!in_array($status, $allowedStatuses)) {
            return [
                'success' => false,
                'message' => 'Invalid status. Allowed: ' . implode(', ', $allowedStatuses),
            ];
        }
        
        $updateData = ['status' => $status];
        
        // Set timestamps for check-in/check-out
        if ($status === 'checked_in') {
            $updateData['checked_in_at'] = now();
        } elseif ($status === 'checked_out') {
            $updateData['checked_out_at'] = now();
        }
        
        $visit->update($updateData);
        
        return [
            'success' => true,
            'message' => "Visit status updated to: {$status}",
            'visit' => $visit->fresh()->load(['visitor', 'host'])->toArray(),
        ];
    }

    /**
     * Approve a visit
     */
    public function approveVisit(int $id): array
    {
        return $this->updateVisitStatus($id, 'approved');
    }

    /**
     * Reject a visit
     */
    public function rejectVisit(int $id): array
    {
        return $this->updateVisitStatus($id, 'rejected');
    }

    /**
     * Check in a visitor
     */
    public function checkInVisit(int $id): array
    {
        return $this->updateVisitStatus($id, 'checked_in');
    }

    /**
     * Check out a visitor
     */
    public function checkOutVisit(int $id): array
    {
        return $this->updateVisitStatus($id, 'checked_out');
    }
}
