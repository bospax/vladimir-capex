<?php

namespace App\Services;

use App\Models\MainCapex;

class MainCapexRequestorService
{
	public function getByConditions(array $conditions)
	{
		$query = MainCapex::query();

		if (!empty($conditions['status'])) {
			$query->whereIn('status', $conditions['status']);
		}

		if (!empty($conditions['phase'])) {
			$query->where('phase', $conditions['phase']);
		}

		return $query->latest()->paginate(10);
	}
}

