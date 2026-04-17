<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectDescription extends Model
{
    use HasFactory;

    protected $table = 'project_descriptions';

    protected $fillable = [
        'id_number',
		'description',
		'type_of_expenditure',
		'years',
		'quantity',
		'unit_cost',
		'enrolled_budget_amount',
		'remarks',
		'date_applied',
		'cost_applied'
    ];
}
