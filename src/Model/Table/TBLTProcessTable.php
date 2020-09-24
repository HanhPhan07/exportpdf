<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLTProcessTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTProcess');
        $this->setPrimaryKey('ID');
    }
}
