<?php

namespace App\Model\Table;
use Cake\Validation\Validator;
class TBLTWhatNewsTable extends BaseTable
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('tblTWhatNews');
        $this->setPrimaryKey('ID');
        $this->belongsTo('TBLMStaff', [
            'className' => 'TBLMStaff',
            'foreignKey' => 'StaffID',
        ]);

    }
    //  public function validationDefault(Validator $validator)
    // {
    //     $validator
    //         ->notEmpty('StaffID', 'Need enter StaffID')
    //         ->notEmpty('VNTitle', 'Need enter VNTitle')
    //         ->notEmpty('JPTitle', 'Need enter JPTitle')
    //         ->notEmpty('ENTitle', 'Need enter ENTitle')
    //         ->notEmpty('VNContents', 'Need enter VNContents')
    //         ->notEmpty('JPContents', 'Need enter JPContents')
    //         ->notEmpty('ENContents', 'Need enter ENContents');

    //     return $validator;
    // }
    public function getNews($staffID = '') {

        if($staffID !=''){
            $conds= [
                'TBLTWhatNews.StaffID' => $staffID,

            ];
        }else{
            $conds = [
                'PublishFlag' => 1,
                //'PublishDate >=' => date('Y-m-d', strtotime(date('Y-m-d').' -10 days'))
            ];
        }

        if($staffID){
            $select = [
                'ID' => 'TBLTWhatNews.ID',
                'PublishDate' => 'TBLTWhatNews.PublishDate',
                'StaffName'   => 'TBLMStaff.StaffName',
                'PublishFlag' => 'TBLTWhatNews.PublishFlag',
                'Title'     => 'TBLTWhatNews.Title',
                'Contents'     => 'TBLTWhatNews.Contents',
                'Watched' => 'TBLTWhatNews.Watched'
            ];

        }else{
            $select = [
                'ID' => 'TBLTWhatNews.ID',
                'Title' => 'TBLTWhatNews.Title',
                'Contents' => 'TBLTWhatNews.Contents',
                'PublishDate' => 'TBLTWhatNews.PublishDate',
                'StaffName'     => 'TBLMStaff.StaffName',
                'StaffID'     => 'TBLMStaff.StaffID',
                'Watched' => 'TBLTWhatNews.Watched'
            ];
        }
        $lst = $this->find()
        ->select($select)
         ->join([
                'TBLMStaff' => [
                    'table' => 'tblMStaff',
                    'type' => 'LEFT',
                    'conditions' => 'TBLTWhatNews.StaffID = TBLMStaff.StaffID',
                ]
            ])
        ->where($conds)
        ->order(['PublishDate' => 'DESC']);
        if ($staffID =='') {
            $lst->limit(7);

        }
        return $lst;
    }

}
