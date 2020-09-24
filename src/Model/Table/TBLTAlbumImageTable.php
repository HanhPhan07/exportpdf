<?php


namespace App\Model\Table;

class TBLTAlbumImageTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTAlbumImage');
        $this->setPrimaryKey('ID');
    }

    
}
?>


