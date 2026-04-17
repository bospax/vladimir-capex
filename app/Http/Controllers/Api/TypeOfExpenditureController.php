<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTypeOfExpenditureRequest as ApiStoreTypeOfExpenditureRequest;
use App\Http\Requests\Api\UpdateTypeOfExpenditureRequest as ApiUpdateTypeOfExpenditureRequest;
use App\Http\Resources\TypeOfExpenditureResource;
use App\Models\TypeOfExpenditure;
use App\Services\TypeOfExpenditureService;

class TypeOfExpenditureController extends Controller
{
    private $service;

    public function __construct(TypeOfExpenditureService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->list();

        return TypeOfExpenditureResource::collection($data);
    }

    public function store(ApiStoreTypeOfExpenditureRequest $request)
    {
        $type = $this->service->store($request->validated());

        return new TypeOfExpenditureResource($type);
    }

    public function show(TypeOfExpenditure $type_of_expenditure)
    {
        return new TypeOfExpenditureResource($type_of_expenditure->load('typeOfSubCapex'));
    }

    public function update(ApiUpdateTypeOfExpenditureRequest $request, TypeOfExpenditure $type_of_expenditure)
    {
        $type = $this->service->update($type_of_expenditure, $request->validated());

        return new TypeOfExpenditureResource($type);
    }

    public function destroy(TypeOfExpenditure $type_of_expenditure)
    {
        $this->service->delete($type_of_expenditure);

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}