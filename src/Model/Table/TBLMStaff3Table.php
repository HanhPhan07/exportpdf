<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Constants;

class TBLMStaff3Table extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('tblMStaff3');
		$this->setPrimaryKey('StaffID');

		$this->hasOne('TBLMStaff', [
			'className' => 'TBLMStaff',
			'foreignKey' => ['StaffID'],
			'bindingKey' => ['StaffID'],
			'propertyName' => 'TBLMStaff',
		]);
		$this->hasMany('TBLTScoreUser', [
			'className' => 'TBLTScoreUser',
			'foreignKey' => false,
			'conditions' => ['TBLTScoreUser.Team = TBLMStaff3.Team'],
			'propertyName' => 'TBLTScoreUser',
		]);
	}

	public function getAll()
    {
        $lst = $this->find()->contain([
            'TBLMStaff',
            'TBLMStaff.TBLMStaff2',
        ])
            ->where(
                [
                    'OR' => [
                        'TBLMStaff.QuitJobDate IS NULL',
                        'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
                    ],
                    'TBLMStaff3.Team <>' => 'Not yet',
                ]
            )
            ->order([
                'TBLMStaff3.Team',
                // 'TBLMStaff3.Position',
                'TBLMStaff3.Part' => "DESC",
                'TBLMStaff3.StaffID',
            ]);
        $rst = [];
        foreach ($lst as $each) {
            $position = $each->Position == "Shunin" ? "Leader" : $each->Position;
            $position = $position == "Subleader" ? "Staff" : $position;
            $rst[$each->Team][$position][$each->Part][] = $each->TBLMStaff;
        }
        return $rst;
	}
	
	// public function getHR() {
	// 	$lst = $this->find()->contain([
    //         'TBLMStaff',
    //         'TBLMStaff.TBLMStaff2',
    //     ])
    //         ->where(
    //             [
    //                 'OR' => [
    //                     'TBLMStaff.QuitJobDate IS NULL',
    //                     'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
    //                 ],
    //                 'TBLMStaff3.Team <>' => 'Not yet',
    //             ]
    //         )
    //         ->order([
    //             'TBLMStaff3.Team',
    //             // 'TBLMStaff3.Position',
    //             'TBLMStaff3.Part' => "DESC",
    //             'TBLMStaff3.StaffID',
    //         ]);
    //     $rst = [];
	// }

	public function getLeader() {
		$lst = $this->find()->contain([
			'TBLMStaff',
		])
			->where([
				'TBLMStaff.QuitJobDate IS NULL',
				'TBLMStaff3.Position' => ['Leader', 'Shunin'],
			]);
		$rst = [];
		foreach ($lst as $each) {
			$f_char = substr($each->StaffID, 0, 1);
			$f_team = substr($each->Team, -1);
			if ($each->TBLMStaff) {
				$rst[$f_team][$f_char] = $each->TBLMStaff;
			}
		}
		return $rst;
	}
	public function getAllLeaderShunin() {
		$where = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Position IN' => ['Leader', 'Shunin'],
		];
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}
	public function getTeamOfStaff($staffID) {
		return $this->find()
			->where(['StaffID' => $staffID])
			->first();
	}

	public function getTeams() {
		$lst = $this->find('list', [
			'keyField' => 'TeamCode',
			'valueField' => function ($row) {return $row->Team;},
		])
			->where([
				'TBLMStaff3.Team NOT IN ' => ['Not yet'],
			])
			->group([
				'TBLMStaff3.Team',
			]);
		return $lst->toArray();
	}

	public function countStaff($team) {
		$query = $this->find();
		return $query->contain([
			'TBLMStaff',
		])
			->select([
				'position' => 'SUBSTRING(TBLMStaff3.StaffID, 1, 1)',
				'count' => $query->func()->count('*'),
			])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Team NOT IN ' => ['Not yet', '3ka', 'BO'],
				'TeamCode' => $team,
			])
			->group([
				'SUBSTRING(TBLMStaff3.StaffID, 1, 1)',
			]);
	}

	public function countStaffFull($team) {
		$query = $this->find();
		return $query->contain([
			'TBLMStaff',
		])
			->select([
				'position' => 'SUBSTRING(TBLMStaff3.StaffID, 1, 1)',
				'count' => $query->func()->count('*'),
			])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.TeamCode' => $team,
			])
			->group([
				'SUBSTRING(TBLMStaff3.StaffID, 1, 1)',
			])
		;
	}

	public function getStaffOfTeam($team) {
		return $this->find()->contain([
			'TBLMStaff',
			'TBLMStaff.TBLMStaff2',
		])
			->select(['TBLMStaff3.StaffID'])
			->where([
				'TBLMStaff.QuitJobDate IS NULL',
				'TBLMStaff3.Team NOT IN ' => ['Not yet', '3ka', 'BO'],
				'TBLMStaff3.TeamCode' => $team,
			])
			->order([
				'TBLMStaff3.Team',
				'TBLMStaff3.Position',
				'TBLMStaff3.StaffID',
			]);
	}

	public function getStaffOfTeamFull($team) {
		return $this->find()->contain([
			'TBLMStaff',
			'TBLMStaff.TBLMStaff2',
		])
			->select(['TBLMStaff3.StaffID'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Team <>' => 'Not yet',
				'TBLMStaff3.TeamCode' => $team,
			])
			->order([
				'TBLMStaff3.Team',
				'TBLMStaff3.Position',
				'TBLMStaff3.StaffID',
			]);
	}

	public function getStaffForInsertShift() {
		return $this->find()
			->contain([
				'TBLMStaff',
			])
			->select([
				'TBLMStaff3.StaffID',
				'TBLMStaff.QuitJobDate',
				'Team' => '((CASE WHEN TBLMStaff3.Team <> "" THEN TBLMStaff3.Team ELSE TBLMStaff3.Position END))',
				'TeamCode' => '((CASE WHEN TBLMStaff3.Team <> "" THEN TBLMStaff3.Team ELSE TBLMStaff3.Position END))'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Team <>' => 'Not yet',
				'TBLMStaff3.Team IS NOT NULL',
			])->toArray();
	}

	public function getTeamForInsertShiftMiddleware($team) {
		if (strpos($team, 'H') !== false) {
			return $this->getStaffByCode($team);
		} elseif ($team == "Trainee") {
			return $this->getTrainee();
		} else {
			return $this->getStaffOfTeamForInsertShift($team);
		}
	}

	public function getStaffByCode($code) {
		return $this->find()
			->contain([
				'TBLMStaff',
			])
			->select(['TBLMStaff3.StaffID', 'TBLMStaff.QuitJobDate', 'TBLMStaff3.Team', 'TeamCode' => 'TBLMStaff3.Team'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.StaffID' => $code,
			])->toArray();
	}

	public function getTrainee() {
		return $this->find()
			->contain([
				'TBLMStaff',
			])
			->select(['TBLMStaff3.StaffID', 'TBLMStaff.QuitJobDate', 'Team' => 'TBLMStaff3.Position', 'TeamCode' => 'TBLMStaff3.Position'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Position' => "Trainee",
			])->toArray();
	}

	public function getStaffOfTeamForInsertShift($team) {
		return $this->find()
			->contain([
				'TBLMStaff',
			])
			->select(['TBLMStaff3.StaffID', 'TBLMStaff.QuitJobDate', 'TBLMStaff3.Team', 'TeamCode' => 'TBLMStaff3.Team'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Team <>' => 'Not yet',
				'TBLMStaff3.Team IS NOT NULL',
				'TBLMStaff3.Team' => $team,
			])->toArray();
	}

	public function getAllStaff() {
		return $this->find()
			->contain([
				'TBLMStaff',
			])
			->select(['TBLMStaff3.StaffID', 'TBLMStaff3.Team', 'TeamCode' => 'TBLMStaff3.Team'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Team <>' => 'Not yet',
				'TBLMStaff3.Team IS NOT NULL',
				'TBLMStaff3.Team <>' => '',
			])->toArray();
	}

	public function getStaffOfTeamFullBySub($team, $group, $position) {
		return $this->find()->contain([
			'TBLMStaff',
			'TBLMStaff.TBLMStaff2',
		])
			->select(['TBLMStaff3.StaffID'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
				'TBLMStaff3.Team <>' => 'Not yet',
				//'TBLMStaff3.Position NOT IN ' => ['Leader', 'Shunin'],
				'TBLMStaff3.Team LIKE ' => $team . '%',
				'SUBSTR(TBLMStaff3.Team, -1) <>' => $group,
				'SUBSTR(TBLMStaff3.StaffID, 1, 1) = ' => $position,
			])
			->order([
				//  'TBLMStaff3.Team',
				//  'TBLMStaff3.Position',
				'TBLMStaff3.StaffID',
			]);
	}

	public function getLeaderOfTeamFullBySub($team, $group, $position) {
		return $this->find()->contain([
			'TBLMStaff',
			'TBLMStaff.TBLMStaff2',
		])
			->select(['TBLMStaff3.StaffID'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-01"),
				],
				'TBLMStaff3.Team <>' => 'Not yet',
				'TBLMStaff3.Position IN ' => ['Leader', 'Shunin'],
				'TBLMStaff3.Team LIKE ' => $team . '%',
				'SUBSTR(TBLMStaff3.Team, -1) <>' => $group,
				'SUBSTR(TBLMStaff3.StaffID, 1, 1) = ' => $position,
			])
			->order([
				'TBLMStaff3.Team',
				'TBLMStaff3.Position',
				'TBLMStaff3.StaffID',
			]);
	}

	public function getStaffByIDAndTeam($id, $team, $date) {
		$limitQuitJob = date("Y-m-01", strtotime($date . "/01"));
		return $this->find()->contain([
			'TBLMStaff',
		])
			->select(['StaffName' => 'TBLMStaff.StaffName', 'StaffID' => 'TBLMStaff3.StaffID'])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => $limitQuitJob,
				],
				'TBLMStaff.StaffID LIKE ' => $id . '%',
				'TBLMStaff3.FromTeam' => $team,
			])
			->order([
				'TBLMStaff3.StaffID',
			]);
	}

	//AL form
	public function getStaffOf($team, $conds = [], $current_date = false) {
		$bigTeam = substr($team, 0, 3);
		$excepTeams = '3ka';
		$where = [
			// 'OR' => [
			//     'TBLMStaff.QuitJobDate IS NULL',
			//     'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-01"),
			// ],
			'OR' => [
				"SUBSTRING(TBLMStaff3.Team, 1, 3) = '{$bigTeam}'",
				"SUBSTRING(TBLMStaff3.Team, 1, 3) = '{$excepTeams}'",
			],
		] + $conds;
		if ($current_date !== false) {
			$where[] = [

				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(QuitJobDate) >' => date("Y-m-01", strtotime($current_date)),
				],
			];
		}
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}

	public function getLeaderOf($team, $conds = []) {
		$bigTeam = substr($team, 0, 3);
		$where = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Position IN' => Constants::LEAD_CHAR,
			'SUBSTRING(TBLMStaff3.Team, 1, 3) = ' => $bigTeam,
		] + $conds;
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}

	public function getShunin($conds = []) {
		$where = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Position IN' => Constants::SHUNIN_CHAR,
		] + $conds;
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}

	public function alColumns($staffId, $shunin = false) {
		$fchar = substr($staffId, 0, 1);
		if (strtolower($shunin) == 'shunin') {
			$fchar = 'Shunin';
		}

		$colId = Constants::AL_COLUMNS[$fchar];
		return [
			'id' => "{$colId}ID",
			'name' => "{$colId}Name",
			'approval' => "{$colId}Approval",
			'reason' => "{$colId}ReasonReject",
		];
	}
	public function getAllLeader($staffId, $conds = []) {
		$where = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Position IN' => Constants::LEAD_CHAR,
			'TBLMStaff3.StaffID <>' => $staffId,
		] + $conds;
		return $this->find('list', [
			'StaffID' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}
	public function getBO($conds = []) {
		$where = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Team IN ' => Constants::BO_CHAR,
			'TBLMStaff.StaffID IS NOT NULL',
		] + $conds;
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}
	public function getLeaderByTeam($team) {
		$where = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Position IN' => Constants::LEAD_CHAR,
			'TBLMStaff3.Team' => $team,
		];
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}
	public function getLeaderByTeamA($team, $staffID) {
		$where[] = (strpos($staffID, 'J') === false) ? ['TBLMStaff.StaffID LIKE' => "A%"] : ['TBLMStaff.StaffID LIKE' => "J%"];
		$where[] = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Position IN' => Constants::LEAD_CHAR,
			'TBLMStaff3.Team' => $team,

		];
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->contain(['TBLMStaff'])
			->where($where)
			->toArray();
	}
	public function getStaffOfOnlyAOrJTeamByKey($team, $staffID) {

		$where[] = (strpos($staffID, 'J') === false) ? ['TBLMStaff.StaffID LIKE' => "A%"] : ['TBLMStaff.StaffID LIKE' => "J%"];
		$where[] = [
			'TBLMStaff.QuitJobDate IS NULL',
			'TBLMStaff3.Team' => $team];
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->where($where)
			->contain(['TBLMStaff'])
			->order([
				'TBLMStaff3.StaffID',
			])
			->toArray();
	}
	public function getStaffOfAllAJByTeamCode($team, $staffID) {
		$where[] = [
			//'TBLMStaff.QuitJobDate IS NULL',
			'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
				],
			'TBLMStaff3.Team' => $team];
		return $this->find('list', [
			'keyField' => 'TBLMStaff.StaffID',
			'valueField' => 'TBLMStaff.StaffID',
		])
			->where($where)
			->contain(['TBLMStaff'])
			->order([
				'TBLMStaff3.StaffID',
			])
			->toArray();
	}

	public function countStaffOfTeam($f_char, $t_char, $wdate) {
		return $this->find()->contain([
			'TBLMStaff',
		])
			->where([
				'OR' => [
					'TBLMStaff.QuitJobDate IS NULL',
					'DATE(TBLMStaff.QuitJobDate) >' => $wdate,
				],
				'SUBSTR(TBLMStaff3.StaffID, 1, 1) =' => $f_char,
				'SUBSTR(TBLMStaff3.Team, 1, 3) =' => $t_char,
			])
			->count();
    }
    
    // Get IT Staff - 20200709 - Baonguyen
    public function fetchITStaffsIds() {
        // Update condition, still in IT team event already quitJob - 20200804 - Baonguyen
        $lst = $this->find('list')
        ->select(['TBLMStaff3.StaffID'])
        ->contain([
			'TBLMStaff',
		])->where([
            'TBLMStaff3.ITTeam' => true,
        ]);
		return $lst;
	}

}
