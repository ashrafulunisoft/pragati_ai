<?php

namespace App\Mcp\Tools;

use App\Models\Visitor;

class VisitorTools
{
    /**
     * Get all visitors with optional limit
     */
    public function listVisitors(int $limit = 10): array
    {
        return Visitor::limit($limit)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get a specific visitor by ID
     */
    public function getVisitor(int $id): array
    {
        $visitor = Visitor::findOrFail($id);
        return $visitor->toArray();
    }

    /**
     * Search visitors by name or email
     */
    public function searchVisitors(string $query, int $limit = 10): array
    {
        return Visitor::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Create a new visitor
     */
    public function createVisitor(array $data): array
    {
        $visitor = Visitor::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_blocked' => $data['is_blocked'] ?? false,
        ]);
        
        return [
            'success' => true,
            'message' => 'Visitor created successfully',
            'visitor' => $visitor->toArray(),
        ];
    }

    /**
     * Update an existing visitor
     */
    public function updateVisitor(int $id, array $data): array
    {
        $visitor = Visitor::findOrFail($id);
        
        $visitor->update(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_blocked' => $data['is_blocked'] ?? null,
        ], fn($v) => $v !== null));
        
        return [
            'success' => true,
            'message' => 'Visitor updated successfully',
            'visitor' => $visitor->fresh()->toArray(),
        ];
    }

    /**
     * Delete a visitor
     */
    public function deleteVisitor(int $id): array
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->delete();
        
        return [
            'success' => true,
            'message' => 'Visitor deleted successfully',
            'deleted_id' => $id,
        ];
    }

    /**
     * Block or unblock a visitor
     */
    public function blockVisitor(int $id, bool $blocked = true): array
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->update(['is_blocked' => $blocked]);
        
        return [
            'success' => true,
            'message' => $blocked ? 'Visitor blocked' : 'Visitor unblocked',
            'visitor' => $visitor->fresh()->toArray(),
        ];
    }
}
