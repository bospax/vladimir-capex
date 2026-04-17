<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ParticularCollection extends ResourceCollection
{

	public function toArray(Request $request): array
    {
        return [
			'status' => 200,
			'message' => 'Data fetched successfully!',
			'data' => ParticularResource::collection($this->collection),
            'current_page' => $this->resource->currentPage(),
            'first_page_url' => $this->resource->url(1),
            'last_page' => $this->resource->lastPage(),
            'last_page_url' => $this->resource->url($this->resource->lastPage()),
            'links' => $this->resource->linkCollection(),
            'next_page_url' => $this->resource->nextPageUrl(),
            'prev_page_url' => $this->resource->previousPageUrl(),
            'per_page' => (string) $this->resource->perPage(),
            'total' => $this->resource->total(),
        ];
    }

	public function paginationInformation($request, $paginated, $default)
	{
		return [];
	}

    // public function toArray(Request $request): array
	// {
	// 	return [
	// 		'status' => 200,
	// 		'message' => 'Data fetched successfully!',
	// 		'data' => ParticularResource::collection($this->collection),
	// 	];
	// }

	// public function paginationInformation($request, $paginated, $default)
	// {
	// 	return [
	// 		'meta' => [
	// 			'current_page' => $paginated['current_page'],
	// 			'per_page' => $paginated['per_page'],
	// 			'total' => $paginated['total'],
	// 			'last_page' => $paginated['last_page'],
	// 		],
	// 		'links' => $paginated['links'],
	// 	];
	// }
}
