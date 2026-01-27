<?php

namespace App\Models\pragati;

use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    //
    protected $table = 'claims';
     protected $fillable = [
        'user_id',
        'insurance_package_id',
        'order_id',
        'claim_number',
        'claim_amount',
        'reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(InsurancePackage::class, 'insurance_package_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
