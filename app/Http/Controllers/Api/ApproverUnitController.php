<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApproverUnitRequest;
use App\Http\Resources\ApproverUnitResource;
use App\Services\ApproverUnitService;

class ApproverUnitController extends Controller
{
    protected $service;

    public function __construct(ApproverUnitService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $result = $this->service->getAll(10);

        return response()->json([
            'current_page' => $result['current_page'],
            'data' => ApproverUnitResource::collection($result['data'])
        ]);
    }

	public function show($one_charging_id)
	{
		return response()->json(
			$this->service->getByOneChargingId($one_charging_id)
		);
	}

    public function store(ApproverUnitRequest $request)
    {
        return response()->json(
            $this->service->store($request->validated())
        );
    }

    public function update(ApproverUnitRequest $request, $one_charging_id)
	{
		return response()->json(
			$this->service->updateByOneChargingId($one_charging_id, $request->validated())
		);
	}

	public function destroy($one_charging_id)
	{
		$response = $this->service->deleteByOneChargingId($one_charging_id);

		return response()->json($response);
	}
}