<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubCapex extends Model
{
    use HasFactory;

    protected $table = 'sub_subcapex';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'sub_capex_id',
        'particulars',
        'estimated_cost',
        'attachments',
        'estimator_id',
        'estimation_level',
        'estimation_status',
        'estimation_approver_id',
        'estimation_approving_status',
        'is_applicable',
        'remarks',
    ];

    /**
     * Casts (VERY IMPORTANT for money + flags)
     */
    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'is_applicable' => 'integer',
        'estimation_level' => 'integer',
        'estimator_id' => 'integer',
        'estimation_approver_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent SubCapex
     */
    public function subCapex()
    {
        return $this->belongsTo(SubCapex::class);
    }
}