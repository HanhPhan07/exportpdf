<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Constants;

class TBLTagTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTag');
        $this->setPrimaryKey('ID');

        $this->hasOne('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => ['StaffID'],
            'bindingKey' => ['StaffID'],
            'propertyName' => 'TBLMStaff',
        ]);

    }
}
