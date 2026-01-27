<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorBlock extends Model
{
    //
    protected $table = 'visitor_blocks';
    protected $fillable = [
        'visitor_id',
        'block_type',
        'reason',
        'blocked_by',
        'blocked_at',
        'unblocked_by',
        'unblocked_at',
        'status'
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function blocker()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function unblocker()
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }
}
