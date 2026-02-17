<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallQueue extends Model
{
    use HasFactory;

    protected $table = 'call_queue';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'status',
        'position',
        'joined_at',
        'connected_at',
        'ended_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'connected_at' => 'datetime',
        'ended_at' => 'datetime',
        'position' => 'integer',
    ];

    /**
     * Get the user associated with this queue entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the call session associated with this queue entry.
     */
    public function callSession()
    {
        return $this->hasOne(CallSession::class);
    }

    /**
     * Get wait time in seconds.
     */
    public function getWaitTimeAttribute(): int
    {
        if ($this->connected_at) {
            return $this->connected_at->diffInSeconds($this->joined_at);
        }
        return now()->diffInSeconds($this->joined_at);
    }

    /**
     * Update queue positions.
     */
    public static function reassignPositions(): void
    {
        $waitingEntries = self::where('status', 'waiting')
            ->orderBy('joined_at')
            ->get();

        $position = 1;
        foreach ($waitingEntries as $entry) {
            $entry->update(['position' => $position]);
            $position++;
        }
    }

    /**
     * Get next in queue.
     */
    public static function getNext(): ?self
    {
        return self::where('status', 'waiting')
            ->orderBy('position')
            ->first();
    }

    /**
     * Count waiting customers.
     */
    public static function waitingCount(): int
    {
        return self::where('status', 'waiting')->count();
    }

    /**
     * Mark as connected.
     */
    public function markConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'connected_at' => now(),
        ]);
    }

    /**
     * Mark as ended.
     */
    public function markEnded(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }
}
