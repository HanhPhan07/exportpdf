<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Constants;

class TBLTALApplyTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTALApply');
        $this->setPrimaryKey('ID');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'DateSubmitted' => 'new'
                ]
            ]
        ]);
        $this->belongsTo('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => 'StaffID',
        ]);

    }

    public function validationDefault(Validator $validator)
    {
        $validator
            ->notEmpty('StaffID', 'Need enter StaffID')
            ->notEmpty('FromDate', 'Need enter FromDate')
            ->notEmpty('ToDate', 'Need enter ToDate')
            ->notEmpty('FromTime', 'Need enter FromTime')
            ->notEmpty('ToTime', 'Need enter ToTime')
            ->notEmpty('Total', 'Need enter Total')
            ->notEmpty('Reason', 'Need enter Reason');

        return $validator;
    }

    public function getLeaveAL($col_aproved, $staffs, $staffId, $position, $team)
    {
        $fchar = substr($staffId, 0, 1);
        $colId = Constants::SAL_COLUMNS[$fchar];
        $scol_approved = "{$colId}Approval";
        if (in_array($staffId, Constants::CEO_IDS)) { // login with ceo
            $where = [
                'TBLTALApply.StaffID IN' => $staffs,
                'TBLTALApply.BOApproval IS NULL',
            ];
        }
        elseif (in_array($team, Constants::BO_CHAR)) { // login with bo
//            $where = [
//                'OR' => [
//                    [ //self
//                        'TBLTALApply.StaffID IN' => $staffId,
//                        'TBLTALApply.BOApproval' => 0,
//                    ],
//                    [
//                        'TBLTALApply.ShuninApproval' => 1,
//                        'TBLTALApply.BOApproval IS NULL',
//                    ]
//                ]
//            ];
            $where = [
                'TBLTALApply.ShuninApproval' => 1,
                'TBLTALApply.BOApproval IS NULL',
            ];
        }
        elseif (in_array($position, Constants::SHUNIN_CHAR) !== false) {
//            $where = [
//                'OR' => [
//                    [ //self
//                        'TBLTALApply.StaffID IN' => $staffId,
//                        'OR' => [
//                            "TBLTALApply.{$col_aproved}" => 0,
//                            'TBLTALApply.ShuninApproval' => 0,
//                            'TBLTALApply.BOApproval' => 0,
//                        ]
//                    ],
//                    [
//                        'TBLTALApply.StaffID NOT IN' => $staffId,
//                        "TBLTALApply.SLeaderApproval" => 1,
//                        "TBLTALApply.MLeaderApproval" => 1,
//                        'OR' => [
//                            'TBLTALApply.ShuninApproval IS NULL',
//                            //'TBLTALApply.ShuninApproval' => 0,
//                            'TBLTALApply.BOApproval' => 0,
//                        ]
//                    ]
//                ]
//            ];
            $where = [
                'TBLTALApply.StaffID NOT IN' => $staffId,
                "TBLTALApply.SLeaderApproval" => 1,
                "TBLTALApply.MLeaderApproval" => 1,
                'TBLTALApply.ShuninApproval IS NULL',
            ];
        }
        elseif (in_array($position, Constants::LEAD_CHAR) !== false) {
//            $where = [
//                'OR' => [
//                    [ //self
//                        'TBLTALApply.StaffID IN' => $staffId,
//                        'OR' => [
//                            "TBLTALApply.{$col_aproved}" => 0,
//                            'TBLTALApply.ShuninApproval' => 0,
//                            'TBLTALApply.BOApproval' => 0,
//                        ]
//                    ],
//                    [
//                        'TBLTALApply.StaffID NOT IN' => $staffId,
//                        'TBLTALApply.StaffID IN' => $staffs,
//                        "TBLTALApply.{$col_aproved} IS NULL",
//                        'OR' => [
//                            "TBLTALApply.{$col_aproved} IS NULL",
//                            "TBLTALApply.{$scol_approved}" => 0,
//                            'TBLTALApply.ShuninApproval' => 0,
//                            'TBLTALApply.BOApproval' => 0,
//                        ]
//                    ]
//                ]
//            ];
            $where = [
                'TBLTALApply.StaffID NOT IN' => $staffId,
                'TBLTALApply.StaffID IN' => $staffs,
                "TBLTALApply.{$col_aproved} IS NULL",
                'OR' => [
                    "TBLTALApply.{$scol_approved} IS NULL",
                    "TBLTALApply.{$scol_approved}" => 1,
                ]
            ];
        }
        else { //staffs
            $where = [
                'TBLTALApply.StaffID IN' => $staffId,
                'OR' => [
                    'TBLTALApply.SLeaderApproval' => 0,
                    'TBLTALApply.MLeaderApproval' => 0,
                    'TBLTALApply.ShuninApproval' => 0,
                    'TBLTALApply.BOApproval' => 0,
                ]
            ];
        }

        return $this->find()
            ->contain(['TBLMStaff'])
            ->where($where)
            ->order([
                'TBLTALApply.DateSubmitted' => 'ASC'
            ]);
    }
    public function getLeaveALNew($col_aproved, $staffs, $staffId, $position, $team,$date=false,$sort = false)
    {
        $conds = [
                ['OR' =>[
                            "TBLTALApply.SLeaderApproval" => 1,
                            'TBLTALApply.SLeaderApproval IS NULL']
                ],
                ['OR' =>[
                            "TBLTALApply.MLeaderApproval" => 1,
                            'TBLTALApply.MLeaderApproval IS NULL']
                ],
                ['OR' =>[
                            "TBLTALApply.ShuninApproval" => 1,
                            'TBLTALApply.ShuninApproval IS NULL']
                ],
                ['OR' =>[
                            "TBLTALApply.BOApproval" => 1,
                            'TBLTALApply.BOApproval IS NULL']
                ]];
        $fchar = substr($staffId, 0, 1);
        $colId = Constants::SAL_COLUMNS[$fchar];
        $scol_approved = "{$colId}Approval";
        if (in_array($staffId, Constants::CEO_IDS)) { // login with ceo
            // $where = [
            //     'TBLTALApply.StaffID IN' => $staffs,
            // ];
            $where = $conds;
        }
        elseif (in_array($team, Constants::BO_CHAR)) { // login with bo
             $where = [
                'TBLTALApply.StaffID NOT IN' => $staffId,
                $conds
            ];
           // $where = $conds;
        }
        elseif (in_array($position, Constants::SHUNIN_CHAR) !== false) {
            $where = [
                'TBLTALApply.StaffID NOT IN' => $staffId,
                $conds

            ];
        }
        elseif (in_array($position, Constants::LEAD_CHAR) !== false) {
            $where = [
                'TBLTALApply.StaffID NOT IN' => $staffId,
                'TBLTALApply.StaffID IN' => $staffs,
                $conds
            ];
        }
        else { //staffs
            $where = [
                'TBLTALApply.StaffID IN' => $staffId,
                 $conds
            ];
        }
        $where[] = ($date)? ['AND' => ['DATE(TBLTALApply.FromDate) <=' => date('Y-m-d', strtotime($date)), 'DATE(TBLTALApply.ToDate) >=' => date('Y-m-d', strtotime($date))]] : [];
        return $this->find()
            ->contain(['TBLMStaff'])
            ->where($where)
            ->order($sort);
    }
    public function getAllALOfLeader($leader_list){
        $where = [
            'TBLTALApply.StaffID IN' => $leader_list,
            'TBLTALApply.BOApproval'    => 1,
            'TBLTALApply.FromDate >=' => date("Y-m-d", strtotime("-3 month", strtotime(date('Y-m-d'))))

        ];
        return $this->find()
            ->contain(['TBLMStaff'])
            ->select([
                'StaffID'  => 'TBLTALApply.StaffID',
                'FromDate' => 'TBLTALApply.FromDate',
                'ToDate'   => 'TBLTALApply.ToDate',
                'Total'    => 'TBLTALApply.Total',
                'FromTime' => 'TBLTALApply.FromTime',
                'ToTime'   => 'TBLTALApply.ToTime',
                'Type'     => 'TBLTALApply.Type',
                'StaffName' => 'TBLMStaff.StaffName',
                'Avatar'   => 'TBLMStaff.Avatar'
            ])
            ->where($where)
            ->order([
                'TBLTALApply.DateSubmitted' => 'ASC',
            ]);
    }
    public function getAlOfStaffList($staffIds,$Type = false){
        $where = [
            'TBLTALApply.StaffID IN' => $staffIds,
            'TBLTALApply.BOApproval'    => 1,
            'TBLTALApply.FromDate >=' => date("Y-m-d", strtotime("-3 month", strtotime(date('Y-m-d'))))

        ];
        if($Type){
            $where[] = [
                'TBLTALApply.Type' => $Type
            ];
        }
        return $this->find()
            ->contain(['TBLMStaff'])
            ->select([
                'StaffID'  => 'TBLTALApply.StaffID',
                'FromDate' => 'TBLTALApply.FromDate',
                'ToDate'   => 'TBLTALApply.ToDate',
                'Total'    => 'TBLTALApply.Total',
                'FromTime' => 'TBLTALApply.FromTime',
                'ToTime'   => 'TBLTALApply.ToTime',
                'Type'     => 'TBLTALApply.Type',
                'StaffName' => 'TBLMStaff.StaffName',
                'Avatar'   => 'TBLMStaff.Avatar'
            ])
            ->where($where)
            ->order([
                'TBLTALApply.DateSubmitted' => 'ASC',
            ]);
    }

    public function getStaffValidALByDate($staffID, $date) {
        return $this->find()
            ->select()
            ->where([
                'StaffID' => $staffID,
                'FromDate' => $date,
                'SLeaderApproval' => 1,
                'MLeaderApproval' => 1,
                'ShuninApproval' => 1,
                'BOApproval' => 1,
            ])
            ->first();
    }

    public function getValidALByMonth($staffID, $year, $month) {
        return $this->find()
            ->select()
            ->where([
                'StaffID' => $staffID,
                'FromDate LIKE' => $year."-".$month."%",
                'SLeaderApproval' => 1,
                'MLeaderApproval' => 1,
                'ShuninApproval' => 1,
                'BOApproval' => 1,
                'Type' => 1
            ]);
    }

    public function getValidDayOffByMonth($staffID, $year, $month) {
        return $this->find()
            ->select()
            ->where([
                'StaffID' => $staffID,
                'FromDate LIKE' => $year."-".$month."%",
                'SLeaderApproval' => 1,
                'MLeaderApproval' => 1,
                'ShuninApproval' => 1,
                'BOApproval' => 1
            ]);
    }


    public function getValidULByMonth($staffID, $year, $month) {
        return $this->find()
            ->select()
            ->where([
                'StaffID' => $staffID,
                'FromDate LIKE' => $year."-".$month."%",
                'SLeaderApproval' => 1,
                'MLeaderApproval' => 1,
                'ShuninApproval' => 1,
                'BOApproval' => 1,
                'Type' => 4
            ]);
    }

    public function getMaternity() {
        $sys_date = date('Y-m-d');
        return $this->find('list', [
            'keyField' => 'StaffID',
            'valueField' => 'StaffID',
        ])
            ->where([
                'ToDate >= ' => $sys_date,
                'SLeaderApproval' => 1,
                'MLeaderApproval' => 1,
                'ShuninApproval' => 1,
                'BOApproval' => 1,
                'Type' => 4,
                'Reason' => \App\Model\Entity\TBLTALApply::UL_REASON_TYPES[1]
            ])
            ->toArray();
    }

    public function getPeriodMaternity($staffID) {
        $sys_date = date('Y-m-d');
        $rst = $this->find()
            ->select()
            ->where([
                'StaffID' => $staffID,
                'SLeaderApproval' => 1,
                'MLeaderApproval' => 1,
                'ShuninApproval' => 1,
                'BOApproval' => 1,
                'Type' => 4,
                'Reason' => \App\Model\Entity\TBLTALApply::UL_REASON_TYPES[1]
            ]);
        $response = [];
        foreach ($rst as $each) {
            if (!isset($response['from'])) {
                $response['from'] = $each->FromDate;
            }
            $response['to'] = $each->ToDate;
        }
        return $response;
    }
}
