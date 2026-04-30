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

			// ✅ NEW: attachment validation
			'sub_capex.*.items.*.attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,xlsx|max:10240',
		]);

		/*
		|--------------------------------------------------------------------------
		| 🔥 IMPORTANT: MERGE FILES INTO VALIDATED DATA
		|--------------------------------------------------------------------------
		*/

		foreach ($request->file('sub_capex', []) as $i => $sub) {
			foreach ($sub['items'] ?? [] as $j => $item) {

				if (isset($item['attachment'])) {
					$data['sub_capex'][$i]['items'][$j]['attachment'] = $item['attachment'];
				}
			}
		}

		$result = $this->service->saveEstimation($id, $data);

		return response()->json([
			'success' => true,
			'message' => 'Estimation saved successfully',
			'data' => $result
		]);
	}

	public function updateEstimation($id, Request $request)
	{
		$data = $request->validate([
			'sub_capex' => 'required|array',

			'sub_capex.*.sub_capex_id' => 'required|integer',

			'sub_capex.*.items' => 'required|array',

			'sub_capex.*.items.*.particulars' => 'required|string',
			'sub_capex.*.items.*.estimated_cost' => 'required|numeric|min:0',
			'sub_capex.*.items.*.remarks' => 'nullable|string',

			// 🔥 ADD THIS (attachments)
			'sub_capex.*.items.*.attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
		]);

		$result = $this->service->updateEstimation($id, $data, $request);

		return response()->json([
			'success' => true,
			'message' => 'Estimation updated successfully',
			'data' => $result
		]);
	}

	public function return($id, Request $request)
	{
		$remarks = $request->input('remarks');

		$result = $this->service->return($id, $remarks);

		return response()->json([
			'success' => true,
			'message' => 'Capex returned successfully',
			'data' => $result
		]);
	}
}