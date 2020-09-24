<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Constants;
class TBLTShiftChangeTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTShiftChange');
        $this->setPrimaryKey('ID');

        $this->hasOne('TBLMStaff_From', [
            'className' =>'TBLMStaff',
            'foreignKey' => false,
            'conditions' => ['TBLMStaff_From.StaffID = TBLTShiftChange.StaffID_From'],
            'propertyName' => 'TBLMStaff_From'
        ]);

        $this->hasOne('TBLMStaff_To', [
            'className' =>'TBLMStaff',
            'foreignKey' => false,
            'conditions' => ['TBLMStaff_To.StaffID = TBLTShiftChange.StaffID_To'],
            'propertyName' => 'TBLMStaff_To'
        ]);
    }

    public function getShiftChange($staffs) {
        return $this->find()->contain([
            'TBLMStaff_From',
            'TBLMStaff_To',
        ])
            ->where(['OR' => [
                'TBLTShiftChange.StaffID_From IN' => $staffs,
                'TBLTShiftChange.StaffID_To IN' => $staffs
                ]
            ])
            ->order([
                'TBLTShiftChange.StaffID_From'
            ]);
    }
    public function getShiftChangeOfStaff($StaffID) {
        return $this->find()->contain([
            'TBLMStaff_From',
            'TBLMStaff_To',
        ])
            ->where(
                [   
                    'TBLTShiftChange.StaffID_To ' => $StaffID,
                    'OR' => [
                        'TBLTShiftChange.StaffName IS NULL',
                        'TBLTShiftChange.StaffName' => ''
                    ]                               
            ])
            ->order([
                'TBLTShiftChange.DateFrom'
            ]);
    }
    public function getAllShiftChangeOfStaff($StaffID, $date=false) {
        if($date){
            $cond = ['TBLTShiftChange.DateFrom >=' => date('Y-m-01', strtotime($date)),
                    'TBLTShiftChange.DateTo <=' => date('Y-m-t', strtotime($date))];
        }
        return $this->find()->contain([
            'TBLMStaff_From',
            'TBLMStaff_To',
        ])
            ->where(
                [                      
                    'OR' => [
                        'TBLTShiftChange.StaffID_To ' => $StaffID,
                        'TBLTShiftChange.StaffID_From ' => $StaffID,
                    ]                           
                ]+$cond )
            ->order([
                'TBLTShiftChange.DateSubmitted'
            ]);
    }
    public function getShiftChangeFrom($staffs) {
        return $this->find()->contain([
            'TBLMStaff_From',
        ])
            ->where([
                'TBLTShiftChange.StaffID_From IN' => $staffs
            ])
            ->order([
                'TBLTShiftChange.StaffID_From'
            ]);
    }

    public function getShiftChangeTo($staffs) {
        return $this->find()->contain([
            'TBLMStaff_To',
        ])
            ->where([
                'TBLTShiftChange.StaffID_To IN' => $staffs
            ])
            ->order([
                'TBLTShiftChange.StaffID_To'
            ]);
    }
    public function getShiftChangeByLeaderAndBO($staffs_A, $position, $team,$staffId,$date)
    {
        if($date){
            $cond = ['TBLTShiftChange.DateFrom >=' => date('Y-m-01', strtotime($date)),
                    'TBLTShiftChange.DateTo <=' => date('Y-m-t', strtotime($date))];
        }
        $where = [];
        if (in_array($team, Constants::BO_CHAR)) { // login with bo
            // $where = [
            //         'OR' =>[
                               
            //                     [
            //                         'TBLTShiftChange.StaffID_To' => '',
            //                         'TBLTShiftChange.FromLeaderApproval' => 1,
            //                     ],
            //                     [
            //                         'TBLTShiftChange.FromLeaderApproval' => 1,
            //                         'TBLTShiftChange.ToLeaderApproval' => 1,
            //                     ],

            //             ],
                
            //         'TBLTShiftChange.BOApproval IS NULL',
            // ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.StaffApproval' => 1,
                        'TBLTShiftChange.StaffApproval IS NULL'
                ]
            ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.FromLeaderApproval' => 1,
                        'TBLTShiftChange.FromLeaderApproval IS NULL'
                ]
            ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.ToLeaderApproval' => 1,
                        'TBLTShiftChange.ToLeaderApproval IS NULL'
                ]
            ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.BOApproval' => 1,
                        'TBLTShiftChange.BOApproval IS NULL'
                ]
            ];
                
        }
        elseif (in_array($position, Constants::LEAD_CHAR) !== false || $position == 'Shunin') {
            $fchar = substr($staffId, 0, 1);
            $colId = Constants::AL_COLUMNS[$fchar];
            $scol_approved = "{$colId}Approval";
            $where = [
                'OR' =>[
                        [
                            'TBLTShiftChange.StaffID_From IN' => $staffs_A,
                         //   "TBLTShiftChange.FromLeaderApproval IS NULL",
                         //   'TBLTShiftChange.StaffID_To' => ''
                        ],
                        [
                            'TBLTShiftChange.StaffID_From IN' => $staffs_A,
                          //  'TBLTShiftChange.StaffID_To IS NOT NULL',
                          //  'TBLTShiftChange.StaffApproval' => 1,
                          //  'TBLTShiftChange.FromLeaderApproval IS NULL'
                        ],
                        [
                            'TBLTShiftChange.StaffID_To IN' => $staffs_A,
                          //  'TBLTShiftChange.StaffID_To IS NOT NULL',
                          //  'TBLTShiftChange.StaffApproval' => 1,
                          //  'TBLTShiftChange.ToLeaderApproval IS NULL'
                        ],
                    ]
                ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.StaffApproval' => 1,
                        'TBLTShiftChange.StaffApproval IS NULL'
                ]
            ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.FromLeaderApproval' => 1,
                        'TBLTShiftChange.FromLeaderApproval IS NULL'
                ]
            ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.ToLeaderApproval' => 1,
                        'TBLTShiftChange.ToLeaderApproval IS NULL'
                ]
            ];
            $where[] = [
                'OR' =>[
                       'TBLTShiftChange.BOApproval' => 1,
                        'TBLTShiftChange.BOApproval IS NULL'
                ]
            ];
        }       

        return $this->find()
            ->contain([
                'TBLMStaff_From',
                'TBLMStaff_To',
            ])
            ->where($where+$cond)
            ->order([
                'TBLTShiftChange.DateSubmitted' => 'ASC'
            ]);
    }
}
