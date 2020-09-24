<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMStaff2Table extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblMStaff2');
        $this->setPrimaryKey('StaffID');
    }
}
