<?php

namespace App\Model\Table;

class TBLTScoreAdminTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblScoreAdmin');
        $this->setPrimaryKey('ID');
    }

    
}
