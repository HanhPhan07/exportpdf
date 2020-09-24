<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Constants;

/**
 * OTApply Model
 *
 * @method \App\Model\Entity\OTApply get($primaryKey, $options = [])
 * @method \App\Model\Entity\OTApply newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\OTApply[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\OTApply|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OTApply saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\OTApply patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\OTApply[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\OTApply findOrCreate($search, callable $callback = null, $options = [])
 */
class OTApplyTable extends Table
{
    const ROLE_TYPES = [
        "A" => 'Seikaku',
        "J" => 'Madoguchi',
        "Shunin" => 'Shunin',
        'CEO' => 'CEO'
    ];

    const TEAM_TYPES = [
        '1ka' => '1ka',
        '2ka' => '2ka',
        '3ka' => '3ka'
    ];

    const CEO_IDS = [
        'H0001' => 'H0001',
        'H0002' => 'H0002'
    ];

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTOTApply');
        $this->setPrimaryKey('ID');

        $this->belongsTo('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => 'StaffID',
        ]);

        $this->belongsTo('TBLMStaff3', [
            'className' => 'TBLMStaff3',
            'foreignKey' => 'StaffID',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->scalar('StaffID')
            ->maxLength('StaffID', 5)
            ->requirePresence('StaffID', 'create')
            ->notEmptyString('StaffID');

        $validator
            ->date('Date')
            ->requirePresence('Date', 'create')
            ->notEmptyDate('Date');

        $validator
            ->integer('Shift')
            ->requirePresence('Shift', 'create')
            ->notEmptyString('Shift');

        $validator
            ->scalar('FromTime')
            ->notEmptyString('FromTime');

        $validator
            ->scalar('ToTime')
            ->notEmptyString('ToTime');

        $validator
            ->integer('Total')
            ->notEmptyString('Total');

        $validator
            ->scalar('LeaderID')
            ->maxLength('LeaderID', 5);

        // $validator
        //     ->scalar('LeaderName')
        //     ->maxLength('LeaderName', 20);

        $validator
            ->dateTime('DateApproved');

        return $validator;
    }

    public function getUnApproveOTStaff($userData = false){
        $conds[] = [
            'OTApply.LeaderID' => "",
            // 'OTApply.LeaderName' => "",
            'TBLMStaff3.StaffID !=' => $userData['StaffID']
        ];


        if (!@$this::CEO_IDS[$userData['StaffID']]) {
            if ($teamTypes = $this->_generateTeamCondition($userData)) $conds[] = $teamTypes;
        }

        if ($roleConds = $this->_generateRoleCondition($userData)) $conds[] = $roleConds;

        $lst = $this->find()
            ->select([
                'FromTime'      => 'OTApply.FromTime',
                'ToTime'        => 'OTApply.ToTime',
                'Date'          => 'OTApply.Date',
                'Total'         => 'OTApply.Total',
                'Shift'         => 'OTApply.Shift',
                'ID'            => 'OTApply.ID',
                'StaffID'       => 'TBLMStaff.StaffID',
                'StaffName'     => 'TBLMStaff.StaffName'
            ])
            ->join([
                'TBLMStaff' => [
                    'table' => 'tblMStaff',
                    'type' => 'LEFT',
                    'conditions' => 'OTApply.StaffID = TBLMStaff.StaffID',
                ],
                'TBLMStaff3' => [
                    'table' => 'tblMStaff3',
                    'type' => 'LEFT',
                    'conditions' => 'TBLMStaff3.StaffID = TBLMStaff.StaffID',
                ],
            ])
            ->where($conds)
            ->order([
                'OTApply.Date'
            ])
            ;
            // echo "<pre>"; var_dump($conds);echo "</pre>";die;
        return $lst;
    }

    private function _generateTeamCondition($userData = false) {
        $currentTeam = $userData['Team'];
        $conds = [
            'OR' => [
                ['TBLMStaff3.Team' => $currentTeam],
                [
                    'AND' => [
                        'TBLMStaff3.Team LIKE' => $this->_getTeamType($this::TEAM_TYPES['3ka'])."%",
                        'TBLMStaff3.Position' => "Leader"
                    ]
                ]
            ]
        ];
        return @$conds;
    }

    private function _generateRoleCondition($userData = false) {
        $currentRole = $this->_getRoleType($userData);
        if (!$currentRole) return false;
        switch ($currentRole) {
            case $this::ROLE_TYPES['A']:
                return [
                    'OR' => [
                        'TBLMStaff.StaffID LIKE' => 'A%',
                        'TBLMStaff3.Position' => 'Shunin'
                    ]
                ];
            case $this::ROLE_TYPES['J']:
                return [
                    'OR' => [
                        'TBLMStaff.StaffID LIKE' => 'J%',
                        'TBLMStaff3.Position' => 'Shunin'
                    ]
                ];
            case $this::ROLE_TYPES['Shunin']:
                $roleType = $this->_getRoleTypeByStaffID($userData['StaffID']);
                if ($roleType == $this::ROLE_TYPES['A']) $keyword = "A%";
                if ($roleType == $this::ROLE_TYPES['J']) $keyword = "J%";
                return [
                    'OR' => [
                        'TBLMStaff.StaffID LIKE' => @$keyword,
                        'TBLMStaff3.Position' => 'Leader'
                    ]
                ];
            case $this::ROLE_TYPES['CEO']:
                return ['TBLMStaff.StaffID LIKE' => 'H%'];
            default;
        }
    }

    private function _getRoleType($userData) {
        if (($userData['Position'] == $this::ROLE_TYPES['CEO']) || ($userData['Position'] == $this::ROLE_TYPES['Shunin'])) return $this::ROLE_TYPES[$userData['Position']];
        if ($userData['Position'] == 'Leader') return $this->_getRoleTypeByStaffID($userData['StaffID']);

        return false;
    }

    private function _getRoleTypeByStaffID($staffID) {
        $roleCode = substr($staffID, 0, 1);
        return @$this::ROLE_TYPES[$roleCode] ? : false;
    }

    private function _getTeamType($currentTeam) {
        $teamCode = substr($currentTeam, 0, 3);
        return $this::TEAM_TYPES[$teamCode] ? :false;
    }

    public function updateOTRequestStatus($position, $list_save, $leaderID){
        $conn = ConnectionManager::get('default');
            try {
                $conn->begin();
                $datas = [];
                $rst = true;
                foreach ($list_save as $key => $value) {
                    $OTApply = $this->find()
                    ->where(['ID' => $key])
                    ->first();
                    $OTApply['LeaderID'] = $leaderID;
                    // $OTApply['LeaderName'] = TableRegistry::get('TBLMStaff')->find()->where(['StaffID' => $leaderID])->first()['StaffName'];
                    $OTApply['DateApproved'] = date('Y-m-d H:i:s');
                    $OTApply['ReasonReject'] = $value['reason'];
                    $datas[] = $OTApply;
                }

                $rst = $this->saveMany($datas, ['atomic' => false]);
                if ($rst) {
                    $response['success'] = 1;
                    $conn->commit();
                    return true;
                }
                else {
                    $response['error'] = 1;
                    $conn->rollback();
                    return false;
                }
            } catch (\PDOException $e) {
                $response['error'] = 1;
                $conn->rollback();
                return false;
            }
    }

    //ot form
    public function getApproveOT($staffs, $staffId, $position)
    {
        if (in_array($position, Constants::LEAD_CHAR) !== false) {
            $where = [
                'OR' => [
                    // Nhi: hide - not show himself in his staff management
                    // [ //self
                    //     'OTApply.StaffID IN' => $staffId,
                    //     'OTApply.LeaderApproved' => 0,
                    // ],
                    [
                        'OTApply.StaffID NOT IN' => $staffId,
                        'OTApply.StaffID IN' => $staffs,
                        'OR' => [
                            //"OTApply.LeaderApproved" => 0,
                            "OTApply.LeaderApproved IS NULL",
                        ]
                    ]
                ]
            ];
        }
        elseif (in_array($position, Constants::SHUNIN_CHAR) !== false) {
            $where = [
                'OR' => [
                    // [ //self
                    //     'OTApply.StaffID IN' => $staffId,
                    //     'OTApply.LeaderApproved' => 0,
                    // ],
                    [
                        'OTApply.StaffID NOT IN' => $staffId,
                        'OTApply.StaffID IN' => $staffs,
                        'OR' => [
                            //"OTApply.LeaderApproved" => 0,
                            "OTApply.LeaderApproved IS NULL",
                        ]
                    ]
                ]
            ];
        }
        elseif (in_array($position, Constants::CEO_CHAR) !== false) {
            $where = [
                'OR' => [
                    // [ //self
                    //     'OTApply.StaffID IN' => $staffId,
                    //     'OTApply.LeaderApproved' => 0,
                    // ],
                    [
                        'OTApply.StaffID NOT IN' => $staffId,
                        'OTApply.StaffID IN' => $staffs,
                        'OR' => [
                            //"OTApply.LeaderApproved" => 0,
                            "OTApply.LeaderApproved IS NULL",
                        ]
                    ]
                ]
            ];
        }
        else { //staffs
            $where = [
                'OTApply.StaffID IN' => $staffId,
                'OTApply.LeaderApproved' => 0,
            ];
        }

        return $this->find()
            ->contain(['TBLMStaff'])
            ->where($where)
            ->order([
                'OTApply.Date' => 'ASC'
            ]);
    }

    public function countStaffOfTeam($f_char, $t_char, $wdate) {
        return $this->find()->contain([
            'TBLMStaff3'
        ])
            ->where([
                'DATE(OTApply.Date)' => $wdate,
                'SUBSTR(TBLMStaff3.StaffID, 1, 1) =' => $f_char,
                'SUBSTR(TBLMStaff3.Team, 1, 3) =' => $t_char
            ])
            ->group('TBLMStaff3.StaffID')
            ->count();
    }

    public function countTotalOTByStaffID($staffID, $month, $year ) {
        $otTotal = $this->find();
        $otTotal = $otTotal->where([
            'staffID'=> $staffID,
            'LeaderApproved' => 1,
            'Date LIKE' => $year."-".$month."%"
        ]);
        $sumOftotal =  $otTotal->sumOf('Total');
        return $sumOftotal;
    }
}
