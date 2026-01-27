<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitLog extends Model
{
    //
    protected $table = 'visit_logs';
    protected $fillable = [
        'visit_id',
        'rfid_id',
        'checkin_time',
        'checkout_time',
        'total_minutes'
    ];

    protected $casts = [
        'checkin_time' => 'datetime',
        'checkout_time' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function rfid()
    {
        return $this->belongsTo(Rfid::class);
    }
}
