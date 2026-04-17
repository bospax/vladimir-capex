<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCapex extends Model
{
    protected $table = 'sub_capex';

    protected $fillable = [
        'main_capex_id',
        'index',
        'type_of_subcapex',
        'remarks',
        'building_number',
        'approved_amount',
        'applied_amount',
        'estimate_amount',
        'variance_amount',
    ];

    public function mainCapex()
    {
        return $this->belongsTo(MainCapex::class);
    }
}
