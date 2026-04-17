<?php

namespace App\Services;

use App\Models\Particular;

class ParticularService
{
    public function list()
    {
        return Particular::with('typeOfSubCapex')
			->useFilters()
            ->latest()
            ->dynamicPaginate();
    }
	
    public function store(array $data)
    {
        return Particular::create($data);
    }

    public function update(Particular $particular, array $data)
    {
        $particular->update($data);

        return $particular;
    }

    public function delete(Particular $particular)
    {
        $particular->delete();
    }
}