<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MainCapexEstimatorApproverService;
use Illuminate\Http\Request;

class MainCapexEstimatorApproverController extends Controller
{
    protected $service;

    public function __construct(MainCapexEstimatorApproverService $service)
    {
        $this->service = $service;
    }

	public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->getEstimatorApproverList()
        ]);
    }

	public function show($id)
	{
		return response()->json([
			'success' => true,
			'data' => $this->service->getCapexDetails($id)
		]);
	}

    public function approve($id)
    {
        $result = $this->service->approve($id);

        return response()->json([
            'success' => true,
            'message' => 'Estimation approved',
            'data' => $result
        ]);
    }

    public function reject($id, Request $request)
    {
        $data = $request->validate([
            'remarks' => 'nullable|string'
        ]);

        $result = $this->service->reject($id, $data['remarks'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Estimation rejected',
            'data' => $result
        ]);
    }
}
