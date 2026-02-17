<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallFeedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_session_id',
        'user_id',
        'agent_id',
        'rating',
        'comment',
        'customer_name',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the call session for this feedback.
     */
    public function callSession()
    {
        return $this->belongsTo(CallSession::class);
    }

    /**
     * Get the user for this feedback.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the agent for this feedback.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get rating stars.
     */
    public function getRatingStarsAttribute(): array
    {
        $stars = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars[] = $i <= $this->rating;
        }
        return $stars;
    }

    /**
     * Get rating label.
     */
    public function getRatingLabelAttribute(): string
    {
        return match ($this->rating) {
            1 => 'Very Poor',
            2 => 'Poor',
            3 => 'Average',
            4 => 'Good',
            5 => 'Excellent',
            default => 'No Rating',
        };
    }
}
