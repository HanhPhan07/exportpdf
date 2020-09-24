<?php
/**
 * @author  NHIPHAN
 */
namespace App\Model\Table;

/**
 * @author  NHIPHAN
 * TBLTCompanyRegulationsTable Model
 *
 */
class TBLTCompanyRegulationsTable extends BaseTable
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

        $this->setTable('tblTCompanyRegulations');
        $this->setDisplayField('ID');
        $this->setPrimaryKey('ID');
    }

    /**
     * getAll
     *
     * @return void
     */
    public function getAll()
    {
        return $this->find('all')->order('OrderNo', 'ASC')->toArray();
    }

}