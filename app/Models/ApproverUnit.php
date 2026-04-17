<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApproverUnit extends Model
{
    protected $fillable = [
        'unit_id',
        'subunit_id',
        'one_charging_id',
        'approver_id',
        'level',
        'approver_set_name',
    ];

    public function oneCharging()
    {
        return $this->belongsTo(OneCharging::class, 'one_charging_id');
    }

    public function approver()
    {
        return $this->belongsTo(Approvers::class, 'approver_id');
    }
}