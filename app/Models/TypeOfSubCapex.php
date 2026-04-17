<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfSubCapex extends Model
{
    use HasFactory;

    protected $table = 'type_of_subcapex';

    protected $fillable = [
		'type_of_expenditure_id',
        'name',
        'with_remarks'
    ];

	protected $casts = [
		'with_remarks' => 'boolean'
	];

	public function typeOfExpenditure()
    {
        return $this->belongsTo(TypeOfExpenditure::class, 'type_of_expenditure_id');
    }

	public function particulars()
	{
		return $this->hasMany(Particular::class, 'type_of_subcapex_id');
	}
}
