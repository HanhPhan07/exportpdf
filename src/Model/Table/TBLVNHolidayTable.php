<?php
/**
 * @author  DANDEV
 */
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @author  DANDEV
 * TBLVNHoliday Model
 *
 * @method \App\Model\Entity\TBLVNHoliday get($primaryKey, $options = [])
 * @method \App\Model\Entity\TBLVNHoliday newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\TBLVNHoliday[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\TBLVNHoliday|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TBLVNHoliday saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\TBLVNHoliday patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TBLVNHoliday[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\TBLVNHoliday findOrCreate($search, callable $callback = null, $options = [])
 */
class TBLVNHolidayTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('tblVNHoliday');
        $this->setDisplayField('ID');
        $this->setPrimaryKey('ID');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->allowEmptyString('ID', null, 'create');

        $validator
            ->scalar('HolidayDate')
            ->requirePresence('HolidayDate', 'create')
            ->notEmptyString('HolidayDate');

        $validator
            ->scalar('HolidayName')
            ->maxLength('HolidayName', 255)
            ->allowEmptyString('HolidayName');

        $validator
            ->scalar('HolidayNote')
            ->allowEmptyString('HolidayNote');

        $validator
            ->dateTime('DateUpdated')
            ->requirePresence('DateUpdated', 'create')
            ->notEmptyDateTime('DateUpdated');

        return $validator;
    }

    /**
     * @param $date
     * @return array|\Cake\Datasource\EntityInterface
     * @author  DANDEV
     */
    public function getHolidayByDate($date){

        return $this->find()->where(['HolidayDate' => $date])->first();
    }

    /**
     * @param $fromDate
     * @param $toDate
     * @return array
     * @author  DANDEV
     */
    public function getRangeHoliday($fromDate,$toDate){

        return $this->find()->where(['HolidayDate >=' => $fromDate,'HolidayDate <='=>$toDate])->toList();
    }
}
