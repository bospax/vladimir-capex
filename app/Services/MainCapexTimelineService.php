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

        // isolate per phase + per user
        $history = $rawHistory
            ->groupBy(fn($h) => $h->approver_set_name . '_' . $h->approver_id)
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
                $users,
                $isActivePhase,
                $level
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
    |--------------------------------------------------------------------------
    | ESTIMATOR LEVEL
    |--------------------------------------------------------------------------
    */

    private function buildEstimatorLevel(
		$groupKey,
		$label,
		$approvers,
		$history,
		$users,
		&$isActivePhase,
		$level
	) {
		$list = $approvers->get($groupKey, collect());
		$usersArr = [];

		// STEP 1: FIND ACTOR (ANYONE WHO ALREADY ACTED IN THIS GROUP)
		$actor = null;
		$actorHistory = null;

		foreach ($list as $item) {
			$key = $groupKey . '_' . $item->user_id;

			if (isset($history[$key])) {
				$actor = $item;
				$actorHistory = $history[$key];
				break; // only one should exist
			}
		}

		foreach ($list as $item) {

			if (!$isActivePhase) {
				$status = 'pending';

			} else {

				// CASE 1: SOMEONE ALREADY ACTED
				if ($actor) {

					if ($item->user_id == $actor->user_id) {

						if ($actorHistory->status === 'returned') {
							$status = 'returned';
						} elseif ($actorHistory->status === 'rejected') {
							$status = 'rejected';
						} else {
							$status = 'completed'; // estimated
						}

					} else {
						// THIS IS YOUR MISSING LOGIC
						$status = 'N/A';
					}

				} else {
					// 🔥 CASE 2: NO ONE HAS ACTED YET
					$status = 'current';
				}
			}

			$usersArr[] = [
				'name' => $users[$item->user_id]->firstname ?? 'N/A',
				'status' => $this->mapStatus('estimator', $status)
			];
		}

		// 🔥 PHASE STATUS LOGIC
		if (!$isActivePhase) {
			$phaseStatus = 'pending';
		} elseif ($actor) {
			if ($actorHistory->status === 'returned') {
				$phaseStatus = 'returned';
			} elseif ($actorHistory->status === 'rejected') {
				$phaseStatus = 'rejected';
			} else {
				$phaseStatus = 'completed';
			}
		} else {
			$phaseStatus = 'current';
		}

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
    |--------------------------------------------------------------------------
    | ESTIMATOR APPROVER
    |--------------------------------------------------------------------------
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
                            : ($latest->status === 'returned' ? 'returned' : 'completed');
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

        $statuses = collect($usersArr)->pluck('status');

        if (!$isActivePhase) {
            $phaseStatus = 'pending';
        } elseif ($statuses->contains('returned')) {
            $phaseStatus = 'returned';
        } elseif ($statuses->contains('rejected')) {
            $phaseStatus = 'rejected';
        } elseif ($statuses->contains('approved')) {
            $phaseStatus = 'completed';
        } else {
            $phaseStatus = 'current';
        }

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
    |--------------------------------------------------------------------------
    | SEQUENTIAL PHASE (FIRST + MAJOR)
    |--------------------------------------------------------------------------
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

        foreach ($list as $item) {

            $key = $groupKey . '_' . $item->user_id;
            $latest = $history[$key] ?? null;

            if (!$isActivePhase) {
                $status = $latest ? $latest->status : 'pending';

            } else {

                if ($latest) {

                    if ($latest->status === 'returned') {
                        $status = 'returned';

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

        $phaseStatus = $this->getPhaseStatus($usersArr);

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