<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MainCapexMajorApproverService;
use Illuminate\Http\Request;

class MainCapexMajorApproverController extends Controller
{
    protected $service;

    public function __construct(MainCapexMajorApproverService $service)
    {
        $this->service = $service;
    }

    public function approve($id)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->approve($id)
        ]);
    }

    public function reject($id, Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->reject($id, $request->remarks)
        ]);
    }

    public function list()
    {
        return $this->service->getMajorApproverList();
    }

    public function show($id)
    {
        return $this->service->getCapexDetails($id);
    }
}
