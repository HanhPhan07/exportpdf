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
class TBLTHolidayTable extends Table
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

        $this->setTable('tblTHoliday');
        $this->setDisplayField('ID');
        $this->setPrimaryKey('ID');
    }
}

