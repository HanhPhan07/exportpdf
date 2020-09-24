<?php

namespace App\Model\Table;

class TBLTEvaluationTimeTable extends BaseTable{
    public  function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tbltEvaluationTime');
        $this->setPrimaryKey('EvaluationID');
    }
}
