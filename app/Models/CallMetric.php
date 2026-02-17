<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'total_calls',
        'connected_calls',
        'missed_calls',
        'total_duration',
        'total_wait_time',
        'average_wait_time',
        'average_rating',
    ];

    protected $casts = [
        'date' => 'date',
        'total_calls' => 'integer',
        'connected_calls' => 'integer',
        'missed_calls' => 'integer',
        'total_duration' => 'integer',
        'total_wait_time' => 'integer',
        'average_wait_time' => 'float',
        'average_rating' => 'float',
    ];

    /**
     * Update metrics for a given call session.
     */
    public static function updateMetrics(CallSession $session): void
    {
        $date = now()->toDateString();
        
        $metric = self::firstOrCreate(['date' => $date]);

        $isConnected = $session->status === 'ended' && $session->duration > 0;
        $waitTime = $session->callQueue ? $session->callQueue->wait_time : 0;

        $updates = [
            'total_calls' => $metric->total_calls + 1,
            'connected_calls' => $metric->connected_calls + ($isConnected ? 1 : 0),
            'missed_calls' => $metric->missed_calls + ($isConnected ? 0 : 1),
            'total_duration' => $metric->total_duration + $session->duration,
            'total_wait_time' => $metric->total_wait_time + $waitTime,
        ];

        // Recalculate averages
        if ($metric->connected_calls > 0) {
            $updates['average_wait_time'] = round($updates['total_wait_time'] / $updates['connected_calls'], 2);
            $updates['average_rating'] = self::calculateAverageRating($date);
        }

        $metric->update($updates);
    }

    /**
     * Calculate average rating for a date.
     */
    protected static function calculateAverageRating(string $date): float
    {
        $feedbacks = CallFeedback::whereDate('created_at', $date)->get();
        
        if ($feedbacks->isEmpty()) {
            return 0;
        }

        return round($feedbacks->avg('rating'), 2);
    }

    /**
     * Get today's metrics.
     */
    public static function today(): ?self
    {
        return self::where('date', now()->toDateString())->first();
    }

    /**
     * Get weekly metrics.
     */
    public static function thisWeek(): self
    {
        return self::whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw('SUM(total_calls) as total_calls, SUM(connected_calls) as connected_calls, 
                         SUM(missed_calls) as missed_calls, SUM(total_duration) as total_duration,
                         SUM(total_wait_time) as total_wait_time, AVG(average_rating) as average_rating')
            ->first();
    }

    /**
     * Get monthly metrics.
     */
    public static function thisMonth(): self
    {
        return self::whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->selectRaw('SUM(total_calls) as total_calls, SUM(connected_calls) as connected_calls, 
                         SUM(missed_calls) as missed_calls, SUM(total_duration) as total_duration,
                         SUM(total_wait_time) as total_wait_time, AVG(average_rating) as average_rating')
            ->first();
    }

    /**
     * Format duration for display.
     */
    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->total_duration, 3600);
        $minutes = intdiv($this->total_duration % 3600, 60);
        $seconds = $this->total_duration % 60;
        
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Format wait time for display.
     */
    public function getFormattedWaitTimeAttribute(): string
    {
        if ($this->average_wait_time == 0) {
            return '0s';
        }
        
        $minutes = intdiv($this->average_wait_time, 60);
        $seconds = $this->average_wait_time % 60;
        
        return sprintf('%dm %ds', $minutes, $seconds);
    }
}
