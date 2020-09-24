<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMCustomerTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblMCustomer');
        $this->setPrimaryKey('ID');
    }
}
