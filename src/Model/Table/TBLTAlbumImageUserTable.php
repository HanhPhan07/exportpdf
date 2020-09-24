<?php


namespace App\Model\Table;

class TBLTAlbumImageUserTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTAlbumImageUser');
        $this->setPrimaryKey('ID');
    }

    
}
?>


