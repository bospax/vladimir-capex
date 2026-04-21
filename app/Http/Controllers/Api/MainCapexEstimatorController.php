<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MainCapexEstimatorService;
use Illuminate\Http\Request;

class MainCapexEstimatorController extends Controller
{
    protected $service;

    public function __construct(MainCapexEstimatorService $service)
    {
        $this->service = $service;
    }

    /**
     * GET: Estimator List
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getEstimatorList()
        ]);
    }

    /**
     * GET: Capex Details (with sub + subsub)
     */
    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getCapexDetails($id)
        ]);
    }

	public function saveEstimation($id, Request $request)
	{
		$data = $request->validate([
			'sub_capex' => 'required|array',
			'sub_capex.*.sub_capex_id' => 'required|integer',
			'sub_capex.*.items' => 'required|array',
			'sub_capex.*.items.*.particulars' => 'required|string',
			'sub_capex.*.items.*.estimated_cost' => 'required|numeric|min:0',
			'sub_capex.*.items.*.remarks' => 'nullable|string',
		]);

		$result = $this->service->saveEstimation($id, $data);

		return response()->json([
			'success' => true,
			'message' => 'Estimation saved successfully',
			'data' => $result
		]);
	}
}