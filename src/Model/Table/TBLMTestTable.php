<?php
/**
 * @author  DANDEV
 */
namespace App\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;
use Cake\ORM\Table;

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
class TBLMTestTable extends Table
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

        $this->setTable('tblMTest');
        $this->setDisplayField('ID');
        $this->setPrimaryKey('ID');
    }

    public function getAll($testId, $selectedIds)
    {
        $query = $this->find()->where(function (QueryExpression $exp, Query $q) use ($testId) {
            return $exp->like('TestID', '%' . $testId . '%');
        });
        if (!empty($selectedIds)) {
            $query->whereNotInList('ID', $selectedIds);
        }
        return $query;
    }
}