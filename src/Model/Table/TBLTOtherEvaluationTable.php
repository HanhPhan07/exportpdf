<?php

namespace App\Model\Table;

class TBLTOtherEvaluationTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tbltOtherEvaluation');
        $this->setPrimaryKey('ID');

        $this->belongsTo('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => 'StaffID_From',
            'propertyName' => 'TBLMStaff',
        ]);
    }


}
