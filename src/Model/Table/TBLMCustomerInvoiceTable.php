<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMCustomerInvoiceTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblMCustomerInvoice');
        $this->setPrimaryKey('ID');

        $this->hasMany('TBLTRedInvoice', [
			'className' => 'TBLTRedInvoice',
			'foreignKey' => false,
			'conditions' => ['TBLTRedInvoice.CustomerID = TBLMCustomerInvoice.CustomerID'],
			'propertyName' => 'TBLTRedInvoice',
        ]);

        $this->hasMany('TBLTRedInvoice', [
			'className' => 'TBLTRedInvoice',
			'foreignKey' => false,
			'conditions' => ['TBLTRedInvoice.CustomerName = TBLMCustomerInvoice.CustomerName'],
			'propertyName' => 'TBLTRedInvoice',
		]);
    }
}
