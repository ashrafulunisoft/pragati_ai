<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_name',
        'agora_uid',
        'user_id',
        'agent_id',
        'call_queue_id',
        'status',
        'started_at',
        'ended_at',
        'duration',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration' => 'integer',
    ];

    /**
     * Get the user (customer) for this call.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the agent for this call.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the queue entry for this call.
     */
    public function callQueue()
    {
        return $this->belongsTo(CallQueue::class);
    }

    /**
     * Get feedback for this call.
     */
    public function feedback()
    {
        return $this->hasOne(CallFeedback::class);
    }

    /**
     * Generate unique channel name.
     */
    public static function generateChannelName(): string
    {
        return 'call_' . uniqid() . '_' . time();
    }

    /**
     * End the call and calculate duration.
     */
    public function endCall(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
            'duration' => now()->diffInSeconds($this->started_at),
        ]);

        // Update agent stats
        if ($this->agent) {
            $this->agent->recordCall($this->duration);
            $this->agent->setFree();
        }

        // Update queue entry
        if ($this->callQueue) {
            $this->callQueue->markEnded();
            CallQueue::reassignPositions();
        }

        // Update metrics
        CallMetric::updateMetrics($this);
    }

    /**
     * Connect the call.
     */
    public function connect(): void
    {
        $this->update(['status' => 'connected']);
    }

    /**
     * Check if call is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'connected' || $this->status === 'ringing';
    }
}
