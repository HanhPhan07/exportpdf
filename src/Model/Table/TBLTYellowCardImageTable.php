<?php

namespace App\Model\Table;

class TBLTYellowCardImageTable extends BaseTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTYellowCardImage');
        $this->setPrimaryKey('ID');
        $this->belongsTo('TBLTYellowCard', [
            'className' => 'TBLTYellowCard',
            'foreignKey' => ['YellowCardID'],
            'bindingKey' => ['YellowCardID'],
            'joinType' => 'INNER',
            'propertyName' => 'TBLTYellowCard',
        ]);
    }
}
