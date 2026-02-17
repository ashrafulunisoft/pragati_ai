<?php

namespace App\Models\pragati;

use App\Models\pragati\Claim;
use App\Models\pragati\Order;
use Illuminate\Database\Eloquent\Model;

class InsurancePackage extends Model
{
    //
    protected $table = 'insurance_packages';
      protected $fillable = [
        'name',
        'description',
        'price',
        'coverage_amount',
        'duration_months',
        'is_active',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }
}
