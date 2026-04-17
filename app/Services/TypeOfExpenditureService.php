<?php

namespace App\Services;

use App\Models\TypeOfExpenditure;

class TypeOfExpenditureService
{
    public function list()
    {
        return TypeOfExpenditure::latest()
			->paginate(10);
    }

    public function store(array $data)
    {
        return TypeOfExpenditure::create($data);
    }

    public function update(TypeOfExpenditure $type, array $data)
    {
        $type->update($data);
        return $type;
    }

    public function delete(TypeOfExpenditure $type)
    {
        return $type->delete();
    }
}