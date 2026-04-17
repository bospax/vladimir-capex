<?php

namespace App\Services;

use App\Models\TypeOfSubCapex;

class TypeOfSubCapexService
{
	public function list()
    {
        return TypeOfSubCapex::with('typeOfExpenditure')
            ->latest()
			->paginate(10);
    }

    public function store(array $data)
    {
        return TypeOfSubCapex::create($data);
    }

    public function update(TypeOfSubCapex $type, array $data)
    {
        $type->update($data);
        return $type;
    }

    public function delete(TypeOfSubCapex $type)
    {
        return $type->delete();
    }
}