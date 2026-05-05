<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MainCapexResource;
use App\Services\MainCapexRequestorService;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class MainCapexRequestorController extends Controller
{
	protected $service;

    public function __construct(MainCapexRequestorService $service)
    {
        $this->service = $service;
    }

    public function getDataByRequestorTab(Request $request)
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

	public function confirmEstimate($id)
	{
		$userId = Auth::id();

		$result = $this->service->confirmEstimate($id, $userId);

		return response()->json([
			'success' => true,
			'message' => 'Estimate confirmed successfully',
			'data' => $result
		]);
	}
}
