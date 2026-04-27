<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainCapexHistory extends Model
{
    protected $table = 'main_capex_history';

    protected $fillable = [
        'main_capex_id',
        'capex_number',
        'one_charging_id',
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
        'approver_id',
        'change_log',
        'revision_no',
        'approver_set_name'
    ];

    // 🔗 Relationships
    public function mainCapex()
    {
        return $this->belongsTo(MainCapex::class, 'main_capex_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }
}