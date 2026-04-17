<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMainCapexRequest;
use App\Http\Requests\Api\UpdateMainCapexRequest;
use App\Http\Resources\MainCapexResource;
use App\Services\MainCapexHistoryService;
use App\Services\MainCapexService;
use App\Services\MainCapexTimelineService;

class MainCapexController extends Controller
{
    protected $service;
	protected $timelineService;
	protected $historyService;

    public function __construct(MainCapexService $service, MainCapexTimelineService $timelineService, MainCapexHistoryService $historyService)
    {
        $this->service = $service;
        $this->timelineService = $timelineService;
        $this->historyService = $historyService;
    }

    public function index()
    {
        $data = $this->service->list(request());
        return MainCapexResource::collection($data);
    }

    public function store(StoreMainCapexRequest $request)
    {
		$data = $this->service->createWithChildren($request->validated());

        return response()->json([
            'status' => 201,
            'message' => 'Main Capex created successfully!',
            'data' => new MainCapexResource($data),
        ]);
    }

    public function show($id)
    {
        $data = $this->service->find($id);

        return new MainCapexResource($data);
    }

    public function update(UpdateMainCapexRequest $request, $id)
    {
        $data = $this->service->updateWithChildren($id, $request->validated());

        return response()->json([
            'status' => 200,
            'message' => 'Main Capex updated successfully!',
            'data' => new MainCapexResource($data),
        ]);
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return response()->json([
            'status' => 200,
            'message' => 'Main Capex deleted successfully!',
        ]);
    }

	public function timeline($id)
    {
        return response()->json(
            $this->timelineService->getTimeline($id)
        );
    }

	public function history($capexId)
    {
        $history = $this->historyService->getHistory($capexId);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
