<?php

namespace App\Model\Table;

class TBLTRedInvoiceItemTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTRedInvoiceItem');
        $this->setPrimaryKey('ID');

        $this->belongsTo('TBLTRedInvoice', [
            'className' => 'TBLTRedInvoice',
            'foreignKey' => 'RedInvoice_ID',
            'propertyName' => 'TBLTRedInvoice',
        ]);
    }


}
