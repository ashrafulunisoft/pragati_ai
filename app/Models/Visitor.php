<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Visitor extends Model
{
    use SoftDeletes, Notifiable;

     protected $table = 'visitors';

    protected $fillable = [
        'name', 'phone', 'email', 'address', 'is_blocked'
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function blocks()
    {
        return $this->hasMany(VisitorBlock::class);
    }

    //otp
    public function otps()
    {
        return $this->hasMany(VisitorOtp::class);
    }

    public function activeOtp()
    {
        return $this->hasOne(VisitorOtp::class)
                    ->where('is_active', true);
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    /**
     * Route notifications for the SMS channel.
     */
    public function routeNotificationForSms($notification)
    {
        return $this->phone;
    }
}
