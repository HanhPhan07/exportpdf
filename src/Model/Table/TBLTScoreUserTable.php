<?php

namespace App\Model\Table;

class TBLTScoreUserTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblScoreUser');
        $this->setPrimaryKey('ID');
    }

    
}
