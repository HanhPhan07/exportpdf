<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMSalaryRangeTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblMSalaryRange');
    }
}
