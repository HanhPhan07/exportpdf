<?php

namespace App\Model\Table;

class TBLTNotificationsTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTNotifications');
        $this->setPrimaryKey('ID');
    }

}
