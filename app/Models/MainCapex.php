<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainCapex extends Model
{
    use HasFactory;

    protected $table = 'main_capex';

    protected $fillable = [
        'one_charging_id',
        'capex_number',
        'expenditure_type',
        'budget_type',
        'project_description',
        'enrolled_budget_amount',
        'total_applied_amount',
        'total_difference_amount',
        'total_variance_amount',
        'requestor_id',
		'form_type',
		'first_phase_level',
        'estimation_level',
        'estimation_approving_level',
        'major_level',
        'status',
        'phase',
        'remarks',
    ];

	public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

	public function oneCharging()
    {
        return $this->belongsTo(OneCharging::class, 'one_charging_id');
    }

	public function subCapex()
    {
        return $this->hasMany(SubCapex::class, 'main_capex_id');
    }

	public function approverSets()
    {
        return $this->hasMany(ApproverSet::class, 'main_capex_id');
    }

	public function histories()
    {
        return $this->hasMany(MainCapexHistory::class, 'main_capex_id');
    }
}
