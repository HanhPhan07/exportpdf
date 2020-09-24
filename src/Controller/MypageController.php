<?php

/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package        app.Controller
 * @link        https://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */

use App\Helper\Excel;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Dompdf\Dompdf;
use Exception;
use Cake\I18n\Time;
use DateInterval;
use DateTime;

class MypageController extends AppController
{

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('TBLMStaff');
        $this->loadModel('TBLMStaff3');
        // $this->loadModel('OTApply');
        // $this->loadModel('TBLTEvaluationTime');
        // $this->loadModel('TBLTSelfEvaluation');
        // $this->loadModel('TBLTOtherEvaluation');
    }

    public function beforeRender(Event $event)
    {
        $this->viewBuilder()->setLayout('admin_default');
        parent::beforeRender($event);
    }

    public function index()
    {
    }

    public function emerg()
    {
        $lst = $this->TBLMStaff3->getAll();
        $result = Excel::makeStaffEmerg($lst);
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }

    public function exportStaffInfo()
    {
        $result = Excel::makeExportStaffInfo();
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }

    public function exportOvertime()
    {
        $data = $this->getRequest()->getData();
        $staffId = $data['staff_id'];
        $dateFrom = $data['date_from'];
        $dateTo = $data['date_to'];
        $data = Excel::makeExportOvertime($staffId, $dateFrom, $dateTo);
        $dateFrom = date("Y-m-d", strtotime($dateFrom));
        $dateTo = date("Y-m-d", strtotime($dateTo));
        if ($data['error'] == '') {
            $builder = $this->viewBuilder();
            // configure as needed
            $builder->setLayout(false);
            $builder->setTemplate('/Admin/Mypage/ExportOvertime');
            $builder->setHelpers(['Html']);
            // create a view instance
            $view = $builder->build(compact('data'));
            $content = $view->render();

            $dompdf = new Dompdf(array('enable_font_subsetting' => true));
            $dompdf->loadHtml(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), "UTF-8");
            $dompdf->set_option('defaultFont', 'Times-Roman');

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4');

            // Render the HTML as PDF
            $dompdf->render();

            /* save file */
            $fileDir = WWW_ROOT . "excel";
            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $fileName = "Overtime_" . $staffId . "_" . $dateFrom . "-" . $dateTo . ".pdf";
            $output = $path . "/" . $fileName;

            $file = $dompdf->output();
            file_put_contents($output, $file);

            $result['success'] = 1;
            $result['pdf'] = "excel/output/" . $fileName;

            // $fileExport = $staffID . "-" . date("Ymd", strtotime($date)) . "-" . date("Ymd", strtotime($staff->TrialEntryDate)) . "-" . $staff->StaffName . ".pdf";
            // // Output the generated PDF to Browser
            // $dompdf->stream($fileExport);
        } else {
            $result['error'] = $data['error'];
        }
        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($result));
    }

    public function exportShiftLeavePdf()
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        $query = $this->getRequest()->getQuery();
        $month = isset($query['month']) ? date("Y/m", strtotime($query['month'] . "/01")) : die("Please insert month!");
        $data = Excel::makeExportShiftLeavePdf($month);

        $builder = $this->viewBuilder();
        // configure as needed
        $builder->setLayout(false);
        $builder->setTemplate('/Element/Admin/Pdf/shift_leave');
        $builder->setHelpers(['Html']);
        // create a view instance
        $view = $builder->build(compact('data'));
        $content = $view->render();
        // echo $content; exit();

        $dompdf = new Dompdf();
        $dompdf->loadHtml(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), "UTF-8");
        $dompdf->set_option('defaultFont', 'Times-Roman');
        // $dompdf->set_base_path('/webroot/css/shift-leave-pdf.css');
        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A3', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        $dompdf->stream(
            "Shift_Leave_" . date("Y-m", strtotime($month . "/01")) . '.pdf'
            // array("Attachment" => false
        );
    }

    public function exportStaffContactPdf(){
        $data=$this->loadModel('TBLMStaff3')->getAll();
        // dd($data);
        $this->set('data', $data);// dd($dataHR);
        $builder = $this->viewBuilder();
        $builder->setLayout(false);
        $builder->setTemplate('/Element/Admin/Pdf/exportStaffContactPdf');
        $builder->setHelpers(['Html']);
        $view = $builder->build(compact('data'));
        $content = $view->render();
        $dompdf = new Dompdf();
        $dompdf->loadHtml(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), "UTF-8");
        $dompdf->set_option('defaultFont', 'Times-Roman');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        //$dompdf->stream("working" . date("Y-m") . '.pdf');



        // $queryarray=[] ;

        // $result = TableRegistry::getTableLocator()->get('tblmstaff3');
        // $query = $result->find()
        //     ->join([
        //         'tblmstaff' => [
        //             'table' => 'tblmstaff',
        //             'type' => 'lEFT',
        //             'conditions' => [
        //                 'tblmstaff.StaffID = tblmstaff3.StaffID',
        //             ]
        //         ],
        //         'tblmstaff2' => [
        //             'table' => 'tblmstaff2',
        //             'type' => 'INNER',
        //             'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //         ]
        //     ])->where(['tblmstaff.QuitJobDate IS NULL']);


        //     $queryH= $query->find('all', [
        //         'conditions' => [
        //             'tblmstaff3.StaffID LIKE' => 'H%',
        //             'tblmstaff3.Position'=>'Staff',
        //         ]
        //     ])->enableHydration(false)->toList();
        //     //dd($query)
        //     $queryarray['H']=$query;
        //     //dd($queryH);
        //     //$queryT= $query->find('all')->where(['Team IS NOT' => 'BO'])->group('Team')->extract('Team')->toList();
        //             //->enableHydration(false)
        //             //->extract('Team')
        //     //dd($query);
        //     $query = $result->find()
        //         ->join([
        //             'tblmstaff' => [
        //             'table' => 'tblmstaff',
        //             'type' => 'LEFT',
        //             'conditions' => [
        //                 'tblmstaff.StaffID = tblmstaff3.StaffID',
        //                 ]
        //             ],
        //             'tblmstaff2' => [
        //                 'table' => 'tblmstaff2',
        //                 'type' => 'INNER',
        //                 'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //                 ]
        //         ])->where(['tblmstaff.QuitJobDate IS NULL']);

        //     $query= $query->find('all')->where(['Team IS NOT' => 'BO'])->group('Team')->extract('Team')->toList();
        //     //dd($query);
        //     //$queryarray[$value]=$query;
            
        //      foreach($query as  $value){
        //         $query = $result->find()
        //         ->join([
        //             'tblmstaff' => [
        //             'table' => 'tblmstaff',
        //             'type' => 'LEFT',
        //             'conditions' => [
        //                 'tblmstaff.StaffID = tblmstaff3.StaffID',
        //                 ]
        //             ],
        //             'tblmstaff2' => [
        //                 'table' => 'tblmstaff2',
        //                 'type' => 'INNER',
        //                 'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //                 ]
        //         ])->where(['tblmstaff.QuitJobDate IS NULL']);
        //         $query=$query->find('all')->where([
        //                 'Team' => $value ,
        //             ])->group('Part')->extract('Part')->toList();   
                
        //         if($value=='PG01'){
        //             $query = $result->find()
        //             ->join([
        //                 'tblmstaff' => [
        //                 'table' => 'tblmstaff',
        //                 'type' => 'LEFT',
        //                 'conditions' => [
        //                     'tblmstaff.StaffID = tblmstaff3.StaffID',
        //                     ]
        //                 ],
        //                 'tblmstaff2' => [
        //                     'table' => 'tblmstaff2',
        //                     'type' => 'INNER',
        //                     'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //                     ]
        //             ])->where(['tblmstaff.QuitJobDate IS NULL']);
        //             $query=$query->find('all')->where([
        //                 ['Team' => $value],
        //                 'OR' => [['Position'=> 'Leader'],['Position'=> 'Shunin']],
        //             ]);
        //             $query->enableHydration(false);
        //             $query=$query->toList();  
        //             $queryarray[$value][0]=$query;

        //             $query = $result->find()
        //             ->join([
        //                 'tblmstaff' => [
        //                 'table' => 'tblmstaff',
        //                 'type' => 'LEFT',
        //                 'conditions' => [
        //                     'tblmstaff.StaffID = tblmstaff3.StaffID',
        //                     ]
        //                 ],
        //                 'tblmstaff2' => [
        //                     'table' => 'tblmstaff2',
        //                     'type' => 'INNER',
        //                     'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //                     ]
        //             ])->where(['tblmstaff.QuitJobDate IS NULL']);
        //             $query=$query->find('all')->where([
        //                 ['Team' => $value],
        //                 ['Position'=> 'Staff'],
        //             ]);
        //             $query->enableHydration(false);
        //             $query=$query->toList();  
        //             $queryarray[$value][0]['Staff']=$query;

        //         } else{
        //             foreach($query as $valueteam){
        //                 $queryarray[$value][$valueteam]=$valueteam;
        //                 $queryPosition = $result->find()
        //                     ->join([
        //                         'tblmstaff' => [
        //                         'table' => 'tblmstaff',
        //                         'type' => 'LEFT',
        //                         'conditions' => [
        //                             'tblmstaff.StaffID = tblmstaff3.StaffID',
        //                             ]
        //                         ],
        //                         'tblmstaff2' => [
        //                             'table' => 'tblmstaff2',
        //                             'type' => 'INNER',
        //                             'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //                             ]
        //                     ])->where(['tblmstaff.QuitJobDate IS NULL']);
        //                     $queryPosition=$queryPosition->find('all')->where([
        //                                     ['Team' => $value],
        //                                     'OR' => [['Position'=> 'Leader'],['Position'=> 'Shunin']],
        //                                     'Part'=>$valueteam,
        //                                 ]);
        //                                 $queryPosition->enableHydration(false);
        //                                 $queryPosition=$queryPosition->toList();  
        //                                 //dd($query) ;        
        //                     $queryarray[$value][$valueteam]=$queryPosition;
        //                     //$queryarray[$value][$valueteam]['Staff']=$queryStaff;
        //                     // dd($queryarray);
        //                     $queryStaff = $result->find()
        //                     ->join([
        //                         'tblmstaff' => [
        //                         'table' => 'tblmstaff',
        //                         'type' => 'LEFT',
        //                         'conditions' => [
        //                             'tblmstaff.StaffID = tblmstaff3.StaffID',
        //                             ]
        //                         ],
        //                         'tblmstaff2' => [
        //                             'table' => 'tblmstaff2',
        //                             'type' => 'INNER',
        //                             'conditions' => 'tblmstaff2.StaffID = tblmstaff3.StaffID',
        //                             ]
        //                     ])->where(['tblmstaff.QuitJobDate IS NULL']);
        //                     $queryStaff=$queryStaff->find('all')->where([
        //                                     ['Team' => $value],
        //                                     'OR' => [['Position'=> 'Staff'],['Position'=> 'Subleader']],
        //                                     'Part'=>$valueteam,
        //                                 ]);
        //                                 $queryStaff->enableHydration(false);
        //                                 $queryStaff=$queryStaff->toList();  
        //                     $queryarray[$value][$valueteam]['Staff']=$queryStaff;
        //                     //dd($queryStaff);
    
        //             }
        //         }
            // }
    }
}