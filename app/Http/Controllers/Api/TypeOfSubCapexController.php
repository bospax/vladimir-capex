<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTypeOfSubCapexRequest;
use App\Http\Requests\Api\UpdateTypeOfSubCapexRequest;
use App\Http\Resources\TypeOfSubCapexResource;
use App\Models\TypeOfSubCapex;
use App\Services\TypeOfSubCapexService;

class TypeOfSubCapexController extends Controller
{
    private $service;

    public function __construct(TypeOfSubCapexService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->list();

        return TypeOfSubCapexResource::collection($data);
    }

    public function store(StoreTypeOfSubCapexRequest $request)
    {
        $data = $this->service->store($request->validated());

        return new TypeOfSubCapexResource($data);
    }

    public function show(TypeOfSubCapex $type_of_subcapex)
    {
        return new TypeOfSubCapexResource(
            $type_of_subcapex->load(['typeOfExpenditure', 'particulars'])
        );
    }

    public function update(UpdateTypeOfSubCapexRequest $request, TypeOfSubCapex $type_of_subcapex)
    {
        $data = $this->service->update(
            $type_of_subcapex,
            $request->validated()
        );

        return new TypeOfSubCapexResource($data);
    }

    public function destroy(TypeOfSubCapex $type_of_subcapex)
    {
        $this->service->delete($type_of_subcapex);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}