<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLTSalaryMetaTable extends Table
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTSalaryMeta');
    }

    public function getCurrentSalaryMetaData($staffID, $year, $month) {
        return $this->find()
            ->select()
            ->where([
                'StaffID' => $staffID,
                'Date LIKE' => $year."-".$month."%"
            ])->first();
    }
}
