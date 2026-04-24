<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MainCapexResource;
use App\Services\MainCapexFaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MainCapexFaController extends Controller
{
    protected $service;

    public function __construct(MainCapexFaService $service)
    {
        $this->service = $service;
    }

    /**
     * GET: FA Pending List
     */
    public function index()
    {
        $data = $this->service->getFaPendingList();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

	public function getDataByFaTab(Request $request)
	{
		$conditions = [
			'user_id' => Auth::id(),
			'section' => $request->input('section'),
			'status' => $request->input('status') ? explode(',', $request->input('status')) : [],
			'phase' => $request->input('phase'),
		];

		$data = $this->service->getByConditions($conditions);

		return MainCapexResource::collection($data);
	}

    /**
     * POST: Return
     */
    public function return($id, Request $request)
    {
        $data = $this->service->return($id, $request->input('remarks'));

        return response()->json([
            'success' => true,
            'message' => 'Transaction returned successfully.',
            'data' => $data
        ]);
    }

	public function reject($id, Request $request)
    {
        $data = $this->service->reject($id, $request->input('remarks'));

        return response()->json([
            'success' => true,
            'message' => 'Transaction rejected successfully.',
            'data' => $data
        ]);
    }

    /**
     * POST: Submit
     */
    public function submit($id)
    {
        $data = $this->service->submit($id);

        return response()->json([
            'success' => true,
            'message' => 'Transaction submitted successfully.',
            'data' => $data
        ]);
    }
}