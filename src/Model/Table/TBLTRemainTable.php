<?php

namespace App\Model\Table;

use App\Model\Entity\TBLTALApply;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class TBLTRemainTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('tbtTRemain');
		$this->setPrimaryKey('ID');
	}

	public function validationDefault(Validator $validator) {
		$validator
			->notEmpty('StaffID', 'Need enter StaffID');

		return $validator;
	}
	public function getRemain($staffID) {
        if ($staffID == "H0001" || $staffID == "H0002") {
            return 0;
        }

        $isBO = strpos($staffID, 'H') !== false;

		$remain = $this->find()
			->select('Remain')
			->where(['StaffID' => $staffID, 'Date' => date('Y-m-01')])
			->first()->Remain;

		if ($isBO) {
            $alDay = TableRegistry::get('TBLTALApply')
                ->find()
                ->where([
                    'TBLTALApply.StaffID' => $staffID,
                    'TBLTALApply.FromDate >=' => date('Y-m-01'),
                    'TBLTALApply.FromDate <=' => date('Y-m-t'),
                    'TBLTALApply.ShuninApproval <>' => 0,
                    'TBLTALApply.BOApproval <>' => 0,
                    'TBLTALApply.BOApproval IS NOT NULL',
                ])->all();
        }
		else {
            $alDay = TableRegistry::get('TBLTALApply')
                ->find()
                ->where([
                    'TBLTALApply.StaffID' => $staffID,
                    'TBLTALApply.FromDate >=' => date('Y-m-01'),
                    'TBLTALApply.FromDate <=' => date('Y-m-t'),
                    'TBLTALApply.SLeaderApproval <>' => 0,
                    'TBLTALApply.MLeaderApproval <>' => 0,
                    'TBLTALApply.ShuninApproval <>' => 0,
                    'TBLTALApply.BOApproval <>' => 0,
                    'TBLTALApply.BOApproval IS NOT NULL',
                ])->all();
        }
		$aplicationLeaveDay = [];
		foreach ($alDay as $ald) {
			for ($i = 1; $i <= 31; $i++) {
				if ($ald->FromDate &&
					$ald->ToDate &&
					intval(date("d", strtotime($ald->FromDate))) <= $i &&
					intval(date("d", strtotime($ald->ToDate))) >= $i) {
					if ($ald->Type && isset($ald->Type)) {
						$aplicationLeaveDay[] = ['type' => $ald->Type, 'total' => $ald->Total];
						$totalApply = $ald->Total;
					}
				}
			}
		}
		// dd($aplicationLeaveDay);
		$totalRemainMinus = 0;
		foreach ($aplicationLeaveDay as $key => $value) {
			$types = TBLTALApply::TYPES;
			if ($value && isset($types[$value['type']])) {
				if (intval($value['type']) != 4) {
					$total = $value['total'] == 0.5 ? 0.5 : 1;
					$totalAl[$key] = isset($totalAl[$key]) ? $totalAl[$key] + $total : $total;
				}
				if (intval($value['type']) == 1) {
					$totalRemainMinus += $total;
				}
			}
		}

		$staff = TableRegistry::get('TBLMStaff')
			->find()
			->where([
				'TBLMStaff.StaffID' => $staffID,
			])->first();

        $chkDateTrial = date('Y-m-15');
        $chkTrial = (!empty($staff['TrialEndDate']) && date("Y-m-d", strtotime($staff['TrialEndDate'])) > $chkDateTrial);
		$chkQuit = (!empty($staff['QuitJobDate']) && date("j", strtotime($staff['QuitJobDate'])) < 17 && date("n", strtotime($staff['QuitJobDate'])) == date("n"));
		$addRemain = ($chkQuit || $chkTrial) ? 0 : 1;

        $monthAfter = date("Y-m-01", strtotime(date('Y-m-01') . " + 1 month"));

        if (date("m", strtotime($monthAfter)) == '1') {
            $remain = $remain >= 5 ? 5 : $remain;
        }

        //if ($remain < 0) $remain = 0;

		$totalRemain = $remain + $addRemain - $totalRemainMinus;

        if ($staff['StaffID'] == "H0001" || $staff['StaffID'] == "H0002") {
            $totalRemain = $addRemain = 0;
        }

		TableRegistry::get('TBLTRemain')->deleteAll(['Date =' => $monthAfter, 'StaffID' => $staffID]);
		$tableRemain = TableRegistry::get('TBLTRemain');
		$entity = $tableRemain->newEntity();

		$entity->Remain = $totalRemain;
		$entity->StaffID = $staffID;
        $entity->LeaveDay = $totalRemainMinus;
		$entity->Date = $monthAfter;
		$tableRemain->save($entity);

		return $totalRemain;
	}
}
