<?php

namespace App\Services;

use App\Models\MainCapex;
use App\Models\ApproverSet;
use Illuminate\Support\Facades\DB;

class MainCapexMajorApproverService
{
    protected $historyService;

    public function __construct(MainCapexHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    /*
    |----------------------------------------------------------------------
    | APPROVE MAJOR
    |----------------------------------------------------------------------
    */

    public function approve($id)
    {
        return DB::transaction(function () use ($id) {

            // $userId = Auth::id();
            $userId = 25; // HARDCODED for testing

            $capex = MainCapex::findOrFail($id);

            /*
            |------------------------------------------------------------------
            | VALIDATION
            |------------------------------------------------------------------
            */

            if ($capex->phase !== 'for_major_approval') {
                throw new \Exception('Not in major approval phase.');
            }

            $currentLevel = $capex->major_level;

            /*
            |------------------------------------------------------------------
            | CHECK APPROVER
            |------------------------------------------------------------------
            */

            $approver = ApproverSet::where('main_capex_id', $id)
                ->where('approver_set_name', 'MAJOR APPROVER')
                ->where('level', $currentLevel)
                ->where('user_id', $userId)
                ->first();

            if (!$approver) {
                throw new \Exception('You are not allowed to approve.');
            }

            /*
            |------------------------------------------------------------------
            | CHECK IF LAST LEVEL
            |------------------------------------------------------------------
            */

            $maxLevel = ApproverSet::where('main_capex_id', $id)
                ->where('approver_set_name', 'MAJOR APPROVER')
                ->max('level');

            if ($currentLevel >= $maxLevel) {

                // ✅ FINAL APPROVAL (END OF FLOW)
                $capex->update([
                    'major_level' => $currentLevel + 1,
                    'status' => 'approved',
                    'phase' => 'major_approval_completed',
                ]);

            } else {

                // ✅ MOVE TO NEXT APPROVER
                $capex->update([
                    'major_level' => $currentLevel + 1,
                    'status' => 'for_approval',
                    'phase' => 'for_major_approval',
                ]);
            }

            /*
            |------------------------------------------------------------------
            | HISTORY
            |------------------------------------------------------------------
            */

            $this->historyService->log(
                $capex->fresh(),
                $userId,
                'MAJOR APPROVER',
                "APPROVED MAJOR LEVEL {$currentLevel}"
            );

            return $capex->fresh();
        });
    }

    /*
    |----------------------------------------------------------------------
    | REJECT MAJOR
    |----------------------------------------------------------------------
    */

    public function reject($id, $remarks = null)
    {
        return DB::transaction(function () use ($id, $remarks) {

            // $userId = Auth::id();
            $userId = 19; // HARDCODED for testing

            $capex = MainCapex::findOrFail($id);

            if ($capex->phase !== 'for_major_approval') {
                throw new \Exception('Not in major approval phase.');
            }

            $currentLevel = $capex->major_level;

            /*
            |------------------------------------------------------------------
            | CHECK APPROVER
            |------------------------------------------------------------------
            */

            $approver = ApproverSet::where('main_capex_id', $id)
                ->where('approver_set_name', 'MAJOR APPROVER')
                ->where('level', $currentLevel)
                ->where('user_id', $userId)
                ->first();

            if (!$approver) {
                throw new \Exception('You are not allowed to reject.');
            }

            /*
            |------------------------------------------------------------------
            | RESET FLOW (BACK TO REQUESTOR CONFIRMATION)
            |------------------------------------------------------------------
            */

            $capex->update([
                'major_level' => 1,
                'status' => 'rejected',
                'phase' => 'estimate_completed',
                'remarks' => $remarks,
            ]);

            /*
            |------------------------------------------------------------------
            | HISTORY
            |------------------------------------------------------------------
            */

            $this->historyService->log(
                $capex->fresh(),
                $userId,
                'MAJOR APPROVER',
                "REJECTED MAJOR LEVEL {$currentLevel}"
            );

            return $capex->fresh();
        });
    }

    /*
    |----------------------------------------------------------------------
    | LIST FOR MAJOR APPROVER
    |----------------------------------------------------------------------
    */

    public function getMajorApproverList()
    {
        // $userId = Auth::id();
        $userId = 25; // HARDCODED for testing

        return MainCapex::where('phase', 'for_major_approval')
            ->whereHas('approverSets', function ($query) use ($userId) {

                $query->where('user_id', $userId)
                    ->where('approver_set_name', 'MAJOR APPROVER')

                    // ✅ MATCH CURRENT LEVEL
                    ->whereColumn(
                        'approver_set.level',
                        'main_capex.major_level'
                    );
            })
            ->latest()
            ->get();
    }

    /*
    |----------------------------------------------------------------------
    | DETAILS VIEW
    |----------------------------------------------------------------------
    */

    public function getCapexDetails($id)
    {
        // $userId = Auth::id();
        $userId = 25; // HARDCODED for testing

        return MainCapex::with([
            'subCapex.subSubCapex'
        ])
        ->where('id', $id)
        ->whereHas('approverSets', function ($query) use ($userId) {

            $query->where('user_id', $userId)
                ->where('approver_set_name', 'MAJOR APPROVER')
                ->whereColumn(
                    'approver_set.level',
                    'main_capex.major_level'
                );
        })
        ->firstOrFail();
    }
}