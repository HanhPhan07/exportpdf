<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLSaturdayWorkingTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblSaturdayWorking');
        $this->setPrimaryKey('ID');
    }
}
