<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLMItemTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblMItem');
    }

    public function getValue($code){
        $item = $this->find()->where(['code' => $code])->first();
        return $item->Value;
    }
}
