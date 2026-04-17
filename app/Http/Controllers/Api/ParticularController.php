<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreParticularRequest;
use App\Http\Requests\Api\UpdateParticularRequest;
use App\Http\Resources\ParticularCollection;
use App\Http\Resources\ParticularResource;
use App\Models\Particular;
use App\Services\ParticularService;

class ParticularController extends Controller
{
    private $service;

    public function __construct(ParticularService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
		return new ParticularCollection(
			$this->service->list()
		);
		
        // return ParticularResource::collection(
        //     $this->service->list()
        // );
    }

    public function store(StoreParticularRequest $request)
    {
        $data = $this->service->store($request->validated());

        return new ParticularResource($data);
    }

    public function show(Particular $particular)
    {
        return new ParticularResource($particular);
    }

    public function update(UpdateParticularRequest $request, Particular $particular)
    {
        $data = $this->service->update(
            $particular,
            $request->validated()
        );

        return new ParticularResource($data);
    }

    public function destroy(Particular $particular)
    {
        $this->service->delete($particular);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}