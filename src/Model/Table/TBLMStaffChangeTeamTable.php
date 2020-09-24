<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMStaffChangeTeamTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblMStaffChangeTeam');
        $this->setPrimaryKey('ID');
    }

}
