<?php

namespace App\Model\Table;
use Cake\Database\Schema\TableSchema;

class TBLTSelfEvaluationTable extends BaseTable
{
    // protected function _initializeSchema(TableSchema $schema)
    // {
    //     $schema->columnType('Goal', 'json');
    //     $schema->columnType('Strength', 'json');
    //     $schema->columnType('Weakness', 'json');
    //     return $schema;
    // }

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tbltSelfEvaluation');
        $this->setPrimaryKey('ID');

        $this->hasOne('TBLMStaff', [
			'className' => 'TBLMStaff',
			'foreignKey' => ['StaffID'],
			'bindingKey' => ['StaffID'],
			'propertyName' => 'TBLMStaff',
		]);
    }
    // public function getSelfInfor($StaffID, $evaluationKey){
    // 	return $this->find()
	// 		->where(['StaffID' => $StaffID, 'EvaluatePeriod' => $evaluationKey])
	// 		->first();
    // }

}
