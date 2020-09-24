<?php

namespace App\Model\Table;

class TBLTSalaryLevelTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTSalaryLevel');
        $this->setPrimaryKey('ID');
    }

    public function getSalary($staffId) {
        return $this->find()
            ->select('TBLMSalaryRange.Amount')
            ->join([
                'table' => 'tblMSalaryRange',
                'alias' => 'TBLMSalaryRange',
                'type' => 'LEFT',
                'conditions' => 'TBLMSalaryRange.SalaryLevel = TBLTSalaryLevel.SalaryLevel',
            ])
            ->where(['StaffID' => $staffId])
            ->order(['TBLTSalaryLevel.DateUpdated' => 'DESC'])
            ->first();
    }

    public function getSalaryRange($staffId) {
        return $this->find()
            ->select('TBLMSalaryRange.SalaryLevel')
            ->join([
                'table' => 'tblMSalaryRange',
                'alias' => 'TBLMSalaryRange',
                'type' => 'LEFT',
                'conditions' => 'TBLMSalaryRange.SalaryLevel = TBLTSalaryLevel.SalaryLevel',
            ])
            ->where(['StaffID' => $staffId])
            ->order(['TBLTSalaryLevel.DateUpdated' => 'DESC'])
            ->first();
    }

}
