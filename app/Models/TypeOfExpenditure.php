<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeOfExpenditure extends Model
{
    use HasFactory;

    protected $table = 'type_of_expenditures';

    protected $fillable = [
        'name',
        'value'
    ];

	public function typeOfSubCapex()
	{
		return $this->hasMany(TypeOfSubCapex::class, 'type_of_expenditure_id');
	}
}
