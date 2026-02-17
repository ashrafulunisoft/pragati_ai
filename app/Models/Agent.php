<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'department',
        'status',
        'last_seen',
        'total_calls',
        'total_duration',
        'average_rating',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'total_calls' => 'integer',
        'total_duration' => 'integer',
        'average_rating' => 'float',
    ];

    /**
     * Get the user associated with this agent.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get call sessions for this agent.
     */
    public function callSessions()
    {
        return $this->hasMany(CallSession::class);
    }

    /**
     * Get feedback for this agent.
     */
    public function feedback()
    {
        return $this->hasMany(CallFeedback::class);
    }

    /**
     * Check if agent is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'free';
    }

    /**
     * Set agent as busy.
     */
    public function setBusy(): void
    {
        $this->update(['status' => 'busy']);
    }

    /**
     * Set agent as free.
     */
    public function setFree(): void
    {
        $this->update(['status' => 'free', 'last_seen' => now()]);
    }

    /**
     * Set agent as offline.
     */
    public function setOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    /**
     * Record a completed call.
     */
    public function recordCall(int $duration = 0, int $rating = 0): void
    {
        $this->update([
            'total_calls' => $this->total_calls + 1,
            'total_duration' => $this->total_duration + $duration,
            'average_rating' => $this->calculateAverageRating($rating),
        ]);
    }

    /**
     * Calculate new average rating.
     */
    protected function calculateAverageRating(int $newRating): float
    {
        if ($newRating === 0) {
            return $this->average_rating;
        }

        $totalRating = ($this->average_rating * $this->total_calls) + $newRating;
        return round($totalRating / ($this->total_calls + 1), 2);
    }
}
