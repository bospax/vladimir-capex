<?php

namespace App\Services;

use App\Models\MainCapex;
use App\Models\ApproverSet;
use App\Models\MainCapexHistory;
use App\Models\User;

class MainCapexTimelineService
{
    public function getTimeline($capexId)
    {
        $capex = MainCapex::findOrFail($capexId);

        $approvers = ApproverSet::where('main_capex_id', $capexId)
            ->orderBy('level')
            ->get()
            ->groupBy('approver_set_name');

        $rawHistory = MainCapexHistory::where('main_capex_id', $capexId)
            ->where('revision_no', $capex->revision_no)
            ->orderByDesc('id')
            ->get();

        // ✅ GROUP BY approver_set_name + approver_id
        $history = $rawHistory
            ->groupBy(fn($item) => $item->approver_set_name . '_' . $item->approver_id)
            ->map(fn($items) => $items->first());

        $userIds = $approvers->flatten()->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $timeline = [];
        $isActivePhase = true;

        $timeline[] = $this->buildRequestor();

        $timeline[] = $this->buildSequentialPhase(
            'FIRST PHASE APPROVER',
            'First Phase Approval',
            $approvers,
            $history,
            $users,
            $isActivePhase,
            $capex
        );

        for ($level = 1; $level <= 4; $level++) {

            $timeline[] = $this->buildEstimatorLevel(
                "ESTIMATOR LEVEL $level",
                "Estimator Level $level",
                $approvers,
                $history,
                $rawHistory,
                $users,
                $isActivePhase,
                $level,
                $capex
            );

            $timeline[] = $this->buildEstimatorApproverLevel(
                'ESTIMATOR APPROVER',
                "Estimator Approver Level $level",
                $approvers,
                $history,
                $users,
                $isActivePhase,
                $level,
                $capex
            );
        }

        $timeline[] = $this->buildRequestorConfirmation($capex, $isActivePhase);

        $timeline[] = $this->buildSequentialPhase(
            'MAJOR APPROVER',
            'Major Approval',
            $approvers,
            $history,
            $users,
            $isActivePhase,
            $capex
        );

        return $timeline;
    }

    /*
    |------------------------------------------------------------------
    | ESTIMATOR LEVEL
    |------------------------------------------------------------------
    */

    private function buildEstimatorLevel(
        $groupKey,
        $label,
        $approvers,
        $history,
        $rawHistory,
        $users,
        &$isActivePhase,
        $level,
        $capex
    ) {
        $list = $approvers->get($groupKey, collect());
        $usersArr = [];

        $isRejected = $this->isEstimatorLevelRejected($rawHistory, $level, $capex, $groupKey);

        $actor = $list->first(fn($u) =>
            isset($history[$groupKey . '_' . $u->user_id])
        );

        foreach ($list as $item) {

            $key = $groupKey . '_' . $item->user_id;
            $latest = $history[$key] ?? null;

            if (!$isActivePhase) {
                $status = 'pending';

            } else {

                if ($isRejected) {
                    $status = ($actor && $item->user_id == $actor->user_id)
                        ? 'current'
                        : 'N/A';

                } else {

                    if ($actor && $latest) {
                        $status = $latest->status === 'rejected'
                            ? ($item->user_id == $actor->user_id ? 'rejected' : 'N/A')
                            : ($item->user_id == $actor->user_id ? 'completed' : 'N/A');
                    } else {
                        $status = 'current';
                    }
                }
            }

            $usersArr[] = [
                'name' => $users[$item->user_id]->firstname ?? 'N/A',
                'status' => $this->mapStatus('estimator', $status)
            ];
        }

        $phaseStatus = !$isActivePhase
            ? 'pending'
            : ($isRejected ? 'current' : ($actor ? 'completed' : 'current'));

        if ($phaseStatus !== 'completed') {
            $isActivePhase = false;
        }

        return [
            'label' => $label,
            'phase_status' => $this->mapStatus('estimator', $phaseStatus),
            'users' => $usersArr
        ];
    }

    /*
    |------------------------------------------------------------------
    | ESTIMATOR APPROVER
    |------------------------------------------------------------------
    */

    private function buildEstimatorApproverLevel(
        $groupKey,
        $label,
        $approvers,
        $history,
        $users,
        &$isActivePhase,
        $level,
        $capex
    ) {
        $list = $approvers->get($groupKey, collect());
        $usersArr = [];

        foreach ($list as $item) {

            if ($item->level != $level) continue;

            $key = $groupKey . '_' . $item->user_id;
            $latest = $history[$key] ?? null;

            if (!$isActivePhase) {
                $status = ($latest && $latest->status === 'rejected')
                    ? 'rejected'
                    : 'pending';

            } else {

                if (
                    $capex->phase === 'for_estimate_approval'
                    && $capex->estimation_approving_level == $level
                    && $capex->status !== 'rejected'
                ) {
                    $status = 'current';

                } else {
                    if ($latest) {
                        $status = $latest->status === 'rejected'
                            ? 'rejected'
                            : 'completed';
                    } else {
                        $status = 'current';
                    }
                }
            }

            $usersArr[] = [
                'name' => $users[$item->user_id]->firstname ?? 'N/A',
                'level' => $item->level,
                'status' => $this->mapStatus('estimator_approver', $status)
            ];
        }

        $phaseStatus = !$isActivePhase
            ? 'pending'
            : $this->getPhaseStatus($usersArr);

        if ($phaseStatus !== 'completed') {
            $isActivePhase = false;
        }

        return [
            'label' => $label,
            'phase_status' => $this->mapStatus('estimator_approver', $phaseStatus),
            'users' => $usersArr
        ];
    }

    /*
    |------------------------------------------------------------------
    | SEQUENTIAL PHASE
    |------------------------------------------------------------------
    */

    private function buildSequentialPhase(
        $groupKey,
        $label,
        $approvers,
        $history,
        $users,
        &$isActivePhase,
        $capex
    ) {
        $list = $approvers->get($groupKey, collect());

        if (
            $groupKey === 'MAJOR APPROVER'
            && $capex->phase === 'major_approval_completed'
        ) {
            return [
                'label' => $label,
                'phase_status' => 'major_approval_completed',
                'users' => $list->map(fn($item) => [
                    'name' => $users[$item->user_id]->firstname ?? 'N/A',
                    'level' => $item->level,
                    'status' => 'approved'
                ])->values()
            ];
        }

        $usersArr = [];
        $currentLevel = 1;
        $hasReturned = false;

        foreach ($list as $item) {

            $key = $groupKey . '_' . $item->user_id;
            $latest = $history[$key] ?? null;

            if (!$isActivePhase) {

                if ($latest) {
                    if ($latest->status === 'returned') {
                        $status = 'returned';
                        $hasReturned = true;
                    } elseif ($latest->status === 'rejected') {
                        $status = 'rejected';
                    } else {
                        $status = 'completed';
                    }
                } else {
                    $status = 'pending';
                }

            } else {

                if ($latest) {

                    if ($latest->status === 'returned') {
                        $status = 'returned';
                        $hasReturned = true;

                    } elseif ($latest->status === 'rejected') {
                        $status = 'rejected';

                    } else {
                        $status = 'completed';
                        $currentLevel++;
                    }

                } elseif ($item->level == $currentLevel) {
                    $status = 'current';
                } else {
                    $status = 'pending';
                }
            }

            $usersArr[] = [
                'name' => $users[$item->user_id]->firstname ?? 'N/A',
                'level' => $item->level,
                'status' => $this->mapStatus('major', $status)
            ];
        }

        $phaseStatus = $hasReturned
            ? 'returned'
            : $this->getPhaseStatus($usersArr);

        if ($phaseStatus !== 'completed') {
            $isActivePhase = false;
        }

        return [
            'label' => $label,
            'phase_status' => $this->mapStatus('major', $phaseStatus),
            'users' => $usersArr
        ];
    }

    private function buildRequestor()
    {
        return [
            'label' => 'Requestor',
            'phase_status' => 'requested',
            'users' => [
                ['name' => 'Requestor', 'status' => 'requested']
            ]
        ];
    }

    private function buildRequestorConfirmation($capex, &$isActivePhase)
    {
        if ($capex->phase === 'major_approval_completed') {
            return [
                'label' => 'Requestor Confirmation',
                'phase_status' => 'confirmed',
                'users' => [['name' => 'Requestor', 'status' => 'confirmed']]
            ];
        }

        if ($capex->phase === 'estimate_completed') {
            $isActivePhase = false;

            return [
                'label' => 'Requestor Confirmation',
                'phase_status' => 'current',
                'users' => [['name' => 'Requestor', 'status' => 'current']]
            ];
        }

        if ($capex->phase === 'for_major_approval') {
            return [
                'label' => 'Requestor Confirmation',
                'phase_status' => 'confirmed',
                'users' => [['name' => 'Requestor', 'status' => 'confirmed']]
            ];
        }

        return [
            'label' => 'Requestor Confirmation',
            'phase_status' => 'pending',
            'users' => [['name' => 'Requestor', 'status' => 'pending']]
        ];
    }

    private function getPhaseStatus($users)
    {
        $statuses = collect($users)->pluck('status');

        if ($statuses->contains('returned')) return 'returned';
        if ($statuses->contains('rejected')) return 'rejected';
        if ($statuses->contains('current')) return 'current';
        if ($statuses->every(fn($s) => in_array($s, ['approved']))) return 'completed';

        return 'pending';
    }

    private function isEstimatorLevelRejected($history, $level, $capex, $groupKey)
    {
        return $history->contains(fn($h) =>
            $h->approver_set_name === $groupKey &&
            $capex->status === 'rejected' &&
            $h->estimation_approving_level == $level &&
            $h->status === 'rejected'
        );
    }

    private function mapStatus($context, $status)
    {
        return match ($context) {
            'estimator' => $status === 'completed' ? 'estimated' : $status,
            'estimator_approver' => $status === 'completed' ? 'approved' : $status,
            'major' => $status === 'completed' ? 'approved' : $status,
            default => $status
        };
    }
}