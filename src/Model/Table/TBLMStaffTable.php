<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMStaffTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('tblMStaff');
		$this->setPrimaryKey('StaffID');

		$this->hasOne('TBLMStaff2', [
			'className' => 'TBLMStaff2',
			'foreignKey' => false,
			'conditions' => ['TBLMStaff2.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'TBLMStaff2',
        ]);

        $this->hasOne('TBLTSelfEvaluation', [
			'className' => 'TBLTSelfEvaluation',
			'foreignKey' => false,
			'conditions' => ['TBLTSelfEvaluation.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'TBLTSelfEvaluation',
        ]);

        $this->hasMany('TBLTOtherEvaluation', [
			'className' => 'TBLTOtherEvaluation',
			'foreignKey' => false,
			'conditions' => ['TBLTOtherEvaluation.StaffID_From = TBLMStaff.StaffID'],
			'propertyName' => 'TBLTOtherEvaluation',
		]);

		$this->hasOne('TBLMStaff3', [
			'className' => 'TBLMStaff3',
			'foreignKey' => false,
			'conditions' => ['TBLMStaff3.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'TBLMStaff3',
		]);

		$this->hasMany('TBLTShift', [
			'className' => 'TBLTShift',
			'foreignKey' => false,
			'conditions' => ['TBLTShift.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'TBLTShift',
		]);

		$this->hasMany('TBLTALApply', [
			'className' => 'TBLTALApply',
			'foreignKey' => 'StaffID',
			'propertyName' => 'TBLTALApply',
		]);

		$this->hasMany('TBLTRemain', [
			'className' => 'TBLTRemain',
			'foreignKey' => false,
			'conditions' => ['TBLTRemain.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'TBLTRemain',
		]);

		$this->hasMany('TBLSaturdayWorking', [
			'className' => 'TBLSaturdayWorking',
			'foreignKey' => false,
			'conditions' => ['TBLSaturdayWorking.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'TBLSaturdayWorking',
		]);

		$this->hasMany('OTApply', [
			'className' => 'OTApply',
			'foreignKey' => false,
			'conditions' => ['OTApply.StaffID = TBLMStaff.StaffID'],
			'propertyName' => 'OTApply',
		]);

        $this->hasOne('TBLTag', [
            'className' => 'TBLTag',
            'foreignKey' => false,
            'conditions' => ['TBLTag.StaffID = TBLMStaff.StaffID'],
            'propertyName' => 'TBLTag',
        ]);
	}

	public function findAuth(\Cake\ORM\Query $query, array $options) {
		$query
			->select(['StaffID', 'Password', 'QuitJobDate']);
		// hide to check quick job date
		// ->where(['(QuitJobDate IS NULL)']);

		return $query;
	}

	public function getAllStaffNoQuitJob() {
		$lst = $this->find()
			->where(
				[
					'OR' => [
						'QuitJobDate IS NULL',
						'QuitJobDate >' => date("Y-m-d")
					],
				]
			)
			->order([
				'TBLMStaff.StaffID',
			]);
		return $lst->combine('StaffID', 'StaffID')->toArray();
	}

	public function getStaffIdsByDateQuitJob($date){
		$lst = $this->find()
			->where(
				[
					'OR' => [
						'QuitJobDate IS NULL',
						'QuitJobDate >' => $date
					],
				]
			)
			->order([
				'TBLMStaff.StaffID',
			]);
		return $lst->combine('StaffID', 'StaffID')->toArray();
	}

	public function getAllStaffNoQuitJobToLeader() {
		$lst = $this->find()
			->where(
				[
					'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
					],

				]
			)
			->order([
				'TBLMStaff.StaffID',
			]);
		return $lst->combine('StaffID', 'StaffID')->toArray();
	}

	public function getAllStaffNoQuitJobToLeaderCalendar() {
		$lst = $this->find()
			->where(
				[
					'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
					],

				]
			)
			->order([
				'TBLMStaff.StaffID',
			]);
		return $lst->combine('StaffID', 'StaffName')->toArray();
	}
	public function getQuitJob() {
		$lst = $this->find()
			->where(
				[
					'QuitJobDate IS NOT NULL',
				]
			)
			->order([
				'TBLMStaff.StaffID',
			]);
		return $lst->combine('StaffID', 'StaffID')->toArray();
	}

	public function getAllStaffLeaveByTeam($team) {
		$lst = $this->find()->contain([
			'TBLMStaff3',
			'TBLTALApply',

		])
			->where(['TBLMStaff3.Team' => $team, 'TBLMStaff3.Position' => 'Staff'])
			->order([
				'TBLMStaff.StaffID',
			]);
		return $lst;
	}

	public function getOvertimeAggregation($dateFrom, $dateTo, $team) {
		$result = $this->find()
			->select([
				'TBLMStaff.StaffID',
				'TBLMStaff.StaffName',
				'SumOvertime' => 'SUM(OTApply.Total) * COUNT(DISTINCT OTApply.ID) / COUNT(OTApply.ID)',
                'TeamCode' => 'SUBSTRING(TBLMStaff3.Team,1,3)',
                'ShiftWeek' => 'TBLTShift.ShiftWeek'
            ])
            ->leftJoinWith('TBLTShift', function ($q) use ($dateFrom, $dateTo) {
				return $q->where(['TBLTShift.Date >=' => $dateFrom, 'TBLTShift.Date <=' => $dateTo]);
			})
			->leftJoinWith('TBLMStaff3')
			->leftJoinWith('OTApply', function ($q) use ($dateFrom, $dateTo) {
				return $q->where([
					'DATE(OTApply.Date) >=' => $dateFrom, 'DATE(OTApply.Date) <=' => $dateTo,
                    'OTApply.LeaderApproved' => 1,
				]);
            })
            ->contain('TBLTALApply', function ($q) use ($dateFrom, $dateTo) {
				return $q->where([
                    'TBLTALApply.FromDate <=' => $dateFrom,
                    'TBLTALApply.ToDate >=' => $dateTo,
                    // 'TBLTALApply.ToDate >' => date("Y-m-15", strtotime($dateTo)),
                    'TBLTALApply.Type' => 4, // Type UL
                    'TBLTALApply.Reason' => \App\Model\Entity\TBLTALApply::UL_REASON_TYPES[1],
                    ['OR' =>[
                        "TBLTALApply.SLeaderApproval" => 1,
                        "TBLTALApply.ShuninApproval" => 1,
                        "TBLTALApply.MLeaderApproval" => 1,
                        "TBLTALApply.BOApproval" => 1,
                        ]
                    ],
				]);
			})
			->group("TBLMStaff.StaffID")
			->order(['SumOvertime' => 'DESC'])
			->where(
				[
					'SUBSTRING(TBLMStaff3.Team,1,3)' => $team,
					'OR' => ['DATE(QuitJobDate) >' => date("Y-m-01", strtotime($dateFrom)), 'QuitJobDate IS NULL'],
				]
            );
            // dd($result);
        return $result;
	}

	public function getUserData($staffID) {
		return $this->find('all')
			->contain(['TBLMStaff2', 'TBLMStaff3'])
			->where(["TBLMStaff.StaffID" => $staffID])
			->first();
	}

}
