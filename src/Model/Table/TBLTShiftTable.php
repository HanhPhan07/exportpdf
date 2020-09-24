<?php

namespace App\Model\Table;

use Cake\ORM\Table;

class TBLTShiftTable extends Table {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->setTable('tblTShift');
		$this->setPrimaryKey('ID');
		$this->hasOne('TBLMStaff3', [
			'className' => 'TBLMStaff3',
			'foreignKey' => ['StaffID'],
			'bindingKey' => ['StaffID'],
			'propertyName' => 'TBLMStaff3',
		]);
		$this->belongsTo('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => 'StaffID',
        ]);

	}

	public function delShiftDate($date) {
		return $this->deleteAll(['Date' => $date]);
	}

	public function delShiftMiddleware($from, $to, $isDeleteByTeam = false, $staffs) {
		if ($isDeleteByTeam) {
			$staffIDs = array_map(function ($staff) {
				return $staff->StaffID;
			}, $staffs);
			return $this->delShiftDateRangeAndTeam($from, $to, $staffIDs);
		} else {
			return $this->delShiftDateRange($from, $to);
		}
	}

	public function delShiftDateRangeAndTeam($from, $to, $staffIDs) {
		return $this->deleteAll(['Date >=' => $from, 'Date <=' => $to, 'StaffID IN' => $staffIDs]);
	}

	public function delShiftDateRange($from, $to) {
		return $this->deleteAll(['Date >=' => $from, 'Date <=' => $to]);
	}

	public function getShiftlByDate($staff_id, $from, $to) {
		$shifts = $this->find()
			->select(['Shift'])
			->where(['StaffID' => $staff_id, 'Date >= ' => $from, 'Date <=' => $to]);
		$list = [];

		foreach ($shifts as $key => $value) {
			if (!in_array($value->Shift, $list)) {
				$list[] = $value->Shift;
			}

		}
		return $list;
	}

	/**
	 * @param $from
	 * @param $to
	 * @param $staffID
	 * @return \Cake\ORM\Query
	 * @author DANDEV
	 */
	public function getAllShiftDataRangeOfOneStaff($from, $to, $staffID) {
		$shifts = $this->find()->where(['Date >=' => $from, 'Date <=' => $to, 'StaffID ' => $staffID]);
		return $shifts;
	}

	/**
	 * @param $date
	 * @param $team
	 * @return array|\Cake\Datasource\EntityInterface|null
	 * @author  DANDDEV
	 */
	public function getCurrentShiftOfTeam($date, $team, $staffID) {
		$shifts = $this->find()

			->contain(['TBLMStaff3'])
			->where(['Date' => $date, 'TBLMStaff3.Team' => $team, 'TBLMStaff3.StaffID <>' => $staffID])->firstOrFail();
		return $shifts;
	}

	public function getStaffShiftDetailByDate($staffID, $date) {
		$shift = $this->find()->where(['Date' => $date, 'StaffID' => $staffID])->firstOrFail();
		$date = new \DateTime($shift->FromTime);

		$fromTime = $date->format('H:i:s');
		$date->add(new \DateInterval('PT4H'));
		$endTime = $date->format('H:i:s');
		$result['firstShift'] = ['start' => $fromTime, 'end' => $endTime];

		$date->add(new \DateInterval('PT1H'));
		$fromTime = $endTime;
		$endTime = $date->format('H:i:s');
		$result['break'] = ['start' => $fromTime, 'end' => $endTime];

		$date->add(new \DateInterval('PT4H'));
		$fromTime = $endTime;
		$endTime = $date->format('H:i:s');
		$result['secondShift'] = ['start' => $fromTime, 'end' => $endTime];

		return $result;
	}

	public function getStaffShiftByMonth($staffID, $year, $month) {
		$shifts = $this->find()->where([
			'Date LIKE' => $year . "-" . $month . "%",
			'StaffID ' => $staffID,
		]);
		return $shifts;
	}
	public function getStaffWithShift($staffIDs) {
		$where = [
            'TBLTShift.StaffID IN' => $staffIDs,
            'TBLTShift.Date >=' => date("Y-m-d", strtotime("-3 month", strtotime(date('Y-m-d'))))

        ];
        return $this->find()
            ->contain(['TBLMStaff'])
            ->select([
                'StaffID'  => 'TBLTShift.StaffID',
                'FromDate' => 'TBLTShift.Date',
                'ToDate'   => 'TBLTShift.Date',
                'FromTime' => 'TBLTShift.FromTime',
                'ToTime'   => 'TBLTShift.ToTime',
                'StaffName'=> 'TBLMStaff.StaffName',
                'Shift'    => 'TBLTShift.Shift',
                'Avatar'   => 'TBLMStaff.Avatar'
            ])
            ->where($where)
            ->order([
                'TBLTShift.Date' => 'ASC',
            ]);
	}
}
