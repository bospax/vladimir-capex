<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MainCapexFaService;

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

    /**
     * POST: Return
     */
    public function return($id)
    {
        $data = $this->service->return($id);

        return response()->json([
            'success' => true,
            'message' => 'Transaction returned successfully.',
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