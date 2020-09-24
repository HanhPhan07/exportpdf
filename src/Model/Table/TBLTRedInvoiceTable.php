<?php

namespace App\Model\Table;

class TBLTRedInvoiceTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTRedInvoice');
        $this->setPrimaryKey('ID');

        $this->belongsTo('TBLMCustomerInvoice', [
            'className' => 'TBLMCustomerInvoice',
            'foreignKey' => 'CustomerID',
            'propertyName' => 'TBLMCustomerInvoice',
        ]);

        $this->belongsTo('TBLMCustomerInvoice', [
            'className' => 'TBLMCustomerInvoice',
            'foreignKey' => 'CustomerName',
            'propertyName' => 'TBLMCustomerInvoice',
        ]);

        $this->hasMany('TBLTRedInvoiceItem', [
			'className' => 'TBLTRedInvoiceItem',
			'foreignKey' => false,
			'conditions' => ['TBLTRedInvoice.ID = TBLTRedInvoiceItem.RedInvoice_ID'],
			'propertyName' => 'TBLTRedInvoiceItem',
		]);
    }


}
