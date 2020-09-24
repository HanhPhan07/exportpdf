<?php

namespace App\Model\Table;

class TBLTYellowCardTable extends BaseTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTYellowCard');
        $this->setPrimaryKey('YellowCardID');
        $this->hasMany('TBLTYellowCardImage', [
            'className' => 'TBLTYellowCardImage',
            'foreignKey' => ['YellowCardID'],
            'bindingKey' => ['YellowCardID'],
            'propertyName' => 'TBLTYellowCardImage',
        ]);
        $this->hasOne('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => ['StaffID'],
            'bindingKey' => ['StaffID'],
            'propertyName' => 'TBLMStaff',
        ]);
    }
}
