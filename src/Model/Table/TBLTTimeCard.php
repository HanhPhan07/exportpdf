<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLTTimeCard extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTTimeCard');
        $this->setPrimaryKey('StaffID');
    }

    public function importSalary() {
        
    }
}
