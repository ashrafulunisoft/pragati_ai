<?php

namespace App\Models\pragati;

use App\Models\User;
use App\Models\pragati\Claim;
use App\Models\pragati\InsurancePackage;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table='orders';
    protected $fillable = [
        'user_id',
        'insurance_package_id',
        'policy_number',
        'status',
        'start_date',
        'end_date',
    ];
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(InsurancePackage::class, 'insurance_package_id');
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
