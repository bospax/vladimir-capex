<?php

namespace App\Models;

use App\Filters\ParticularFilter;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Model;

class Particular extends Model
{
	use Filterable;

	protected string $default_filters = ParticularFilter::class;

    protected $fillable = [
        'type_of_subcapex_id',
        'name'
    ];

    public function typeOfSubCapex()
    {
        return $this->belongsTo(TypeOfSubCapex::class, 'type_of_subcapex_id');
    }
}