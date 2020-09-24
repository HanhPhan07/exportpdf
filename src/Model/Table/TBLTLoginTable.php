<?php

namespace App\Model\Table;

class TBLTLoginTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTLogin');
        $this->setPrimaryKey('ID');
    }

    public function getLastLogin($staffID) {
        return $this->find()
            ->where(['StaffID' => $staffID])
            ->order(['ID' => 'DESC'])
            ->first();
    }
}
