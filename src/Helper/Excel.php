<?php

namespace App\Helper;

use App\Model\Entity\TBLTALApply;
use Cake\Chronos\Chronos;
use Cake\ORM\TableRegistry;
use Constants;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// require 'Header.php';

class Excel
{
    const MONTH_COLORS = [
        '01' => 'CC0066', '02' => 'FFCCCC', '03' => 'FFE5CC', '04' => 'FFFFCC',
        '05' => 'E5FFCC', '06' => 'F8CBAD', '07' => '9BC2E6', '08' => 'CC6600',
        '09' => '9999FF', '10' => 'E5CCFF', '11' => '660033', '12' => '0E4175',
    ];

    const WEEKDAY_EN = [
        'Hai' => 'Mon',
        'Ba' => 'Tue',
        'Tư' => 'Wed', 'T?' => 'Wed',
        'Năm' => 'Thu', 'N?m' => 'Thu',
        'Sáu' => 'Fri',
        'Bảy' => 'Sat', 'B?y'  => 'Sat',
        'CN' => 'Sun'
    ];

    public static $rowResult = [];
    public static $rowResultPdf = [];

    public static function makeSalaryLevel()
    {
        $response = ['success' => 0, 'error' => ''];
        try {
            $fileName = "1906CurrentSalaryLevel.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $spreadsheet = IOFactory::load($input);
            $sheet = $spreadsheet->getActiveSheet();

            $salaryRanges = TableRegistry::get('TBLMSalaryRange')
                ->find()
                ->select([
                    'SalaryLevel', 'Amount',
                    'level' => '(CAST(SUBSTR(SalaryLevel, 3) AS UNSIGNED))',
                ])
                ->order(['level' => 'ASC'])
                ->all();

            $startLevelColumn = "A";
            $startAmountColumn = "B";
            $row = 3; // start write range in row 3
            $levelRows = [];
            foreach ($salaryRanges as $range) {
                // write SalaryLevel to A3
                $sheet->setCellValue($startLevelColumn . $row, $range->SalaryLevel);
                // write Amount to B3
                $sheet->setCellValue($startAmountColumn . $row, $range->Amount);

                // keep the row of the level to quickly find a row using a level
                $levelRows[$range->SalaryLevel] = $row;
                $row++;
            }
            $maxRow = $row - 1;

            $staffs = TableRegistry::get('TBLMStaff')
                ->find()
                ->select([
                    'TBLMStaff.StaffID',
                    'levels' => '(GROUP_CONCAT(SalaryLevel.SalaryLevel, ":", SalaryLevel.DateUpdated  ORDER BY SalaryLevel.DateUpdated ASC))',
                ])
                ->join([
                    'SalaryLevel' => [
                        'table' => 'tblTSalaryLevel',
                        'type' => 'INNER',
                        'conditions' => [
                            'TBLMStaff.StaffID = SalaryLevel.StaffID',
                        ],
                    ],
                ])
                ->where(['OR' => ['DATE(QuitJobDate) >' => date("Y-m-01"), 'QuitJobDate IS NULL']])
                ->order(['TBLMStaff.StaffID' => 'ASC'])
                ->group(['TBLMStaff.StaffID'])
                ->all();

            $usedColors = [];
            $staffIdRow = 2;
            $columnNumber = 3; // start with col C (index of col C is 3)
            $endSeisakuCol = 0;
            $startMadoguchiCol = 0;
            foreach ($staffs as $staff) {
                // find column charracter
                $columnChar = Coordinate::stringFromColumnIndex($columnNumber);
                $sheet->getColumnDimension($columnChar)->setWidth(6);

                // write StaffID to C2
                $sheet->setCellValue($columnChar . $staffIdRow, $staff->StaffID);

                // find column where AXXX ends
                if ($endSeisakuCol == 0) {
                    if (substr($staff->StaffID, 0, 1) != "A") {
                        $endSeisakuCol = $columnNumber - 1;
                        $startMadoguchiCol = $columnNumber;
                    }
                }

                if ($staff->levels) {
                    $levelAndDates = explode(",", $staff->levels);
                    $max_date = 0;
                    foreach($levelAndDates as $item){
                        $date = explode(":", $item);
                        if($date[1] > $max_date){
                            $max_date = $date[1];
                        }
                    }

                    foreach ($levelAndDates as $levelAndDate) {
                        $parts = explode(":", $levelAndDate);
                        if (count($parts) != 2) {
                            continue;
                        }
                        $level = $parts[0];
                        $date = $parts[1];
                        $year = substr($date, 0, 4);
                        $month = substr($date, -2);
                        $currentYear = date('Y');
                        if (isset($levelRows[$level])) {
                            if ($year == $currentYear) {
                                if($date == $max_date){
                                    $color = 'CC0066';
                                    $usedColors[$month] = ['date' => $year . "/" . $month, 'color' => $color];
                                } else {
                                    // $color = self::MONTH_COLORS[$month];
                                    $color = '808080';
                                    $usedColors[$month] = ['date' => $year . "/" . $month, 'color' => $color];
                                }
                            } else {
                                // default color for other year
                                $color = "808080";
                                $usedColors['13'] = ['date' => 'Others', 'color' => $color];
                            }

                            $sheet->getStyle($columnChar . $levelRows[$level])
                                ->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB($color);
                        }
                    }
                }
                $columnNumber++;
            }

            // merge cells for header - seisaku
            if ($endSeisakuCol) {
                $columnChar = Coordinate::stringFromColumnIndex($endSeisakuCol);
                $sheet->mergeCells('C1:' . $columnChar . 1);
            }

            // merge cells for header - madoguchi
            if ($startMadoguchiCol) {
                $startcolumnChar = Coordinate::stringFromColumnIndex($startMadoguchiCol);
                $sheet->setCellValue($startcolumnChar . 1, "Madoguchi");
                $columnChar = Coordinate::stringFromColumnIndex($columnNumber - 1);
                $sheet->mergeCells($startcolumnChar . 1 . ':' . $columnChar . 1);
            }

            // write used color table
            if (count($usedColors)) {
                ksort($usedColors);
                $startColorRow = $maxRow + 5;
                $sheet->setCellValue("A" . $startColorRow, "Colors");
                $sheet->getStyle("A" . $startColorRow)->getFont()->setBold(true);
                $sheet->getStyle("A" . $startColorRow)
                    ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells("A" . $startColorRow . ':' . 'B' . $startColorRow);

                $row = $startColorRow + 1;
                foreach ($usedColors as $colorData) {
                    $sheet->getStyle("A" . $row)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB($colorData['color']);
                    $sheet->setCellValue("B" . $row, $colorData['date']);
                    $row++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }

            $fileName = "CurrentSalaryLevel" . date('Ymd') . ".xlsx";
            $outPut = $path . "/" . $fileName;
            $writer->save($outPut);
            $response['success'] = 1;
            $response['file'] = "excel/output/" . $fileName;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    public static function makeStaffEmerg($lst)
    {
        $response = ['success' => 0, 'error' => ''];
        try {
            $fileName = "02. EmergencyContactList.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $spreadsheet = IOFactory::load($input);
            $sheet = $spreadsheet->getActiveSheet();

            $bg = 9;
            $l_b = $bg;
            $s_b = $bg;
            $col_c = 'C';
            $col_l = 'D';
            $col_e = 'E';
            $col_s = 'F';
            $style = $sheet->getStyle('AA5');
            $styleArray = [
                'font' => [
                    'size' => 11,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];

            $styleEArray = [
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];

            $styleCLArray = [
                'borders' => [
                    'left' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];
            // Nhi: Back Office Staff
            $backOffice = Excel::getBackOfficeStaff();
            $backIds = [];
            foreach ($backOffice as $id => $value) {
                $backIds[$id] = $value['StaffID'];
                switch ($id) {
                    case "0":
                        $sheet->setCellValue("B5", $value['StaffID'] . " " . $value['StaffName'] . " " . $value['PhoneNumber']);
                        break;
                    case "1":
                        $sheet->setCellValue("F5", $value['StaffID'] . " " . $value['StaffName'] . " " . $value['PhoneNumber']);
                        break;
                }
            }

            $wrapTextCells = [];
            $first = 7;
            foreach ($lst as $teamName => $team) {
                $leaders = @$team['Leader'];
                if (array_key_exists('Staff', $team)) {
                    $offices = $team['Staff'];
                    foreach ($offices as $apart => $staffs) {
                        $size = count($staffs);
                        $odd = $size % 2 == 0 ? false : true;
                        if ($odd) {
                            $s_b = $s_b + 1;
                        }
                        foreach ($staffs as $each) {

                            if (!in_array($each['StaffID'], $backIds)) {

                                $sheet->setCellValue($col_s . $s_b, $each->getStaffInfo());
                                //merge cell of staff
                                $pRange = $col_e . $s_b . ':' . $col_e . $s_b;
                                $sheet->getStyle($pRange)->applyFromArray($styleEArray);
                                $pRange = $col_s . $s_b . ':' . $col_s . ($s_b + 1);
                                $sheet->mergeCells($pRange);
                                $sheet->duplicateStyle($style, $pRange);
                                $sheet->getStyle($pRange)->applyFromArray($styleArray);

                                $s_b += 3;
                            }
                        }

                        // if (array_key_exists('Subleader', $team)) {
                        //     $subleaders = @$team['Subleader'];
                        //     foreach ($subleaders as $part => $subleader) {
                        //         foreach ($subleader as $each) {
                        //             $sheet->setCellValue($col_s . $s_b, $each->getStaffInfo());
                        //             $pRange = $col_e . $s_b . ':' . $col_e . $s_b;
                        //             $sheet->getStyle($pRange)->applyFromArray($styleEArray);
                        //             $pRange = $col_s . $s_b . ':' . $col_s . ($s_b + 1);
                        //             $sheet->mergeCells($pRange);
                        //             $sheet->duplicateStyle($style, $pRange);
                        //             $sheet->getStyle($pRange)->applyFromArray($styleArray);
                        //             $s_b += 3;
                        //         }
                        //     }
                        // }

                        $leader = isset($leaders[$apart]) ? $leaders[$apart] : false;
                        if ($leader != false) {
                            $end_leader = $odd ? 1 : 2;

                            $leaderInfo = "";
                            foreach ($leader as $leaderItem) {
                                $leaderInfo .= $leaderItem->getStaffInfo() . "\r\n";
                            }
                            $sheet->setCellValue($col_l . $l_b, $leaderInfo);

                            $wrapTextCells[] = $col_l . $l_b;

                            //merge cell of leader
                            $middle = ($s_b - 1) - ceil(($s_b - $l_b) / 2);
                            $pRange = $col_c . $middle . ':' . $col_c . $middle;
                            $sheet->getStyle($pRange)->applyFromArray($styleEArray);

                            $pRange = $col_l . $l_b . ':' . $col_l . ($s_b - $end_leader);
                            $sheet->mergeCells($pRange);
                            $sheet->duplicateStyle($style, $pRange);
                            $sheet->getStyle($pRange)->applyFromArray($styleArray);

                            // Merge and insert team
                            $pRange = "B" . ($middle - 2) . ':' . "B" . ($middle + 2);
                            $sheet->getStyle($pRange)->getFont()->setSize(11);
                            $sheet->getStyle($pRange)->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB("fcd5b4");

                            $sheet->mergeCells($pRange);



                            $sheet->setCellValue("B" . ($middle - 2), $teamName);
                            //reset begin of apart
                            $bg = $s_b + 1;
                            $l_b = $bg;
                            $s_b = $bg;
                        }
                    }

                    //reset begin of team
                    $bg = $s_b + 1;
                    $l_b = $bg;
                    $s_b = $bg;
                }
            }
            // Nhi: Show trainees
            foreach ($lst as $team) {
                if (array_key_exists('Trainee', $team)) {
                    $trainees = $team['Trainee'];
                    foreach ($trainees as $id => $trainee) {
                        $size = count($trainee);
                        $odd = $size % 2 == 0 ? false : true;
                        if ($odd) {
                            $s_b = $s_b;
                        }
                        foreach ($trainee as $each) {
                            $sheet->setCellValue($col_s . $s_b, $each->getStaffInfo());
                            //merge cell of staff
                            $pRange = $col_e . $s_b . ':' . $col_e . $s_b;
                            $sheet->getStyle($pRange)->applyFromArray($styleEArray);
                            $pRange = $col_s . $s_b . ':' . $col_s . ($s_b + 1);
                            $sheet->mergeCells($pRange);
                            $sheet->duplicateStyle($style, $pRange);
                            $sheet->getStyle($pRange)->applyFromArray($styleArray);
                            $s_b += 3;
                        }
                    }
                }
            }

            //format left
            for ($i = $first; $i <= $middle; $i++) {
                $pRange = $col_c . $i . ':' . $col_c . $i;
                $sheet->getStyle($pRange)->applyFromArray($styleCLArray);
            }

            // Wrap Text cho Leader
            foreach ($wrapTextCells as $value) {
                $sheet->getStyle($value)->getAlignment()->setWrapText(true);
            }

            $writer = new Xlsx($spreadsheet);
            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $date = date("Ymd");
            $fileName = "EmergencyContactList{$date}.xlsx";
            $outPut = $path . "/" . $fileName;
            $writer->save($outPut);
            $response['success'] = 1;
            $response['file'] = "excel/output/" . $fileName;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    private function getBackOfficeStaff()
    {
        $lst = TableRegistry::get('TBLMStaff3')->find()
            ->select(['TBLMStaff.StaffID', 'TBLMStaff.StaffName', 'TBLMStaff2.PhoneNumber'])
            ->contain([
                'TBLMStaff',
                'TBLMStaff.TBLMStaff2',
            ])
            ->where(
                [
                    'TBLMStaff.StaffID LIKE' => 'H%',
                    'OR' => [
                        'TBLMStaff.QuitJobDate IS NULL',
                        'DATE(TBLMStaff.QuitJobDate) >' => date("Y-m-d"),
                    ],
                    'TBLMStaff3.Position' => 'Staff',
                ]
            )
            ->order([
                'TBLMStaff3.StaffID',
            ]);
        $rst = [];
        foreach ($lst as $key => $each) {
            $rst[$key] = array(
                'StaffID' => $each->TBLMStaff['StaffID'],
                'StaffName' => $each->TBLMStaff['StaffName'],
                'PhoneNumber' => $each->TBLMStaff->TBLMStaff2['PhoneNumber'],
            );
        }
        return $rst;
    }

    public static function makeExportOvertime($staffId, $dateFrom, $dateTo)
    {
        $data_overtime['error'] = '';
        // $response = ['success' => 0, 'error' => ''];

        if (empty($staffId) || empty($dateFrom) || empty($dateTo)) {
            // $response['error'] = "Required!";
            // return $response;
            $data_overtime['error'] = "Required!";
        }
        $dateFrom = date("Y-m-d", strtotime($dateFrom));
        $dateTo = date("Y-m-d", strtotime($dateTo));
        try {
            $fileName = "Overtime.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($input);
            $sheet = $spreadsheet->getActiveSheet();

            $staff = TableRegistry::get('TBLMStaff')
                ->find()
                ->select([
                    'TBLMStaff.StaffID',
                    'TBLMStaff.StaffName',
                ])
                ->where(['TBLMStaff.StaffID' => $staffId])
                ->first();

            $overtimes = TableRegistry::get('OTApply')
                ->find()
                ->select([
                    'OTApply.Date',
                    'OTApply.Shift',
                    'OTApply.FromTime',
                    'OTApply.ToTime',
                    'OTApply.Total',
                    'OTApply.LeaderID',
                    // 'TBLMStaff.StaffName',
                    'OTApply.DateApproved',
                ])
                ->where(['OTApply.StaffID' => $staffId, 'DATE(OTApply.Date) >=' => $dateFrom, 'DATE(OTApply.Date) <=' => $dateTo])
                ->where(['OR' => ['LeaderApproved !=' => 0, 'LeaderApproved IS NULL']])
                ->order(['OTApply.Date' => 'ASC'])
                ->all();

            // Write StaffID,StaffName
            $sheet->setCellValue("A3", "Staff ID: " . $staff->StaffID);
            $sheet->setCellValue("K3", "Staff Name: " . strtoupper($staff->StaffName));
            $data_info_overtime['StaffID'] = $staff->StaffID;
            $data_info_overtime['StaffName'] = strtoupper($staff->StaffName);

            $beginRow = 7;
            $shifts = [
                0 => 'B',
                1 => 'C',
                2 => 'D',
                3 => 'E',
                4 => 'F',
                8 => 'G',
                5 => 'H',
            ];

            $styleHightlightShift = array(
                'font' => array(
                    'bold' => true,
                    'underline' => true,
                    // 'color' => array('rgb' => 'ffffff'),
                )
            );
            $MHoliday = TableRegistry::getTableLocator()->get('TBLVNHoliday');
            $list_holiday = $MHoliday->find()->where(['HolidayDate >=' => $dateFrom, 'HolidayDate <=' => $dateTo])->toList();

            $data_table_overtime = [];
            foreach ($overtimes as $overtime) {
                $item = [];
                # write DATE
                $sheet->setCellValue("A" . $beginRow, Chronos::parse($overtime->Date)->format('Y/m/d'));
                $item['date'] = Chronos::parse($overtime->Date)->format('Y/m/d');

                $styleMarginCenter = array(
                    'alignment' => array(
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ),
                );
                $sheet->getStyle("A" . $beginRow . ":M" . $beginRow)->applyFromArray($styleMarginCenter);

                $sheet->setCellValue("B" . $beginRow, "0");
                $sheet->setCellValue("C" . $beginRow, "1");
                $sheet->setCellValue("D" . $beginRow, "2");
                $sheet->setCellValue("E" . $beginRow, "3");
                $sheet->setCellValue("F" . $beginRow, "4");
                $sheet->setCellValue("G" . $beginRow, "W");
                $sheet->setCellValue("H" . $beginRow, "H");
                #write from time
                $sheet->setCellValue("I" . $beginRow, Chronos::parse($overtime->FromTime)->format('H:i'));
                $item['from_time'] = Chronos::parse($overtime->FromTime)->format('H:i');
                $sheet->setCellValue("J" . $beginRow, "~");
                #write to time
                $sheet->setCellValue("K" . $beginRow, Chronos::parse($overtime->ToTime)->format('H:i'));
                $item['to_time'] = Chronos::parse($overtime->ToTime)->format('H:i');
                #write total minutes
                $sheet->setCellValue("L" . $beginRow, $overtime->Total);
                $item['total_mins'] = $overtime->Total;
                #write ID Leader
                $sheet->setCellValue("M" . $beginRow, $overtime->LeaderID);
                $item['ID_leader'] = $overtime->LeaderID;
                #write name Leader
                $leaderName = TableRegistry::get('TBLMStaff')->find()->where(['TBLMStaff.StaffID' => $overtime->LeaderID])->select('TBLMStaff.StaffName')->first();
                $sheet->setCellValue("N" . $beginRow, $leaderName->StaffName);
                $item['name_leader'] = $leaderName->StaffName;
                if (!empty($shifts[$overtime->Shift])) {
                    // $sheet->getStyle($shifts[$overtime->Shift] . $beginRow)
                    //         ->getFill()
                    //         ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    //         ->getStartColor()->setRGB("0000000");

                    #write format SHIFT
                    $sheet->getStyle($shifts[$overtime->Shift] . $beginRow)->applyFromArray($styleHightlightShift);
                    $item['value_shift'] = $overtime->Shift;
                }
                if (Excel::isHoliday($overtime->Date, $list_holiday)) {
                    #write format H
                    $sheet->getStyle($shifts[5] . $beginRow)->applyFromArray($styleHightlightShift);
                    $item['holiday_shift'] = Excel::isHoliday($overtime->Date, $list_holiday);
                }
                $beginRow++;
                array_push($data_table_overtime, $item);
            }
            // $path = $fileDir . "/output";
            // if (!file_exists($path)) {
            //     mkdir($path);
            // }

            // XXXXXXXXX
            //    $fileName = "Overtime_". $staffId. "_" .date("Y-m-d", strtotime($dateFrom)). "-". $dateTo . ".xlsx";
            //                $outPut   = $path . "/" . $fileName;

            //                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            //                $writer->save($outPut);

            // $sheet->getPageMargins()
            //     ->setLeft(0.1)
            //     ->setRight(0.1)
            //     ->setTop(0.1)
            //     ->setBottom(0.1)
            //     ->setHeader(0);
            // XXXXXXXXXX

            // $fileName = "Overtime_" . $staffId . "_" . date("Y-m-d", strtotime($dateFrom)) . "-" . $dateTo . ".pdf";
            // $outPut = $path . "/" . $fileName;

            // IOFactory::registerWriter("PDF", Dompdf::class);
            // $pdfwriter = IOFactory::createWriter($spreadsheet, 'PDF');
            // $pdfwriter->save($outPut);

            // $response['success'] = 1;
            // $response['file'] = "excel/output/" . $fileName;
            $data_overtime['info'] = $data_info_overtime;
            $data_overtime['table'] = $data_table_overtime;
        } catch (\Exception $e) {
            // $response['error'] = $e->getMessage();
            $data_overtime['error'] = $e->getMessage();
        }

        // return $response;
        return $data_overtime;
    }

    public static function makeExportShiftLeave($month, $typeDownload = 0)
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        $response = ['success' => 0, 'error' => ''];

        if (empty($month)) {
            $response['error'] = "Month is required!";
            return $response;
        }
        try {
            $fileName = "ShiftLeave.xlsm";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($input);
            $sheet = $spreadsheet->getActiveSheet();

            // Title
            $monthTitle = date("F", strtotime($month . "/01")) . "(" . date("Y/m", strtotime($month . "/01")) . ")";
            // $sheet->setCellValue("F1", $monthTitle);

            self::$rowResult[] = ["F1", $monthTitle, ""];

            $year = date("Y", strtotime($month . "/01"));
            $monthNo = date("m", strtotime($month . "/01"));
            $monthAfter = date("Y-m-01", strtotime($month . "/01 + 1 month"));

            $firstColumn = "F";
            $lastColumn = "AK";
            $firstDay = 1;
            $lastDay = 31;

            // Wirte Day Name
            $weekends = [];
            for ($column = $firstColumn; $column != $lastColumn; $column++) {
                $isWeekend = false;
                $dayName = date('D', strtotime($month . "/" . $firstDay));
                if (!checkdate($monthNo, $firstDay, $year)) {
                    self::$rowResult[] = [$column . "2", "", ""];

                    $firstDay++;
                    if ($firstDay > $lastDay) {
                        break;
                    }
                    continue;
                }
                if ($dayName == "Sat" || $dayName == "Sun") {
                    $isWeekend = true;
                    $weekends[$dayName][] = $column;
                }
                self::$rowResult[] = [$column . "3", $dayName, ""];
                if ($isWeekend) {
                    self::$rowResult[] = [$column . '2', "FILL_COLOR", "rgb(221, 221, 221)"];
                    self::$rowResult[] = [$column . '3', "FILL_COLOR", "rgb(221, 221, 221)"];
                }

                $firstDay++;
                if ($firstDay > $lastDay) {
                    break;
                }
            }

            $shifts = self::getShiftsByMonth($month);

            $no = 0;
            $row = 4;
            $beforeUser = "";
            $dataShift = [];
            $k = 0;
            for ($column = $firstColumn; $column != $lastColumn; $column++) {
                $k++;
                $dataShift[$k] = $column;
            }

            $currentUser = $shifts->first()->StaffID;
            $seisakuData = [];
            $madoguchiData = [];
            $hrData = [];

            foreach ($shifts as $shift) {
                if ($currentUser != $shift->StaffID) {
                    $currentUser = $shift->StaffID;
                    $no++;
                    $row++;
                }
                if (strpos($shift->StaffID, 'A') !== false) {
                    self::makeDataShiftLeave($shift, "A", $dataShift, $seisakuData, $weekends);
                }

                if (strpos($shift->StaffID, 'J') !== false) {
                    self::makeDataShiftLeave($shift, "J", $dataShift, $madoguchiData, $weekends);
                }

                if (strpos($shift->StaffID, 'H') !== false) {
                    self::makeDataShiftLeave($shift, "H", $dataShift, $hrData, $weekends);
                }
            }
            $no = 1;
            $row = 4;
            $totalAl = [];

            // Write SEISAKU
            self::makeGroupTitleShiftLeave($sheet, $row, "I.SEISAKU");
            $row++;
            $beginRowSat = $row;
            $endRowSat = $row;

            TableRegistry::get('TBLTRemain')->deleteAll(['Date =' => $monthAfter]);
            foreach ($seisakuData as $item) {
                $endRowSat++;
                self::InsertToSheetShiftLeave($sheet, $no, $row, $item, $totalAl, $monthAfter);
            }
            self::drawBgSatSun($weekends, $beginRowSat, $endRowSat, $sheet);

            // Write MADOGUCHI
            self::makeGroupTitleShiftLeave($sheet, $row, "II.MADOGUCHI");
            $row++;

            $beginRowSat = $row;
            $endRowSat = $row;
            foreach ($madoguchiData as $item) {
                $endRowSat++;
                self::InsertToSheetShiftLeave($sheet, $no, $row, $item, $totalAl, $monthAfter);
            }
            self::drawBgSatSun($weekends, $beginRowSat, $endRowSat, $sheet);

            // Write HR
            self::makeGroupTitleShiftLeave($sheet, $row, "III.HR");
            $row++;
            $beginRowSat = $row;
            $endRowSat = $row;
            foreach ($hrData as $item) {
                $endRowSat++;
                self::InsertToSheetShiftLeave($sheet, $no, $row, $item, $totalAl, $monthAfter);
            }
            self::drawBgSatSun($weekends, $beginRowSat, $endRowSat, $sheet);

            foreach ($totalAl as $key => $value) {
                self::$rowResult[] = [$key . $row, $value, "rgb(158, 201, 130)"];
            }
            self::makeTitleTotal($sheet, $row, "Total");

            $row += 2;
            self::makeNote($sheet, $row);

            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $fileName = "ShiftLeave_" . date("Y-m", strtotime($month . "/01")) . ".xlsm";
            $fileNameCsv = "ShiftLeave_" . date("Y-m", strtotime($month . "/01"));
            $outPut = $path . "/" . $fileName;

            self::arrToCsv($month);
            copy($input, $outPut);

            if ($typeDownload == 1) {
                $response['success'] = 1;
                copy($path . "/" . $fileNameCsv . ".txt", $path . "/" . $fileNameCsv . ".csv");
                $response['file'] = "excel/output/" . $fileNameCsv . ".csv";
            } else {
                $response['success'] = 1;
                $response['file'] = "excel/output/" . $fileName;
            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    public static function makeExportShiftLeavePdf($month)
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        $response = ['success' => 0, 'error' => ''];

        if (empty($month)) {
            $response['error'] = "Month is required!";
            return $response;
        }
        try {
            // Title
            $monthTitle = date("F", strtotime($month . "/01")) . "(" . date("Y/m", strtotime($month . "/01")) . ")";
            self::$rowResultPdf['month_title'] = $monthTitle;
            self::$rowResultPdf['month'] = $month;

            $year = date("Y", strtotime($month . "/01"));
            $monthNo = date("m", strtotime($month . "/01"));
            $monthAfter = date("Y-m-01", strtotime($month . "/01 + 1 month"));

            $firstColumn = "F";
            $lastColumn = "AK";
            $firstDay = 1;
            $lastDay = 31;

            // Wirte Day Name
            $weekends = [];
            for ($column = $firstColumn; $column != $lastColumn; $column++) {
                $dayName = date('D', strtotime($month . "/" . $firstDay));
                if (!checkdate($monthNo, $firstDay, $year)) {
                    $firstDay++;
                    if ($firstDay > $lastDay) {
                        break;
                    }
                    continue;
                }
                if ($dayName == "Sat" || $dayName == "Sun") {
                    $weekends[$dayName][] = $column;
                }
                self::$rowResultPdf['day_name'][] = $dayName;
                $firstDay++;
                if ($firstDay > $lastDay) {
                    break;
                }
            }

            $shifts = self::getShiftsByMonth($month);
            $no = 0;
            $row = 4;
            $beforeUser = "";
            $dataShift = [];
            $k = 0;
            for ($column = $firstColumn; $column != $lastColumn; $column++) {
                $k++;
                $dataShift[$k] = $column;
            }

            $currentUser = $shifts->first()->StaffID;
            $seisakuData = [];
            $madoguchiData = [];
            $hrData = [];

            foreach ($shifts as $shift) {
                if ($currentUser != $shift->StaffID) {
                    $currentUser = $shift->StaffID;
                    $no++;
                    $row++;
                }
                if (strpos($shift->StaffID, 'A') !== false) {
                    self::makeDataShiftLeave($shift, "A", $dataShift, $seisakuData, $weekends);
                }

                if (strpos($shift->StaffID, 'J') !== false) {
                    self::makeDataShiftLeave($shift, "J", $dataShift, $madoguchiData, $weekends);
                }

                if (strpos($shift->StaffID, 'H') !== false) {
                    self::makeDataShiftLeave($shift, "H", $dataShift, $hrData, $weekends);
                }
            }
            // dd($hrData);

            $no = 1;
            $row = 4;
            $totalAl = [];

            // Write SEISAKU
            $row++;
            $beginRowSat = $row;
            $endRowSat = $row;
            $sheet = [];
            TableRegistry::get('TBLTRemain')->deleteAll(['Date =' => $monthAfter]);
            foreach ($seisakuData as $item) {
                $endRowSat++;
                self::InsertToSheetShiftLeave($sheet, $no, $row, $item, $totalAl, $monthAfter);
            }
            // Write MADOGUCHI
            $row++;
            $beginRowSat = $row;
            $endRowSat = $row;
            foreach ($madoguchiData as $item) {
                $endRowSat++;
                self::InsertToSheetShiftLeave($sheet, $no, $row, $item, $totalAl, $monthAfter);
            }
            // Write HR
            $row++;
            $beginRowSat = $row;
            $endRowSat = $row;
            foreach ($hrData as $item) {
                $endRowSat++;
                self::InsertToSheetShiftLeave($sheet, $no, $row, $item, $totalAl, $monthAfter);
            }

            foreach ($totalAl as $key => $value) {
                self::$rowResultPdf['total'][$key] = $value;
            }
            return self::$rowResultPdf;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }
        return $response;
    }

    private static function getShiftsByMonth($month)
    {
        return TableRegistry::get('TBLMStaff')
            ->find()
            ->select([
                'TBLMStaff.StaffID',
                'TBLMStaff.StaffName',
                'TBLMStaff.TrialEntryDate',
                'TBLMStaff.TrialEndDate',
                'TBLMStaff.QuitJobDate',
                'TBLTShift.Date',
                'TBLTShift.Shift',
                'TBLTALApply.FromDate',
                'TBLTALApply.ToDate',
                'TBLTALApply.Type',
                'TBLTALApply.HalfDayFlag',
                'TBLTALApply.Total',
                'TBLTRemain.Date',
                'TBLTRemain.Remain',
                'TBLSaturdayWorking.SaturdayDate',
                'TeamCode' => 'SUBSTRING(TBLMStaff3.Team,1,3)',
                'FromTeam' => 'TBLMStaff3.FromTeam',
            ])
            ->where(['OR' => ['DATE(QuitJobDate) >' => date("Y-m-01", strtotime($month . "/01")), 'QuitJobDate IS NULL']])
            ->leftJoinWith('TBLTShift', function ($q) use ($month) {
                return $q->where(['TBLTShift.Date >=' => $month . "/01", 'TBLTShift.Date <=' => date("Y-m-t", strtotime($month . "/01"))]);
            })
            ->leftJoinWith('TBLMStaff3')
            ->leftJoinWith('TBLTALApply', function ($q) use ($month) {
                return $q->where([
                    'TBLTALApply.FromDate >=' => $month . "/01",
                    'TBLTALApply.FromDate <=' => date("Y-m-t", strtotime($month . "/01")),
                    'OR' => [
                        [
                            'TBLMStaff3.FromTeam' => 'BO',
                            'TBLTALApply.BOApproval <>' => 0,
                            'TBLTALApply.BOApproval IS NOT NULL',
                        ],
                        'AND' => [
                            'TBLMStaff3.FromTeam <>' => 'BO',
                            'TBLTALApply.SLeaderApproval <>' => 0,
                            'TBLTALApply.SLeaderApproval IS NOT NULL',
                            'TBLTALApply.MLeaderApproval <>' => 0,
                            'TBLTALApply.MLeaderApproval IS NOT NULL',
                            'TBLTALApply.ShuninApproval <>' => 0,
                            'TBLTALApply.ShuninApproval IS NOT NULL',
                            'TBLTALApply.BOApproval <>' => 0,
                            'TBLTALApply.BOApproval IS NOT NULL',
                        ],
                    ],
                ]);
            })
            ->leftJoinWith('TBLSaturdayWorking', function ($q) use ($month) {
                return $q->where(['TBLSaturdayWorking.SaturdayDate >=' => $month . "/01", 'TBLSaturdayWorking.SaturdayDate <=' => date("Y-m-t", strtotime($month . "/01"))]);
            })
            ->leftJoinWith('TBLTRemain', function ($q) use ($month) {
                return $q->where(['TBLTRemain.Date =' => date("Y-m-01", strtotime($month . "/01"))]);
            })
            ->order(['TBLMStaff.StaffID' => 'ASC', 'TBLTShift.Date' => 'ASC', 'TBLTALApply.FromDate' => 'ASC'])
            ->all();
    }

    private static function drawBgSatSun($weekends, $beginRow, $endRow, &$sheet)
    {
        foreach ($weekends['Sat'] as $column) {
            self::$rowResult[] = [$column . $beginRow . ":" . $column . $endRow, "FILL_COLOR", "rgb(221, 221, 221)"];
        }
        foreach ($weekends['Sun'] as $column) {
            self::$rowResult[] = [$column . $beginRow . ":" . $column . $endRow, "FILL_COLOR", "rgb(221, 221, 221)"];
        }
    }

    private static function makeDataShiftLeave($shift, $type, $dataShift, &$cloneData, $weekends)
    {
        if (strpos($shift->StaffID, $type) !== false) {
            $shiftDay = [];
            $alDay = [];
            for ($i = 1; $i <= 31; $i++) {

                if (in_array($dataShift[$i], $weekends['Sat']) && intval(date("d", strtotime($shift->_matchingData['TBLSaturdayWorking']->SaturdayDate))) == $i && $shift->_matchingData['TBLSaturdayWorking']->SaturdayDate != null) {
                    $shiftDay[$dataShift[$i]] = "O";
                }
                if ($shift->_matchingData['TBLTShift']->Date && intval(date("d", strtotime($shift->_matchingData['TBLTShift']->Date))) == $i) {
                    if ($shift->StaffID == "H0007") {
                        $shiftDay[$dataShift[$i]] = "0";
                    } else {
                        $shiftDay[$dataShift[$i]] = $shift->_matchingData['TBLTShift']->Shift;
                        if (empty($shiftDay[$dataShift[$i]])){
                            continue;
                        }
                        //shift value = 0 can't show ( detail is 2020/07/23 ) - edit kpwzto 2020-08-11
                        if (in_array($shift->StaffID, Constants::CEO_IDS)) {
                            $shiftDay[$dataShift[$i]] = "";
                            continue;
                        }
                    }
                }
                $types = TBLTALApply::TYPES;
                if (
                    $shift->_matchingData['TBLTALApply']->FromDate &&
                    $shift->_matchingData['TBLTALApply']->ToDate &&
                    intval(date("d", strtotime($shift->_matchingData['TBLTALApply']->FromDate))) <= $i &&
                    intval(date("d", strtotime($shift->_matchingData['TBLTALApply']->ToDate))) >= $i && $shift->_matchingData['TBLTALApply']->Type && isset($types[$shift->_matchingData['TBLTALApply']->Type])
                ) {
                    $alDay[$dataShift[$i]] = [
                        'type' => $shift->_matchingData['TBLTALApply']->Type,
                        'total' => $shift->_matchingData['TBLTALApply']->Total,
                        'is_half_day' => $shift->_matchingData['TBLTALApply']->HalfDayFlag,
                        'is_weekend' => in_array($dataShift[$i], $weekends['Sat']) || in_array($dataShift[$i], $weekends['Sun'])
                    ];
                }
            }
            $data = [
                'StaffID' => $shift->StaffID,
                'StaffName' => $shift->StaffName,
                'QuitJobDate' => $shift->QuitJobDate,
                'TrialEntryDate' => date("Y/m/d", strtotime($shift->TrialEntryDate)),
                'TrialEndDate' => date("Y-m-d", strtotime($shift->TrialEndDate)),
                'ShiftDay' => isset($cloneData[$shift->StaffID]) ? array_merge($cloneData[$shift->StaffID]['ShiftDay'], $shiftDay) : $shiftDay,
                'AplicationLeaveDay' => isset($cloneData[$shift->StaffID]) ? array_merge($cloneData[$shift->StaffID]['AplicationLeaveDay'], $alDay) : $alDay,
                'Remain' => $shift->_matchingData['TBLTRemain']->Remain,
            ];
            $cloneData[$shift->StaffID] = $data;
        }
    }

    private static function InsertToSheetShiftLeave(&$sheet, &$no, &$row, $data, &$totalAl, $monthAfter)
    {
        self::$rowResult[] = ["A" . $row, $no, ""];

        self::$rowResult[] = ["B" . $row, $data['StaffID'], ""];

        self::$rowResult[] = ["C" . $row, $data['StaffName'], ""];

        self::$rowResult[] = ["D" . $row, date("Y/m/d", strtotime($data['TrialEntryDate'])), ""];

        // For PDF export
        $pdfRow = [
            'StaffID' => $data['StaffID'],
            'StaffName' => $data['StaffName'],
            'TrialEntryDate' => $data['TrialEntryDate'],
        ];

        foreach ($data['ShiftDay'] as $key => $value) {
            self::$rowResult[] = [$key . $row, $value, ""];
            $pdfRow['ShiftDay'][$key] = [
                'value' => $value,
                'color' => "#ffffff",
            ];
        }

        $totalRemainMinus = 0;

        foreach ($data['AplicationLeaveDay'] as $key => $value) {
            $types = TBLTALApply::TYPES;
            $total = !empty($value['total']) ? $value['total'] : 0;

            if ($value && isset($types[$value['type']])) {

                if (intval($value['type']) != 4) {
                    $total = $value['total'] == 0.5 ? 0.5 : 1;
                    $totalAl[$key] = isset($totalAl[$key]) ? $totalAl[$key] + $total : $total;
                }

                if (intval($value['type']) == 1) {
                    $totalRemainMinus += $total;
                }

                $cellValue = $types[$value['type']];

                if (!empty($value['is_half_day'])) {
                    $cellValue = $cellValue . "/2";
                }

                if ($cellValue != "" && $cellValue != "O" && $value['is_weekend']) {
                    self::$rowResult[] = [$key . $row, "", "rgb(221, 221, 221)"];
                    // PDF
                    $pdfRow['ShiftDay'][$key] = [
                        'value' => "",
                        'color' => "rgb(221, 221, 221)",
                    ];
                    continue;
                }
                self::$rowResult[] = [$key . $row, $cellValue, TBLTALApply::TYPE_COLORS_RGB[$value['type']]];
                // PDF
                $pdfRow['ShiftDay'][$key] = [
                    'value' => $cellValue,
                    'color' => TBLTALApply::TYPE_COLORS_RGB[$value['type']],
                ];
            }
        }

        $month = date("Y-m-01", strtotime($monthAfter . " - 1 month"));
        $chkDateTrial = date("Y-m-15", strtotime($month));
        $chkDateQuitFrom = date("Y-m-01", strtotime($month));
        $chkDateQuitTo = date("Y-m-16", strtotime($month));
        $chkTrial = (!empty($data['TrialEndDate']) && $data['TrialEndDate'] > $chkDateTrial);
        $chkQuit = (!empty($data['QuitJobDate']) && $chkDateQuitFrom <= date("Y-m-d", strtotime($data['QuitJobDate'])) && date("Y-m-d", strtotime($data['QuitJobDate'])) <= $chkDateQuitTo);

        $addRemain = ($chkQuit || $chkTrial) ? 0 : 1;

        if (date("m", strtotime($month)) == '1') {
            $data['Remain'] = $data['Remain'] >= 5 ? 5 : $data['Remain'];
        }

        //if ($data['Remain'] < 0) $data['Remain'] = 0;

        $totalRemain = ($data['Remain'] + $addRemain) - $totalRemainMinus;

        if ($data['StaffID'] == "H0001" || $data['StaffID'] == "H0002") {
            $totalRemain = $addRemain = 0;
        }

        self::$rowResult[] = ["AL" . $row, ($totalRemain), "rgb(245, 218, 129)"];

        $pdfRow['Remain'] = $totalRemain;
        $tableRemain = TableRegistry::get('TBLTRemain');
        $entity = $tableRemain->newEntity();

        $entity->Remain = $totalRemain;
        $entity->StaffID = $data['StaffID'];
        $entity->LeaveDay = $totalRemainMinus;
        $entity->Date = $monthAfter;
        $tableRemain->save($entity);

        $row++;
        $no++;

        self::$rowResultPdf['rows'][] = $pdfRow;
    }

    private static function makeGroupTitleShiftLeave(&$sheet, $row, $title)
    {
        self::$rowResult[] = ["A" . $row, $title, "rgb(158, 201, 130)"];
        self::$rowResult[] = ["A" . $row, "HORIZONTAL_LEFT", ""];
        self::$rowResult[] = ["A" . $row . ":AM" . $row, "MERGE_CELLS", ""];
    }

    private static function makeTitleTotal(&$sheet, $row, $title)
    {

        self::$rowResult[] = ["A" . $row, $title, "rgb(158, 201, 130)"];
        self::$rowResult[] = ["A" . $row, "HORIZONTAL_CENTER", ""];
        self::$rowResult[] = ["A" . $row . ":C" . $row, "MERGE_CELLS", ""];
        self::$rowResult[] = ["A" . $row . ":AM" . $row, "FILL_COLOR", "rgb(158, 201, 130)"];
    }

    private static function makeNote(&$sheet, $row)
    {

        self::$rowResult[] = ["B" . $row, "1", ""];
        self::$rowResult[] = ["B" . ($row + 1), "2", ""];
        self::$rowResult[] = ["B" . ($row + 2), "3", ""];
        self::$rowResult[] = ["B" . ($row + 3), "4", ""];
        self::$rowResult[] = ["B" . ($row + 4), "0", ""];
        self::$rowResult[] = ["B" . ($row + 5), "O", ""];

        self::$rowResult[] = ["C" . $row, "Shift 1 (07:00 ~ 16:00)", ""];
        self::$rowResult[] = ["C" . ($row + 1), "Shift 2 (10:00 ~ 19:00)", ""];
        self::$rowResult[] = ["C" . ($row + 2), "Shift 3 (12:00 ~ 21:00)", ""];
        self::$rowResult[] = ["C" . ($row + 3), "3ka (07:30 ~ 16:30)", ""];
        self::$rowResult[] = ["C" . ($row + 4), "H0007 (08:00 ~ 17:00)", ""];
        self::$rowResult[] = ["C" . ($row + 5), "Saturday working", ""];

        self::$rowResult[] = ["D" . $row, "AL", TBLTALApply::TYPE_COLORS_RGB[1]];
        self::$rowResult[] = ["D" . ($row + 1), "UL", TBLTALApply::TYPE_COLORS_RGB[4]];
        self::$rowResult[] = ["D" . ($row + 2), "PL", TBLTALApply::TYPE_COLORS_RGB[3]];
        self::$rowResult[] = ["D" . ($row + 3), "AL+", TBLTALApply::TYPE_COLORS_RGB[2]];
        self::$rowResult[] = ["D" . ($row + 4), "RD", TBLTALApply::TYPE_COLORS_RGB[5]];

        self::$rowResult[] = ["H" . $row, "Annual leave", ""];
        self::$rowResult[] = ["H" . ($row + 1), "Unpaid leave", ""];
        self::$rowResult[] = ["H" . ($row + 2), "Paid leave", ""];
        self::$rowResult[] = ["H" . ($row + 3), " + Annual leave", ""];
        self::$rowResult[] = ["H" . ($row + 4), "Rostered Day off", ""];
    }

    public static function makeApplicationLeave($staffId, $date)
    {
        $response = ['success' => 0, 'error' => ''];

        $staff = TableRegistry::get('TBLMStaff')
            ->find()
            ->where(['StaffID' => $staffId])
            ->first();

        if (!$staff || !$staff->StaffID) {
            $response['error'] = "Staff ID " . $staffId . " not found";

            return $response;
        }

        if (!$date || strlen($date) != 7) {
            $response['error'] = "Please input correct month data";

            return $response;
        }

        $conditions = ['StaffID' => $staffId];

        $parts = explode("/", $date);
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $conditions[] = '(YEAR(FromDate) = ' . $year . ' AND MONTH(FromDate) = ' . $month . ')';

        $applications = TableRegistry::get('TBLTALApply')
            ->find()
            ->where($conditions)
            ->all();

        if (!count($applications)) {
            $response['error'] = "No record found";

            return $response;
        }

        try {
            $fileName = "02. Application Form - AL.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $spreadsheet = IOFactory::load($input);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('C2', $staffId);
            $sheet->setCellValue('C3', $staff->StaffName);
            $sheet->setCellValue('O3', date('Y/m'));

            $department = $staff->getDepartment();
            $sheet->setCellValue('G2', $department);

            $types = TBLTALApply::TYPES;
            $availableHoursPl = TBLTALApply::AVAILABLE_HOURSPL;
            $row = 8;
            foreach ($applications as $application) {
                if ($date = $application->FromDate) {
                    if (is_string($date)) {
                        $date = str_replace("-", "/", $date);
                    } else {
                        $date = $date->format('Y/m/d');
                    }
                    $sheet->setCellValue("A" . $row, $date);
                }

                if ($date = $application->ToDate) {
                    if (is_string($date)) {
                        $date = str_replace("-", "/", $date);
                    } else {
                        $date = $date->format('Y/m/d');
                    }
                    $sheet->setCellValue("B" . $row, $date);
                }

                $hour = $application->FromTime . ':00';
                if ($application->FromTime > 24 && isset($availableHoursPl[$application->FromTime])) {
                    $hour = $availableHoursPl[$application->FromTime];
                }
                $sheet->setCellValue("C" . $row, $hour);

                $hour = $application->ToTime . ':00';
                if ($application->ToTime > 24 && isset($availableHoursPl[$application->ToTime])) {
                    $hour = $availableHoursPl[$application->ToTime];
                }
                $sheet->setCellValue("D" . $row, $hour);

                $sheet->setCellValue("E" . $row, $application->Total);

                $type = $application->Type;
                if (isset($types[$type])) {
                    $type = $types[$type];
                }
                $sheet->setCellValue("F" . $row, $type);
                $plReasonTypes = \App\Model\Entity\TBLTALApply::REASON_TYPES;
                $application->Reason = (isset($plReasonTypes[$application->Reason])) ? $plReasonTypes[$application->Reason] : $application->Reason;
                $sheet->setCellValue("G" . $row, $application->Reason);

                if ($date = $application->DateSubmitted) {
                    if (is_string($date)) {
                        $date = str_replace("-", "/", $date);
                    } else {
                        $date = $date->format('Y/m/d');
                    }
                }
                $sheet->setCellValue("H" . $row, $date);
                $sheet->setCellValue("I" . $row, $application->MLeaderID);
                $sheet->setCellValue("J" . $row, $application->MLeaderName);
                $sheet->setCellValue("K" . $row, $application->SLeaderID);
                $sheet->setCellValue("L" . $row, $application->SLeaderName);
                $sheet->setCellValue("M" . $row, $application->ShuninID);
                $sheet->setCellValue("N" . $row, $application->ShuninName);
                $sheet->setCellValue("O" . $row, $application->BOID);
                $sheet->setCellValue("P" . $row, $application->BOName);

                /*---DANDEV add 2019-10-31---*/
                $memo = '';
                if ($application->MLeaderApproval == 0 && !empty($application->MLeaderID)) {
                    $memo .= 'Rejected by ' . $application->MLeaderID . "\n";
                    if (!empty($application->MLeaderReasonReject)) {
                        $memo .= $application->MLeaderReasonReject . "\n";
                    }
                }
                if ($application->SLeaderApproval == 0 && !empty($application->SLeaderID)) {
                    $memo .= 'Rejected by ' . $application->SLeaderID . "\n";
                    if (!empty($application->SLeaderReasonReject)) {
                        $memo .= $application->SLeaderReasonReject . "\n";
                    }
                }
                if ($application->ShuninApproval == 0 && !empty($application->ShuninID)) {
                    $memo .= 'Rejected by ' . $application->ShuninID . "\n";
                    if (!empty($application->ShuninReasonReject)) {
                        $memo .= $application->ShuninReasonReject . "\n";
                    }
                }
                if ($application->BOApproval == 0 && !empty($application->BOID)) {
                    $memo .= 'Rejected by ' . $application->BOID . "\n";
                    if (!empty($application->BOReasonReject)) {
                        $memo .= $application->BOReasonReject . "\n";
                    }
                }

                $sheet->setCellValue("Q" . $row, $memo);
                $sheet->getStyle("Q" . $row)->getAlignment()->setWrapText(true);
                /*---/DANDEV---*/
                $row++;
            }

            $writer = new Xlsx($spreadsheet);
            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }

            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageMargins()
                ->setLeft(0.1)
                ->setRight(0.1)
                ->setTop(0.1)
                ->setBottom(0.1)
                ->setHeader(0);

            $fileName = "AL-" . $staffId . "-" . date('Ymd') . ".pdf";
            $outPut = $path . "/" . $fileName;

            IOFactory::registerWriter("PDF", Mpdf::class);
            $pdfwriter = IOFactory::createWriter($spreadsheet, 'PDF');
            $pdfwriter->save($outPut);

            $response['success'] = 1;
            $response['file'] = "excel/output/" . $fileName;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    public static function makeExportStaffInfo()
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        $response = ['success' => 0, 'error' => ''];
        try {
            $fileName = "StaffInfo.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($input);
            $sheet = $spreadsheet->getActiveSheet();

            $staffList = TableRegistry::get('TBLMStaff')
                ->find()
                ->select([
                    'TBLMStaff.StaffID',
                    'TBLMStaff.StaffName',
                    'TBLMStaff.Birthday',
                    'TBLMStaff.TrialEntryDate',
                    'TBLMStaff.QuitJobDate',
                    'TBLMStaff2.Email',
                    'TBLMStaff2.PhoneNumber',
                    'TBLMStaff2.VCBAccount',
                    'TBLMStaff2.Gender',
                    'TBLMStaff2.IDCNumber',
                    'TBLMStaff2.IDCIssuedDate',
                    'TBLMStaff2.IDCIssuedPlace',
                    'TBLMStaff2.Domicile',
                    'TBLMStaff2.PermanentAdd',
                    'TBLMStaff2.ResidentalAdd',
                    'TBLMStaff2.SIBNumber',
                    'TBLMStaff2.SIBIssuedDate',
                    'TBLMStaff3.Team',
                    'TBLMStaff3.Position',
                ])
                ->contain([
                    'TBLMStaff2',
                    'TBLMStaff3',
                ])
                ->where('TBLMStaff.QuitJobDate IS NULL')
                ->all();

            $staffResignation = TableRegistry::get('TBLMStaff')
                ->find()
                ->select([
                    'TBLMStaff.StaffID',
                    'TBLMStaff.StaffName',
                    'TBLMStaff.Birthday',
                    'TBLMStaff.TrialEntryDate',
                    'TBLMStaff.QuitJobDate',
                    'TBLMStaff2.Email',
                    'TBLMStaff2.PhoneNumber',
                    'TBLMStaff2.VCBAccount',
                    'TBLMStaff2.Gender',
                    'TBLMStaff2.IDCNumber',
                    'TBLMStaff2.IDCIssuedDate',
                    'TBLMStaff2.IDCIssuedPlace',
                    'TBLMStaff2.Domicile',
                    'TBLMStaff2.PermanentAdd',
                    'TBLMStaff2.ResidentalAdd',
                    'TBLMStaff2.SIBNumber',
                    'TBLMStaff2.SIBIssuedDate',
                    'TBLMStaff3.Team',
                    'TBLMStaff3.Position',
                ])
                ->contain([
                    'TBLMStaff2',
                    'TBLMStaff3',
                ])
                ->where('TBLMStaff.QuitJobDate IS NOT NULL')
                ->all();

            $row = 5;
            $odd = true;
            $styleArray = [
                'font' => [
                    'size' => 11,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];
            $styleMarginCenter = array(
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ),
            );

            foreach ($staffList as $key => $staff) {
                $sheet->setCellValue("A" . $row, ($key + 1));
                $sheet->setCellValue("B" . $row, $staff->StaffID);
                $sheet->setCellValue("C" . $row, $staff->StaffName);
                // $sheet->setCellValue("D" . $row, $staff->QuitJobDate); # Resignation Date
                $sheet->setCellValue("E" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->Email : "");
                $sheet->setCellValue("F" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->PhoneNumber : "");
                $sheet->setCellValue("G" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->VCBAccount : "");
                $sheet->setCellValue("H" . $row, $staff->TBLMStaff2 ? ($staff->TBLMStaff2->Gender == 1 ? "Female" : "Male") : "");
                $sheet->setCellValue("I" . $row, $staff->TBLMStaff3 ? $staff->TBLMStaff3->Position : "");
                $sheet->setCellValue("J" . $row, $staff->TrialEntryDate ? date("Y/m/d", strtotime($staff->TrialEntryDate)) : "");
                $sheet->setCellValue("K" . $row, $staff->TrialEntryDate ? date("Y/m/d", strtotime($staff->TrialEntryDate . " +2 months")) : "");
                $sheet->setCellValue("L" . $row, $staff->Birthday ? date("Y/m/d", strtotime($staff->Birthday)) : "");
                $sheet->setCellValue("M" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->IDCNumber : "");
                $sheet->setCellValue("N" . $row, $staff->TBLMStaff2 && $staff->TBLMStaff2->IDCIssuedDate ? date("Y/m/d", strtotime($staff->TBLMStaff2->IDCIssuedDate)) : "");
                $sheet->setCellValue("O" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->IDCIssuedPlace : "");
                $sheet->setCellValue("P" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->Domicile : "");
                $sheet->setCellValue("Q" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->PermanentAdd : "");
                $sheet->setCellValue("R" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->ResidentalAdd : "");
                $sheet->setCellValue("S" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->SIBNumber : "");
                $sheet->setCellValue("T" . $row, $staff->TBLMStaff2 && $staff->TBLMStaff2->SIBIssuedDate ? date("Y/m/d", strtotime($staff->TBLMStaff2->SIBIssuedDate)) : "");

                $sheet->getStyle("A" . $row . ":T" . $row)->applyFromArray($styleArray);
                if ($odd) {
                    $sheet->getStyle("A" . $row . ":T" . $row)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB("bfdbab");
                }
                $odd = !$odd;
                $row++;
            }

            foreach ($staffResignation as $key => $staff) {
                $sheet->setCellValue("A" . $row, ($key + 1));
                $sheet->setCellValue("B" . $row, $staff->StaffID);
                $sheet->setCellValue("C" . $row, $staff->StaffName);
                $sheet->setCellValue("D" . $row, date("Y/m/d", strtotime($staff->QuitJobDate))); # Resignation Date
                $sheet->setCellValue("E" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->Email : "");
                $sheet->setCellValue("F" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->PhoneNumber : "");
                $sheet->setCellValue("G" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->VCBAccount : "");
                $sheet->setCellValue("H" . $row, $staff->TBLMStaff2 ? ($staff->TBLMStaff2->Gender == 1 ? "Female" : "Male") : "");
                $sheet->setCellValue("I" . $row, $staff->TBLMStaff3 ? $staff->TBLMStaff3->Position : "");
                $sheet->setCellValue("J" . $row, $staff->TrialEntryDate ? date("Y/m/d", strtotime($staff->TrialEntryDate)) : "");
                $sheet->setCellValue("K" . $row, $staff->TrialEntryDate ? date("Y/m/d", strtotime($staff->TrialEntryDate . " +2 months")) : "");
                $sheet->setCellValue("L" . $row, $staff->Birthday ? date("Y/m/d", strtotime($staff->Birthday)) : "");
                $sheet->setCellValue("M" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->IDCNumber : "");
                $sheet->setCellValue("N" . $row, $staff->TBLMStaff2 && $staff->TBLMStaff2->IDCIssuedDate ? date("Y/m/d", strtotime($staff->TBLMStaff2->IDCIssuedDate)) : "");
                $sheet->setCellValue("O" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->IDCIssuedPlace : "");
                $sheet->setCellValue("P" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->Domicile : "");
                $sheet->setCellValue("Q" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->PermanentAdd : "");
                $sheet->setCellValue("R" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->ResidentalAdd : "");
                $sheet->setCellValue("S" . $row, $staff->TBLMStaff2 ? $staff->TBLMStaff2->SIBNumber : "");
                $sheet->setCellValue("T" . $row, $staff->TBLMStaff2 && $staff->TBLMStaff2->SIBIssuedDate ? date("Y/m/d", strtotime($staff->TBLMStaff2->SIBIssuedDate)) : "");

                $sheet->getStyle("A" . $row . ":T" . $row)->applyFromArray($styleArray);
                $sheet->getStyle("A" . $row . ":T" . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB("88BEDC");
                $row++;
            }

            // Margin Center
            $sheet->getStyle("A")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("B")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("D")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("F")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("G")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("H")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("J")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("K")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("L")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("M")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("N")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("S")->applyFromArray($styleMarginCenter);
            $sheet->getStyle("T")->applyFromArray($styleMarginCenter);

            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $fileName = "StaffInfo_" . date("Y-m-d") . ".xlsx";
            $outPut = $path . "/" . $fileName;
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outPut);
            $response['success'] = 1;
            $response['file'] = "excel/output/" . $fileName;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
    public function makeExportOvertimeAll($date)
    {
        $dateFrom = date('Y-m-01', strtotime($date));
        $dateTo = date('Y-m-t', strtotime($date));
        $month = date('m', strtotime($date));
        $lst = TableRegistry::get('TBLMStaff')
            ->find()
            ->select([
                'StaffID' => 'TBLMStaff.StaffID',
                'StaffName' => 'TBLMStaff.StaffName',
                'StaffTeam' => 'TBLMStaff3.JLPTCertification',
            ])
            ->join([

                'TBLMStaff3' => [
                    'table' => 'tblMStaff3',
                    'type' => 'LEFT',
                    'conditions' => 'TBLMStaff3.StaffID = TBLMStaff.StaffID',
                ],
            ])
            ->where(['OR' => ['DATE(TBLMStaff.QuitJobDate) >' => $dateFrom, 'TBLMStaff.QuitJobDate IS NULL']])
            ->order([
                'TBLMStaff.StaffID',
            ]);
        $list_export = [];
        $list_export['SEISAKU'] = [];
        $list_export['N1'] = [];
        $list_export['N2'] = [];
        $list_export['N3'] = [];
        $list_holiday = TableRegistry::get('TBLVNHoliday')->find()->where(['HolidayDate >=' => $dateFrom, 'HolidayDate <=' => $dateTo])->toList();
        foreach ($lst as $key => $value) {

            $row = [];
            $row['StaffID'] = $value['StaffID'];
            $row['StaffName'] = $value['StaffName'];
            $row['ListTime'] = [];
            $conds = [
                "OTApply.LeaderApproved" => 1,
                'DATE(OTApply.Date) >=' => $dateFrom,
                'DATE(OTApply.Date) <=' => $dateTo,
                'OTApply.StaffID' => $row['StaffID'],
            ];
            $overtimes = TableRegistry::get('OTApply')
                ->find()
                ->select([
                    'Date' => 'OTApply.Date',
                    'FromTime' => 'OTApply.FromTime',
                    'ToTime' => 'OTApply.ToTime',
                    'Total' => 'OTApply.Total',
                ])
                ->where($conds)
                ->order(['OTApply.Date' => 'ASC'])
                ->all();
            $row['After10_210'] = 0;
            $row['Before10_150'] = 0;
            $row['Sum_210_150'] = 0;
            $row['Weekend'] = 0;
            $row['Sum'] = 0;
            $row['Holiday_300'] = 0;
            $row['Holiday_900'] = 0;
            foreach ($overtimes as $k => $v) {
                $to = $v['ToTime'];
                $from = $v['FromTime'];
                $befor_10 = 0;
                $after_10 = 0;
                $is_after10 = false;
                if ((date('H', strtotime($to)) >= 22 && date('i', strtotime($to)) >= 1) || date('H', strtotime($to)) >= 23) {
                    $befor_10 = abs(strtotime("22:00") - strtotime($from)) / 60;
                    $after_10 = abs(strtotime($to) - strtotime('22:00')) / 60;
                    $is_after10 = true;
                } else {
                    $befor_10 = $v['Total'];
                    $after_10 = 0;
                }

                if (Excel::isHoliday($v['Date'], $list_holiday)) {
                    $row['Holiday_900'] += $after_10;
                    $row['Holiday_300'] += $befor_10;
                } else if (date('w', strtotime($v['Date'])) == 0 || date('w', strtotime($v['Date'])) == 6) {
                    $row['Weekend'] += $v['Total'];
                } else {
                    $row['After10_210'] += $after_10;
                    $row['Before10_150'] += $befor_10;
                }

                $index_date = date('d', strtotime($v['Date']));
                $old_value = (isset($row['ListTime'][(int) $index_date]['value'])) ? $row['ListTime'][(int) $index_date]['value'] : 0;
                $is_after_old = (isset($row['ListTime'][(int) $index_date]['is_after10']) && $row['ListTime'][(int) $index_date]['is_after10'] == true) ? true : $is_after10;
                $row['ListTime'][(int) $index_date] = ['value' => $v['Total'] + $old_value, 'is_after10' => $is_after_old];
            }

            $row['Sum_210_150'] = $row['After10_210'] + $row['Before10_150'];
            $row['Sum'] = $row['Sum_210_150'] + $row['Weekend'];
            if (strpos($value['StaffID'], 'A') !== false) {
                $list_export['SEISAKU'][] = $row;
            }
            if (strpos($value['StaffID'], 'J') !== false && $value['StaffTeam'] == 'N1') {
                $list_export['N1'][] = $row;
            }
            if (strpos($value['StaffID'], 'J') !== false && $value['StaffTeam'] == 'N2') {
                $list_export['N2'][] = $row;
            }
            if (strpos($value['StaffID'], 'J') !== false && $value['StaffTeam'] == 'N3') {
                $list_export['N3'][] = $row;
            }
        }
        $response = ['success' => 0, 'error' => ''];
        try {
            $fileName = "Overtime_all.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($input);
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->setTitle(date('Y-m', strtotime($date)));
            $sheet->getRowDimension('1')->setRowHeight(15);

            $list_Group = [
                'SEISAKU' => 'I. SEISAKU',
                'N3' => 'II. MADOGUCHI - N3',
                'N2' => 'III. MADOGUCHI - N2',
                'N1' => 'IV. MADOGUCHI - N1',
            ];
            $list_date_in_month = [];
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            for ($i = 1; $i <= date("t", strtotime($year . '-' . $month . '-01')); $i++) {
                $list_date_in_month[$i] = substr(date('D', strtotime($year . '-' . $month . '-' . $i)), 0, -1);
            }
            $row = 3;
            $total_staff = count($lst->toArray());
            $list_Cell_month = [
                'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W',
                'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ'
            ];
            $styleRedColor = array(
                'font' => array(
                    'color' => array('rgb' => 'fc0320'),
                ),
            );
            $styleBlackColor = array(
                'font' => array(
                    'color' => array('rgb' => '080808'),
                ),
            );
            $styleMarginCenter = array(
                'alignment' => array(
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ),
            );
            foreach ($list_Cell_month as $key => $value) {
                $sheet->getRowDimension($row)->setRowHeight(15);
                $sheet->setCellValue($value . $row, isset($list_date_in_month[$key + 1]) ? $list_date_in_month[$key + 1] : '');
            }
            $row = 4;
            $total_after_10 = 0;
            $total_before_10 = 0;
            $total_before_After = 0;
            $total_weekend = 0;
            $total_AO = 0;
            $total_holiday_300 = 0;
            $total_holiday_900 = 0;
            $total_each_date = [];
            foreach ($list_Cell_month as $k1 => $v1) {
                $total_each_date[$k1 + 1] = 0;
            }
            foreach ($list_export as $key => $value) {
                $sheet->setCellValue("A" . $row, $list_Group[$key]);
                $sheet->getRowDimension($row)->setRowHeight(15);
                $sheet->getStyle("A" . $row)->getFont()->setBold(true);
                $sheet->getStyle("A" . $row . ":AQ" . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('fcfcfc');
                $row++;
                $sheet->getRowDimension($row)->setRowHeight(15);
                $index = 1;
                $sum_after_10 = 0;
                $sum_before_10 = 0;
                $sum_before_After = 0;
                $sum_weekend = 0;
                $sum_AO = 0;
                $sum_holiday_300 = 0;
                $sum_holiday_900 = 0;
                $sum_each_date = [];
                foreach ($list_Cell_month as $k1 => $v1) {
                    $sum_each_date[$k1 + 1] = 0;
                }

                foreach ($value as $k => $v) {
                    $sheet->setCellValue("A" . $row, $index);
                    $sheet->setCellValue("B" . $row, $v['StaffID']);
                    $sheet->setCellValue("C" . $row, $v['StaffName']);
                    //Show total of date in month
                    foreach ($list_Cell_month as $k1 => $v1) {
                        if (isset($list_date_in_month[$k1 + 1]) && ($list_date_in_month[$k1 + 1] == 'Sa' || $list_date_in_month[$k1 + 1] == 'Su')) {
                            $sheet->getStyle($v1 . $row)
                                ->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('dedad9');
                        } else {
                            $sheet->getStyle($v1 . $row)
                                ->getFill()
                                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                                ->getStartColor()->setRGB('fcfcfc');
                        }
                        if (isset($v['ListTime'][$k1 + 1]['value']) && $v['ListTime'][$k1 + 1]['is_after10']) {
                            $sheet->getStyle($v1 . $row)
                                ->applyFromArray($styleRedColor);
                        }
                        $sheet->setCellValue($v1 . $row, isset($v['ListTime'][$k1 + 1]['value']) ? $v['ListTime'][$k1 + 1]['value'] : "");
                        $sum_each_date[$k1 + 1] += isset($v['ListTime'][$k1 + 1]['value']) ? $v['ListTime'][$k1 + 1]['value'] : 0;
                        $total_each_date[$k1 + 1] += isset($v['ListTime'][$k1 + 1]['value']) ? $v['ListTime'][$k1 + 1]['value'] : 0;
                    }
                    $sheet->getStyle("C" . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

                    $sheet->setCellValue("AK" . $row, $v['After10_210'] ? $v['After10_210'] : 0);
                    $sheet->setCellValue("AL" . $row, $v['Before10_150'] ? $v['Before10_150'] : 0);
                    $sheet->setCellValue("AM" . $row, $v['Sum_210_150'] ? $v['Sum_210_150'] : 0);
                    $sheet->setCellValue("AN" . $row, $v['Weekend'] ? $v['Weekend'] : 0);
                    $sheet->setCellValue("AO" . $row, $v['Sum'] ? $v['Sum'] : 0);
                    $sheet->setCellValue("AP" . $row, $v['Holiday_300'] ? $v['Holiday_300'] : '');
                    $sheet->setCellValue("AQ" . $row, $v['Holiday_900'] ? $v['Holiday_900'] : '');

                    $sum_after_10 += $v['After10_210'] ? $v['After10_210'] : 0;
                    $sum_before_10 += $v['Before10_150'] ? $v['Before10_150'] : 0;
                    $sum_before_After += $v['Sum_210_150'] ? $v['Sum_210_150'] : 0;
                    $sum_weekend += $v['Weekend'] ? $v['Weekend'] : 0;
                    $sum_AO += $v['Sum'] ? $v['Sum'] : 0;
                    $sum_holiday_300 += $v['Holiday_300'] ? $v['Holiday_300'] : 0;
                    $sum_holiday_900 += $v['Holiday_900'] ? $v['Holiday_900'] : 0;

                    $total_after_10 += $v['After10_210'] ? $v['After10_210'] : 0;
                    $total_before_10 += $v['Before10_150'] ? $v['Before10_150'] : 0;
                    $total_before_After += $v['Sum_210_150'] ? $v['Sum_210_150'] : 0;
                    $total_weekend += $v['Weekend'] ? $v['Weekend'] : 0;
                    $total_AO += $v['Sum'] ? $v['Sum'] : 0;
                    $total_holiday_300 += $v['Holiday_300'] ? $v['Holiday_300'] : 0;
                    $total_holiday_900 += $v['Holiday_900'] ? $v['Holiday_900'] : 0;

                    $sheet->getStyle("AQ" . $row)->applyFromArray($styleRedColor);
                    $sheet->getRowDimension($row)->setRowHeight(15);
                    $index++;
                    $row++;
                }
                $sheet->setCellValue("A" . $row, 'SUM');
                $sheet->getRowDimension($row)->setRowHeight(15);
                foreach ($list_Cell_month as $k1 => $v1) {
                    $sheet->setCellValue($v1 . $row, $sum_each_date[$k1 + 1]);
                    $sheet->getStyle($v1 . $row)->getFont()->setBold(true);
                    $sheet->getStyle($v1 . $row)->applyFromArray($styleBlackColor);
                }

                $sheet->setCellValue("AK" . $row, $sum_after_10);
                $sheet->setCellValue("AL" . $row, $sum_before_10);
                $sheet->setCellValue("AM" . $row, $sum_before_After);
                $sheet->setCellValue("AN" . $row, $sum_weekend);
                $sheet->setCellValue("AO" . $row, $sum_AO);
                $sheet->setCellValue("AP" . $row, $sum_holiday_300);
                $sheet->setCellValue("AQ" . $row, $sum_holiday_900);

                $sheet->getStyle("A" . $row . ":AQ" . $row)
                    ->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('5bba75');
                $sheet->getStyle("A" . $row . ":AQ" . $row)->getFont()->setBold(true);
                $sheet->getRowDimension($row)->setRowHeight(15);
                $row++;
            }
            $sheet->setCellValue("A" . $row, 'TOTAL');

            foreach ($list_Cell_month as $k1 => $v1) {
                $sheet->setCellValue($v1 . $row, $total_each_date[$k1 + 1]);
                $sheet->getStyle($v1 . $row)->getFont()->setBold(true);
                $sheet->getStyle($v1 . $row)->applyFromArray($styleBlackColor);
            }
            $sheet->setCellValue("AK" . $row, $total_after_10);
            $sheet->setCellValue("AL" . $row, $total_before_10);
            $sheet->setCellValue("AM" . $row, $total_before_After);
            $sheet->setCellValue("AN" . $row, $total_weekend);
            $sheet->setCellValue("AO" . $row, $total_AO);
            $sheet->setCellValue("AP" . $row, $total_holiday_300);
            $sheet->setCellValue("AQ" . $row, $total_holiday_900);

            $sheet->getStyle("A" . $row . ":AQ" . $row)
                ->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1f6e34');
            $sheet->getStyle("A" . $row . ":AQ" . $row)->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(15);

            $sheet->getStyle("AK4:AK" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $sheet->getStyle("AL4:AL" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $sheet->getStyle("AM4:AM" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $sheet->getStyle("AN4:AN" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $sheet->getStyle("AO4:AO" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $sheet->getStyle("AP4:AP" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $sheet->getStyle("AQ4:AQ" . ($total_staff + 3))->applyFromArray($styleMarginCenter);
            $styleEArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000'],
                    ],
                ],
            ];
            $sheet->getStyle('A4:AQ' . $row)->applyFromArray($styleEArray);
            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $fileName = date("Ymd") . "_OT_All_" . date("Y-m", strtotime($date)) . ".xlsx";
            $outPut = $path . "/" . $fileName;
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outPut);
            $response['success'] = 1;
            $response['file'] = "excel/output/" . date("Ymd") . "_OT_All_" . date("Y-m", strtotime($date)) . ".xlsx";
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    public static function makeExportOvertimeAllPDF($date, $tblTOTApply)
    {
        $dateFrom = date('Y-m-01', strtotime($date));
        $dateTo = date('Y-m-t', strtotime($date));
        $conds = [
            //'TBLMStaff.QuitJobDate IS NULL',
            "OTApply.LeaderApproved" => 1,
            'DATE(OTApply.Date) >=' => $dateFrom,
            'DATE(OTApply.Date) <=' => $dateTo,
        ];
        $overtimes = TableRegistry::get('OTApply')
            ->find()
            ->select([
                'Date' => 'OTApply.Date',
                'FromTime' => 'OTApply.FromTime',
                'ToTime' => 'OTApply.ToTime',
                'Total' => 'OTApply.Total',
                'Team' => 'TBLMStaff3.Team',
                'StaffID' => 'TBLMStaff3.StaffID',
            ])
            ->join([
                'TBLMStaff3' => [
                    'table' => 'tblMStaff3',
                    'type' => 'LEFT',
                    'conditions' => 'TBLMStaff3.StaffID = OTApply.StaffID',
                ],
            ])
            ->where($conds)
            ->order(['OTApply.Date' => 'ASC'])
            ->all();
        $rst = [];
        foreach ($overtimes as $overtime) {
            $f_char = substr($overtime['StaffID'], 0, 1);
            $t_char = substr($overtime['Team'], 0, 3);

            $jdate = date('j', strtotime($overtime['Date']));
            $wdate = date('Y-m-d', strtotime($overtime['Date']));
            $idx = strtolower("{$f_char}{$t_char}");
            $sql_total = empty($overtime['Total']) ? 0 : $overtime['Total'];
            $total = isset($rst[$idx][$jdate]['total']) ? $rst[$idx][$jdate]['total'] + $sql_total : intval($sql_total);
            $num_staff = $tblTOTApply->countStaffOfTeam($f_char, $t_char, $wdate);
            $rst[$idx][$jdate] = [
                'total' => $total,
                'num' => $num_staff,
            ];
        }

        $response = ['success' => 0, 'error' => ''];
        try {
            $fileName = "Overtime_all_summary.xlsx";
            $fileDir = WWW_ROOT . "excel";
            $input = $fileDir . "/template/" . $fileName;
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($input);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->getPageMargins()
                ->setLeft(0.1)
                ->setTop(0.1)
                ->setHeader(0);

            $ym = date('Y-m', strtotime($date));
            $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A3);
            $sheet->getPageSetup()->setFitToWidth(0);
            $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
            $spreadsheet->getActiveSheet()->setTitle($ym);

            $keys = ['j1ka', 'j2ka', 'j3ka', 'a1ka', 'a2ka', 'a3ka'];
            $s_row = 4;
            $totals = [];
            $sheet->setCellValue('AA2', $ym);
            foreach ($keys as $idx => $key) {
                $row = $rst[$key];
                foreach ($row as $i => $col) {
                    $f_total = round($col['total'] / 60, 1);
                    $f_arr = round($col['total'] / $col['num'] / 60, 1);
                    if ($f_total == 0) {
                        continue;
                    }

                    $sheet->setCellValueByColumnAndRow($i + 1, $s_row, sprintf("%.1f", $f_total));
                    $sheet->setCellValueByColumnAndRow($i + 1, $s_row + 1, sprintf("%.1f", $f_arr));
                    $totals[$i] = isset($totals[$i]) ? $totals[$i] + $f_total : $f_total;
                }
                $s_row += 2;
            }

            $s_r = 4;
            $e_r = 16;
            for ($d = 1; $d <= 31; $d++) {
                $y = date('Y', strtotime($date));
                $m = date('m', strtotime($date));
                $chk = checkdate($m, $d, $y);
                if ($chk) {
                    $chk_date = date('Y-m-' . $d, strtotime($date));
                    $s = date('w', strtotime($chk_date));

                    $cell = $sheet
                        ->getCellByColumnAndRow($d + 1, 1)
                        ->getCoordinate();
                    $cell = preg_replace("/[^A-Z]+/", "", $cell);
                    if ($s == 0) {
                        //check sun
                        $sheet->getStyle("{$cell}{$s_r}:{$cell}{$e_r}")
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('f6b19e');
                    }

                    if ($s == 6) {
                        //check sat
                        $sheet->getStyle("{$cell}{$s_r}:{$cell}{$e_r}")
                            ->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('9bc2e6');
                    }
                }
            }

            foreach ($totals as $i => $e) {
                $sheet->setCellValueByColumnAndRow($i + 1, 16, sprintf("%.1f", $e));
            }

            $path = $fileDir . "/output";
            if (!file_exists($path)) {
                mkdir($path);
            }
            $fileName = date("Y-m-d", strtotime($date)) . ".pdf";
            $outPut = $path . "/" . $fileName;

            IOFactory::registerWriter("PDF", Dompdf::class);
            $pdfwriter = IOFactory::createWriter($spreadsheet, 'PDF');
            $pdfwriter->save($outPut);

            $response['success'] = 1;
            $response['file'] = "excel/output/" . $fileName;
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * @param string $date
     * @param array $list_holiday
     * @return bool
     * @author  DANDEV
     */
    public static function isHoliday($date = '', $list_holiday = array())
    {
        foreach ($list_holiday as $h) {
            if (!empty($date) && strcasecmp(date("Y-m-d", strtotime($h->HolidayDate)), date("Y-m-d", strtotime($date))) == 0) {
                return $h;
            }
        }
        return false;
    }

    public static function verifyDate($date)
    {
        return date("Y", strtotime($date)) > 1970 && date("Y", strtotime($date)) <= date("Y");
    }

    public static function checkErorrMessange($data)
    {
        $message = [];
        if (!Excel::verifyDate($data['Day'])) {
            array_push($message, 'Date is not format');
        }
        if (!is_string($data['WeekDay'])) {
            array_push($message, 'WeekDay is not a string');
        }
        if (!is_string($data['TimeIn'])) {
            array_push($message, 'Time in not null');
        }
        if (!is_string($data['TimeOut'])) {
            array_push($message, 'Time out not null');
        }
        if (!is_numeric($data['TotalTime'])) {
            array_push($message, 'Total time is not a number');
        }
        return $message;
    }

    /**
     * @param string $file_name
     * @return bool
     * @author  NHIP
     */
    public static function importSalary($salary_file)
    {
        try {
            $inputFileType = 'Xls';
            $reader = IOFactory::createReader($inputFileType);
            // $spreadsheet   = $reader->load($salary_file);

            $spreadsheet = $reader->load($salary_file);

            $worksheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

            // return $worksheet;
            if ($worksheet) {
                $articles = TableRegistry::getTableLocator()->get('tblTTimeCard');
                $entities = [];
                $row_add = 0;
                $row_error = 0;
                $row_update = 0;
                $lines_get_erorr = 1;
                $object_erorr = [];

                foreach ($worksheet as $row) {

                    if (!empty($row["A"]) && !empty($row["B"]) && Excel::verifyDate($row['E'])) {

                        $data = [
                            'StaffID' => $row['A'],
                            'Day' => date('Y-m-d', strtotime($row['E'])),
                            'WeekDay' => self::WEEKDAY_EN[$row['F']],
                            'TimeIn' => $row['G'],
                            'TimeOut' => $row['H'],
                            'TotalTime' => number_format($row['U'], 2),
                        ];
                        if (is_numeric($data['TotalTime'])) {
                            $query = $articles->find('all')
                                ->where(['StaffID' => $data['StaffID']])
                                ->where(['Day' => $data['Day']]);
                            $check = $query->first();
                            if ($check) {
                                $data['ID'] = $check->ID;
                                $row_update++;
                            } else {

                                $row_add++;
                            }
                        } else {
                            $message = Excel::checkErorrMessange($data);
                            $row_error++;
                            array_push($object_erorr, ['line' => $lines_get_erorr, 'message' => $message]);
                        }
                        array_push($entities, $data);
                    }
                    $lines_get_erorr++;
                }

                $row_add = count($entities) - $row_error - $row_update;

                if ($row_error > 0) {
                    return json_encode(array(
                        'success' => false,
                        'row_add' => $row_add,
                        'row_error' => $row_error,
                        'row_update' => $row_update,
                        'details_error' => $object_erorr,
                    ));
                }
                $articles->getConnection()->transactional(function () use ($articles, $entities) {
                    $entities_noti = $articles->newEntities($entities);
                    $result_noti = $articles->saveMany($entities_noti);
                });

                return json_encode(array(
                    'success' => true,
                    'row_add' => $row_add,
                    'row_update' => $row_update,
                ));
            }
        } catch (\Exception $e) {
            return json_encode(array(
                'error' => true,
                'message' => $e->getMessage(),
            ));
        }
    }

    /**
     * @param string $row
     * @return bool
     */
    public static function importEntitySalary($row)
    {

        $articles = TableRegistry::getTableLocator()->get('tblTTimeCard');
        if (!empty($row["A"]) && !empty($row["B"]) && $row["O"] !== "TC1") {

            $data = [
                'StaffID' => $row['A'],
                'Day' => date('Y-m-d', strtotime($row['E'])),
                'WeekDay' => $row['F'],
                'TimeIn' => $row['G'],
                'TimeOut' => $row['H'],
                'TotalTime' => $row['U'],
            ];

            if (Excel::verifyDate($data['Day']) && is_string($data['WeekDay']) && is_string($data['TimeIn']) && is_string($data['TimeOut']) && is_numeric($data['TotalTime'])) {
                $query = $articles->find('all')
                    ->where(['StaffID' => $data['StaffID']])
                    ->where(['Day' => $data['Day']]);
                $check = $query->first();
                if ($check) {
                    $data['ID'] = $check->ID;
                }
            } else {
                return false;
            }

            $overtimes = TableRegistry::get('OTApply')
                ->find()
                ->select([
                    'OTApply.Date',
                    'OTApply.FromTime',
                    'OTApply.ToTime',
                    'OTApply.Total',
                ])
                ->where(['OTApply.StaffID' => $data['StaffID'], 'DATE(OTApply.Date) =' => $data['Day']])
                ->where(['OR' => ['LeaderApproved !=' => 0, 'LeaderApproved IS NULL']])
                ->order(['OTApply.FromTime' => 'ASC'])->toList();

            $overtime = [];
            foreach ($overtimes as $ov) {
                if (!isset($overtime['Total'])) {
                    $overtime['Total'] = 0;
                }
                if (!isset($overtime['FromTime'])) {
                    $overtime['FromTime'] = $ov->FromTime;
                }
                $overtime['Total'] += $ov->Total;
            }

            $shift = TableRegistry::get('TBLTShift')
                ->find()
                ->select([
                    'TBLTShift.StaffID',
                    'TimeIn' => 'SUBSTRING(TBLTShift.FromTime,1,5)',
                ])
                ->where(['TBLTShift.StaffID' => $data['StaffID'], 'DATE(TBLTShift.Date) =' => $data['Day']])
                ->first();

            if (!$shift) {
                if (empty($data['TimeOut'])) {
                    $data['TotalTime'] = 0;
                } else if (!empty($overtime) && empty($data['TimeOut']) && strtotime($overtime['FromTime']) > strtotime("08:00:00")) {
                    $data['TotalTime'] = number_format(9 + ($overtime['Total'] / 60), 2);
                } else {
                    $data['TotalTime'] = number_format(((strtotime($data['TimeOut']) - strtotime("08:00:00")) / 3600), 2);
                }
                $entity = $articles->newEntity($data);
                return $articles->save($entity);
            }

            if (empty($data['TimeIn']) || (empty($data['TimeOut']) && empty($overtime)) || (!empty($overtime) && empty($data['TimeOut']) && strtotime($overtime['FromTime']) < strtotime($shift->TimeIn))) {
                $data['TotalTime'] = 0;
            } else if (!empty($overtime) && empty($data['TimeOut']) && strtotime($overtime['FromTime']) > strtotime($shift->TimeIn)) {
                $data['TotalTime'] = number_format(9 + ($overtime['Total'] / 60), 2);
            } else {
                $overTimeFirst = 0;
                if (!empty($overtime) && strtotime($overtime['FromTime']) < strtotime($shift->TimeIn)) {
                    $overTimeFirst = ($overtime['Total'] / 60);
                }
                $data['TotalTime'] = number_format(((strtotime($data['TimeOut']) - strtotime($shift->TimeIn)) / 3600) + $overTimeFirst, 2);
            }
            $entity = $articles->newEntity($data);
            return $articles->save($entity);
        } else {
            return false;
        }
    }

    /**
     * @param string $staff_id, $date, $file_type
     * @author  NHIP
     */

    public static function exportSalaryDetailByDate($staff_id, $date, $pdf, $excel)
    {
        set_time_limit(0);
        ini_set('memory_limit', -1);
        mb_internal_encoding('UTF-8');
        $response = ['success' => 0, 'error' => ''];
        try {
            $currentMonth = date('Y-m');
            if (strtotime($date) == strtotime($currentMonth)) {
                $response['error'] = "Salary details of StaffID " . $staff_id . " in this month is not available!";
                return $response;
            }
            if ($pdf == "" && $excel == "") {
                $response['error'] = "Please choose one of file type to export staff salary details!";
                return $response;
            }
            $staff = TableRegistry::get('TBLMStaff')
                ->find()
                ->select([
                    'TBLMStaff.StaffID',
                    'TBLMStaff.StaffName',
                    'TBLMStaff.TrialEntryDate',
                    'TBLMStaff3.Team',
                    'TBLMStaff3.Position',
                ])
                ->contain([
                    'TBLMStaff2',
                    'TBLMStaff3',
                ])
                ->where(["TBLMStaff.StaffID" => $staff_id])
                ->first();
            if ($staff) {
                if ($excel == "1") {
                    $fileName1 = Excel::_getExcelForm($staff_id, $date);
                    $fileDir1 = WWW_ROOT . "excel";

                    $input1 = $fileDir1 . "/template/" . $fileName1;
                    $reader1 = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $spreadsheet1 = $reader1->load($input1);
                    $sheet1 = $spreadsheet1->getActiveSheet();

                    $styleArray = [
                        'font' => [
                            'size' => 11,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['argb' => '000'],
                            ],
                        ],
                    ];
                    $styleMarginCenter = array(
                        'alignment' => array(
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        ),
                    );
                    // staff information
                    if (strpos($staff->StaffID, 'A') !== false) {
                        $unit = "Seisaku";
                    } elseif (strpos($staff->StaffID, 'J') !== false) {
                        $unit = "Madoguchi";
                    } elseif (strpos($staff->StaffID, 'H') !== false) {
                        $unit = "Back Office";
                    } elseif (strpos($staff->StaffID, 'P') !== false) {
                        $unit = "Netsurf Programmer";
                    }
                    $_minimumSalaryLevel = Excel::_getMinimumSalaryLevel($staff->StaffID, $date);
                    $sheet1->setCellValue("B1", $staff->StaffID);
                    $sheet1->setCellValue("B2", $staff->StaffName);
                    $sheet1->setCellValue("B3", $unit);
                    $sheet1->setCellValue("B4", $staff->TrialEntryDate ? date("Y/m/d", strtotime($staff->TrialEntryDate)) . " (" . $_minimumSalaryLevel . ")" : "Date Of Enter");
                    $sheet1->setCellValue("E1", $staff->TBLMStaff3 ? $staff->TBLMStaff3->Position : "");
                    $salaryLevel = TableRegistry::get('TBLTSalaryLevel')
                        ->getSalary($staff_id);
                    $getMonth = explode("-", $date);
                    $month = $getMonth[1];
                    $year = $getMonth[0];
                    // $salary = TableRegistry::get('TBLTSalaryLevel')->getSalaryRange($staff_id);
                    $salary = TableRegistry::get('TBLTSalaryLevel')
                        ->find()
                        ->select(['TBLTSalaryLevel.SalaryLevel', 'TBLTSalaryLevel.DateUpdated'])
                        ->join([
                            'table' => 'tblMSalaryRange',
                            'alias' => 'TBLMSalaryRange',
                            'type' => 'LEFT',
                            'conditions' => 'TBLMSalaryRange.SalaryLevel = TBLTSalaryLevel.SalaryLevel',
                        ])
                        ->where([
                            'TBLTSalaryLevel.StaffID' => $staff_id,
                        ])
                        ->all();
                    foreach ($salary as $l) {
                        $split = explode("/", $l->DateUpdated);
                        if ($split[0] == $year && $split[1] == $month) {
                            $salaryLevelAmount = str_replace(",", "", $l->SalaryLevel);
                        }
                    }
                    if (!isset($salaryLevelAmount)) {
                        $response['error'] = "There is no Salary Level found for this staff";
                        return $response;
                    }

                    $sheet1->setCellValue("G2", !isset($salaryLevelAmount) ? 0 : $salaryLevelAmount);
                    // $str = "Total payment";
                    // $sheet1->setCellValue("A63", $str);
                    $timecards = TableRegistry::get('tblTTimeCard')
                        ->find()
                        ->select()
                        ->where([
                            'StaffID' => $staff_id,
                            'MONTH(tblTTimeCard.Day)' => $month,
                            'YEAR(tblTTimeCard.Day)' => $year,
                        ])->all();
                    $totalLateMins = 0;
                    if (count($timecards) > 0) {
                        $beginRow = 7;
                        $start_date = "01-" . $month . "-" . $year;
                        $start_time = strtotime($start_date);
                        $end_time = strtotime("+1 month", $start_time);
                        for ($i = $start_time; $i < $end_time; $i += 86400) {
                            $sheet1->setCellValue("A" . $beginRow, date('Y-m-d', $i));
                            $sheet1->setCellValue("B" . $beginRow, date('D', $i));
                            $sheet1->setCellValue("C" . $beginRow, "0");
                            if (date('D', $i) == "Sat" || date('D', $i) == "Sun") {
                                $sheet1->getStyle("A" . $beginRow . ":I" . $beginRow)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                                if (date('D', $i) == "Sat") {
                                    $sheet1->setCellValue("C" . $beginRow, "1");
                                }
                                if (date('D', $i) == "Sun") {
                                    $sheet1->setCellValue("C" . $beginRow, "2");
                                }
                            }
                            $sheet1->setCellValue("G" . $beginRow, "0");
                            $sheet1->setCellValue("H" . $beginRow, "0");
                            $sheet1->setCellValue("I" . $beginRow, "0");
                            foreach ($timecards as $timecard) {
                                if (strtotime(date('Y-m-d', $i)) == strtotime($timecard->Day)) {
                                    $shift = TableRegistry::get('TBLTShift')->find()->where([
                                        "StaffID" => $staff_id,
                                        'Date' => $timecard->Day,
                                    ])->first();
                                    $ot = TableRegistry::get('OTApply')->find()
                                        ->select(['SUM' => 'SUM(Total)'])
                                        ->where([
                                            "StaffID" => $staff_id,
                                            'Date' => $timecard->Day,
                                            'LeaderApproved' => 1,
                                        ])->first()->toArray();

                                    if ($shift != null) {
                                        // Fixed logic calculate Working time - 20200602 - Baonguyen
                                        $workTime = ($timecard->TimeOut == null || $shift->FromTime == null) ?
                                        0 :
                                        number_format((strtotime($timecard->TimeOut) - strtotime($timecard->TimeIn)) / 3600, 2);
                                        $shiftVal = ($timecard->TimeOut == null || $shift->FromTime == null) ? "--" : "Shift " . $shift->Shift;
                                    } else {
                                        if (date('D', $i) == "Sat" || date('D', $i) == "Sun") {
                                            $workTime = ($timecard->TimeOut == null || $timecard->TimeIn == null) ?
                                                0 :
                                                number_format((strtotime($timecard->TimeOut) - strtotime($timecard->TimeIn)) / 3600, 2);
                                        } else {
                                            $workTime = 0;
                                        }
                                        $shiftVal = "--";
                                    }
                                    if ($ot != null) {
                                        if ($ot["SUM"] != null) {
                                            $overtime = $ot["SUM"];
                                            $sheet1->setCellValue("H" . $beginRow, $overtime);
                                        }
                                    }

                                    //Late column
                                    $_lateMins = Excel::_getLateMinsInDay($staff_id, $timecard, @$shift->Shift);
                                    $totalLateMins += $_lateMins;
                                    $sheet1->setCellValue("I" . $beginRow, $_lateMins);

                                    // if (date('D', $i) == "Sat" || date('D', $i) == "Sun") {
                                    //     $timeIn = "8:00";
                                    //     $sheet1->setCellValue("F" . $beginRow, "--");
                                    // } else {
                                    //     $timeIn = date("H:i", strtotime($timecard->TimeIn));
                                    //     $sheet1->setCellValue("F" . $beginRow, $shiftVal);
                                    // }
                                    $timeIn = ($timecard->TimeIn == null) ? "" : date("H:i", strtotime($timecard->TimeIn));
                                    $timeOut = ($timecard->TimeOut == null) ? "" : date("H:i", strtotime($timecard->TimeOut));

                                    $sheet1->setCellValue("F" . $beginRow, $shiftVal);
                                    $sheet1->setCellValue("D" . $beginRow, $timeIn);
                                    $sheet1->setCellValue("E" . $beginRow, $timeOut);
                                    $sheet1->setCellValue("G" . $beginRow, ($workTime) ? $workTime : 0);
                                }
                            }
                            $beginRow++;
                        }
                        $datediff = $end_time - $start_time;
                        $totalDateInMonth = round($datediff / (60 * 60 * 24));
                        if ($totalDateInMonth <= 30) {
                            $END_ROW_IN_TEMPLATE = 38;
                            $remainingRow = $END_ROW_IN_TEMPLATE - $beginRow;
                            $sheet1->removeRow($beginRow, $remainingRow);
                            $sheet1->setCellValue("H" . $beginRow, "=SUM(H" . ($beginRow - 1) . ":H" . ($beginRow - $totalDateInMonth) . ")");
                            $sheet1->setCellValue("I" . $beginRow, "=SUM(I" . ($beginRow - 1) . ":I" . ($beginRow - $totalDateInMonth) . ")");
                        }

                        // salary
                        Excel::salaryStaff($sheet1, $staff_id, $date, $timecards, $beginRow + 2, $totalLateMins);
                    } else {
                        $response['error'] = "No timecards for this staff found!";
                        return $response;
                    }

                    // Margin Center
                    $sheet1->getStyle("B")->applyFromArray($styleMarginCenter);
                    $sheet1->getStyle("D")->applyFromArray($styleMarginCenter);
                    $path = $fileDir1 . "/output";
                    if (!file_exists($path)) {
                        mkdir($path);
                    }
                    $fileNameExport = $staff_id . "-" . date("Ymd", strtotime($date)) . "-" . date("Ymd", strtotime($staff->TrialEntryDate)) . "-" . $staff->StaffName . ".xls";
                    $outPut = $path . "/" . $fileNameExport;
                    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet1, 'Xls');
                    // $writer->setPreCalculateFormulas(FALSE);

                    $writer->save($outPut);
                    $response['success'] = 1;
                    $response['file'] = "excel/output/" . $fileNameExport;
                }

                if ($pdf == "1") {
                    $data = [];
                    $fileName1 = Excel::_getExcelForm($staff_id, $date);
                    $fileDir1 = WWW_ROOT . "excel";
                    $input1 = $fileDir1 . "/template/" . $fileName1;
                    $reader1 = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    $spreadsheet1 = $reader1->load($input1);
                    $sheet1 = $spreadsheet1->getActiveSheet();

                    // staff information
                    if (strpos($staff->StaffID, 'A') !== false) {
                        $unit = "Seisaku";
                    } elseif (strpos($staff->StaffID, 'J') !== false) {
                        $unit = "Madoguchi";
                    } elseif (strpos($staff->StaffID, 'H') !== false) {
                        $unit = "Back Office";
                    }
                    $_minimumSalaryLevel = Excel::_getMinimumSalaryLevel($staff->StaffID, $date);

                    $data_info = [];
                    $data_info['id'] = $staff->StaffID;
                    $data_info['name'] = $staff->StaffName;
                    $data_info['unit'] = $unit;
                    $data_info['date_of_enter'] = $staff->TrialEntryDate ? date("Y/m/d", strtotime($staff->TrialEntryDate)) . " (" . $_minimumSalaryLevel . ")" : "Date Of Enter";

                    $data_info['position'] = $staff->TBLMStaff3 ? $staff->TBLMStaff3->Position : "";

                    $salaryLevel = TableRegistry::get('TBLTSalaryLevel')
                        ->getSalary($staff_id);
                    $getMonth = explode("-", $date);
                    $month = $getMonth[1];
                    $year = $getMonth[0];
                    // $salary = TableRegistry::get('TBLTSalaryLevel')->getSalaryRange($staff_id);
                    $salary = TableRegistry::get('TBLTSalaryLevel')
                        ->find()
                        ->select(['TBLTSalaryLevel.SalaryLevel', 'TBLTSalaryLevel.DateUpdated'])
                        ->join([
                            'table' => 'tblMSalaryRange',
                            'alias' => 'TBLMSalaryRange',
                            'type' => 'LEFT',
                            'conditions' => 'TBLMSalaryRange.SalaryLevel = TBLTSalaryLevel.SalaryLevel',
                        ])
                        ->where([
                            'TBLTSalaryLevel.StaffID' => $staff_id,
                        ])
                        ->all();
                    foreach ($salary as $l) {
                        $split = explode("/", $l->DateUpdated);
                        if ($split[0] == $year && $split[1] == $month) {
                            $salaryLevelAmount = str_replace(",", "", $l->SalaryLevel);
                        }
                    }
                    if (!isset($salaryLevelAmount)) {
                        $data['error'] = 1;
                        $data['message'] = "There is no Salary Level found for this staff";
                        return $data;
                    }

                    $data_info['level'] = !isset($salaryLevelAmount) ? 0 : $salaryLevelAmount;
                    // $str = "Total payment";
                    // $sheet1->setCellValue("A63", $str);
                    $timecards = TableRegistry::get('tblTTimeCard')
                        ->find()
                        ->select()
                        ->where([
                            'StaffID' => $staff_id,
                            'MONTH(tblTTimeCard.Day)' => $month,
                            'YEAR(tblTTimeCard.Day)' => $year,
                        ])->all();
                    $totalLateMins = 0;

                    if (count($timecards) > 0) {
                        $beginRow = 7;
                        $start_date = "01-" . $month . "-" . $year;
                        $start_time = strtotime($start_date);
                        $end_time = strtotime("+1 month", $start_time);
                        $data_table = [];
                        $totalOT = 0;
                        $totalLate = 0;

                        for ($i = $start_time; $i < $end_time; $i += 86400) {
                            $item = [];
                            $item['date'] = date('Y-m-d', $i);
                            $item['date_of_week'] = date('D', $i);
                            $item['shu'] = "0";
                            $item['color'] = "black";

                            if (date('D', $i) == "Sat" || date('D', $i) == "Sun") {
                                $item['color'] = "red";
                                if (date('D', $i) == "Sat") {
                                    $item['shu'] = "1";
                                }
                                if (date('D', $i) == "Sun") {
                                    $item['shu'] = "2";
                                }
                            }

                            $item['worktime'] = "0";
                            $item['ot'] = "0";
                            $item['late'] = "0";

                            foreach ($timecards as $timecard) {
                                if (strtotime(date('Y-m-d', $i)) == strtotime($timecard->Day)) {
                                    $shift = TableRegistry::get('TBLTShift')->find()->where([
                                        "StaffID" => $staff_id,
                                        'Date' => $timecard->Day,
                                    ])->first();
                                    $ot = TableRegistry::get('OTApply')->find()
                                        ->select(['SUM' => 'SUM(Total)'])
                                        ->where([
                                            "StaffID" => $staff_id,
                                            'Date' => $timecard->Day,
                                            'LeaderApproved' => 1,
                                        ])->first()->toArray();

                                    if ($shift != null) {
                                        $workTime = ($timecard->TimeOut == null || $shift->FromTime == null) ?
                                            0 :
                                            number_format((strtotime($timecard->TimeOut) - strtotime($shift->FromTime)) / 3600, 2);
                                        $shiftVal = ($timecard->TimeOut == null || $shift->FromTime == null) ? "--" : "Shift " . $shift->Shift;
                                    } else {
                                        if (date('D', $i) == "Sat" || date('D', $i) == "Sun") {
                                            $workTime = ($timecard->TimeOut == null || $timecard->TimeIn == null) ?
                                                0 :
                                                number_format((strtotime($timecard->TimeOut) - strtotime($timecard->TimeIn)) / 3600, 2);
                                        } else {
                                            $workTime = 0;
                                        }
                                        $shiftVal = "--";
                                    }

                                    if ($ot != null) {
                                        if ($ot["SUM"] != null) {
                                            $overtime = $ot["SUM"];
                                            $sheet1->setCellValue("H" . $beginRow, $overtime);
                                            $item['ot'] = $overtime;
                                        }
                                    }
                                    $_lateMins = Excel::_getLateMinsInDay($staff_id, $timecard, @$shift->Shift);
                                    $totalLateMins += $_lateMins;
                                    $item['late'] = $_lateMins;

                                    // if (date('D', $i) == "Sat" || date('D', $i) == "Sun") {
                                    //     $timeIn = "8:00";
                                    //     $sheet1->setCellValue("F" . $beginRow, "--");
                                    // } else {
                                    //     $timeIn = date("H:i", strtotime($timecard->TimeIn));
                                    //     $sheet1->setCellValue("F" . $beginRow, $shiftVal);
                                    // }
                                    $timeIn = ($timecard->TimeIn == null) ? "" : date("H:i", strtotime($timecard->TimeIn));
                                    $timeOut = ($timecard->TimeOut == null) ? "" : date("H:i", strtotime($timecard->TimeOut));

                                    $item['check_in_time'] = $timeIn;
                                    $item['check_out_time'] = $timeOut;
                                    $item['shift'] = $shiftVal;
                                    $item['work_time'] = ($workTime) ? $workTime : 0;
                                }
                            }
                            $totalOT = $totalOT + $item['ot'];
                            $totalLate = $totalLate + $item['late'];
                            array_push($data_table, $item);
                            $beginRow++;
                        }

                        $datediff = $end_time - $start_time;
                        $totalDateInMonth = round($datediff / (60 * 60 * 24));
                        $data_info['totalOT'] = $totalOT;
                        $data_info['totalLate'] = $totalLate;

                        $data_salary = Excel::salaryStaff($sheet1, $staff_id, $date, $timecards, $beginRow + 2, $totalLateMins);

                        $data['error'] = 0;
                        $data['info'] = $data_info;
                        $data['table'] = $data_table;
                        $data['salary'] = $data_salary;

                        return $data;
                    } else {
                        $data['error'] = 1;
                        $data['message'] = "No timecards for this staff found!";
                        return $data;
                    }
                }
            } else {
                $response['error'] = "No data found!";
                return $response;
            }
        } catch (\Exception $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    public static function _getExcelForm($staffID, $dateExport)
    {
        if (Excel::_isPartInternPartStaff($staffID, $dateExport)) {
            return "Salary-part-intern.xlsx";
        }

        return "Salary.xlsx";
    }

    public static function _isPartInternPartStaff($staffID, $dateExport)
    {
        $dateExport = new \DateTime($dateExport);
        $firstDayOfMonthExport = new \DateTime($dateExport->modify('first day of this month')->format('Y-m-d'));
        $lastDayOfMonthExport = new \DateTime($dateExport->modify('last day of this month')->format('Y-m-d'));
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $trialStartDate = new \DateTime($userData->TrialStartDate);
        $trialEndDate = new \DateTime($userData->TrialEndDate);
        return ($firstDayOfMonthExport < $trialEndDate && $trialEndDate < $lastDayOfMonthExport);
    }

    public static function _isProgrammer($staffID) {
        return substr( $staffID, 0, 1 ) === "P";
    }

    public static function _getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport)
    {
        $dateExport = new \DateTime($dateExport);
        $firstDayOfMonthExport = new \DateTime($dateExport->modify('first day of this month')->format('Y-m-d'));
        $lastDayOfMonthExport = new \DateTime($dateExport->modify('last day of this month')->format('Y-m-d'));
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $trialStartDate = new \DateTime($userData->TrialStartDate);
        $trialEndDate = new \DateTime($userData->TrialEndDate);
        $result = [
            'trainee' => ['fromDate' => $firstDayOfMonthExport->format('Y-m-d'), 'toDate' => $trialEndDate->format('Y-m-d')],
            'staff' => ['fromDate' => $trialEndDate->modify('+1 day')->format('Y-m-d'), 'toDate' => $lastDayOfMonthExport->format('Y-m-d')],
        ];
        return $result;
    }

    public static function _isIntern($staffID, $dateExport)
    {
        $dateExport = new \DateTime($dateExport);
        $firstDayOfMonthExport = new \DateTime($dateExport->modify('first day of this month')->format('Y-m-d'));
        $lastDayOfMonthExport = new \DateTime($dateExport->modify('last day of this month')->format('Y-m-d'));
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $trialStartDate = new \DateTime($userData->TrialStartDate);
        $trialEndDate = new \DateTime($userData->TrialEndDate);
        return ($lastDayOfMonthExport < $trialEndDate);
    }

    public static function _getValidWorkingDayDetailForTrainee($staffID, $numberWorkingDays, $dateExport, $timecards)
    {
        $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        $workingDateAsTrainee = Excel::_getNumberOfStaffWorkingDaysInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['trainee']['fromDate'], $traineeAndStaffWorkingDayInMonth['trainee']['toDate'], $dateExport, false);
        $workingDateAsStaff = Excel::_getNumberOfStaffWorkingDaysInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);
        $businessDaysAsTrainee = Excel::_getNumberOfBussinessDaysInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['trainee']['fromDate'], $traineeAndStaffWorkingDayInMonth['trainee']['toDate'], $dateExport);
        $businessDaysAsStaff = Excel::_getNumberOfBussinessDaysInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport);
        $traineeFromDate = new \DateTime($traineeAndStaffWorkingDayInMonth['trainee']['fromDate']);
        $traineeToDate = new \DateTime($traineeAndStaffWorkingDayInMonth['trainee']['toDate']);
        $staffFromDate = new \DateTime($traineeAndStaffWorkingDayInMonth['staff']['fromDate']);
        $staffToDate = new \DateTime($traineeAndStaffWorkingDayInMonth['staff']['toDate']);
        return [
            'trainee' => [
                'period' => $traineeFromDate->format('m/d') . '-' . $traineeToDate->format('m/d'),
                'count' => $workingDateAsTrainee,
                'businessDays' => $businessDaysAsTrainee
            ],
            'staff' => [
                'period' => $staffFromDate->format('m/d') . '-' . $staffToDate->format('m/d'),
                'count' => $workingDateAsStaff,
                'businessDays' => $businessDaysAsStaff
            ],
        ];
    }

    public static function _getValidWorkingFullDateInRange($staffID, $timecards, $fromDate, $toDate, $dateExport, $countShift = false, $isCountShift = false)
    {
        $timecards = $timecards->toArray();
        $dateExport = new \DateTime($dateExport);
        $monthExport = $dateExport->format('m');
        $yearExport = $dateExport->format('Y');

        $validOffDay = TableRegistry::get('TBLTALApply')->getValidDayOffByMonth($staffID, $yearExport, $monthExport)->toArray();
        $shifts = TableRegistry::get('TBLTShift')->getStaffShiftByMonth($staffID, $yearExport, $monthExport)->toArray();
        $validWorkingDateCount = 0;
        $interval = \DateInterval::createFromDateString('1 day');
        $toDate = new \DateTime($toDate); //Inteval missing last date so add 1 more date
        $toDate = $toDate->modify('+1 day');
        $period = new \DatePeriod(new \DateTime($fromDate), $interval, $toDate);
        foreach ($period as $dt) {
            $timecard = array_filter($timecards, function ($timecard) use ($dt) {
                return (new \DateTime($timecard->Day) == $dt);
            });
            $timecard = end($timecard);

            $shift = array_filter($shifts, function ($shift) use ($dt) {
                return (new \DateTime($shift->Date) == $dt);
            });
            $shift = end($shift);

            $al = array_filter($validOffDay, function ($al) use ($dt) {
                return (new \DateTime($al->FromDate) >= $dt && new \DateTime($al->ToDate) <= $dt);
            });
            $al = end($al);

            if (!isset($timecard) || !isset($timecard->TimeIn)) {
                continue;
            }

            if (!isset($shift->FromTime)) {
                continue;
            }

            // Update logic 20200602 BaoNguyen
            // Add new case for counter shift, ignore late
            if (!$isCountShift && ($shift->FromTime < $timecard->TimeIn)) {
                continue;
            }

            // Update logic 20200602 BaoNguyen
            // Add new case for counter shift
            if ($isCountShift) {
                if (isset($al) && $al != false || $timecard->TotalTime < 5) continue;
            } else {
                if (isset($al) && $al != false || $timecard->TotalTime < 9) continue;
            }

            if ($countShift == true && $shift->Shift != $countShift) {
                continue;
            }
            //This is for counting by shift
            $validWorkingDateCount++;
        }
        return $validWorkingDateCount;
    }

    public static function arrToCsv($month)
    {
        $fileDir = WWW_ROOT . "excel";
        $path = $fileDir . "/output";
        if (!file_exists($path)) {
            mkdir($path);
        }
        $fileName = "ShiftLeave_" . date("Y-m", strtotime($month . "/01")) . ".txt";
        $outPut = $path . "/" . $fileName;

        $fp = fopen($outPut, 'wa+');
        fwrite($fp, self::arr2csv(self::$rowResult));
        fclose($fp);
    }

    public static function arr2csv($fields)
    {
        $fp = fopen('php://temp', 'r+b');
        foreach ($fields as $field) {
            fputcsv($fp, $field);
        }
        rewind($fp);
        return mb_convert_encoding(stream_get_contents($fp), 'SJIS-win', 'UTF-8');
    }

    public static function getNumberOfWorkingDays($staff_id, $month, $year)
    {
        $type = CAL_GREGORIAN;
        $workdays = array();
        $day_count = cal_days_in_month($type, $month, $year);
        for ($i = 1; $i <= $day_count; $i++) {
            $date = $year . '/' . $month . '/' . $i; //format date
            $get_name = date('l', strtotime($date)); //get week day
            $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars
            //if not a weekend add day to array
            if ($day_name != 'Sun' && $day_name != 'Sat') {
                $workdays[] = $i;
            }
        }
        return count($workdays);
    }

    public static function getValidNumberOfWorkingDays($staff_id, $month, $year)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staff_id);
        $trialEntryDate = $userData->TrialEntryDate->i18nFormat('yyyy-MM-dd');
        $type = CAL_GREGORIAN;
        $workdays = array();
        $day_count = cal_days_in_month($type, $month, $year);
        for ($i = 1; $i <= $day_count; $i++) {
            $date = $year . '/' . $month . '/' . $i; //format date
            $get_name = date('l', strtotime($date)); //get week day
            $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars
            //if not a weekend add day to array
            if ($day_name != 'Sun' && $day_name != 'Sat') {
                if (strtotime($date) >= strtotime($trialEntryDate)) {
                    $workdays[] = $i;
                }
            }
        }
        return count($workdays);
    }

    private static function _calculateExtraWageMins($staff_id, $numberWorkingDays, $month, $year)
    {
        $totalOT = TableRegistry::get('OTApply')->countTotalOTByStaffID($staff_id, $month, $year);
        $totalExtraWageMins = ($totalOT >= 1800) ? $totalOT - 1800 : 0;
        return $totalExtraWageMins;
    }

    private static function _getLateMinsInDay($staffID, $timecard, $shift)
    {
        $timeIn = ($timecard->TimeIn == null) ? "" : date("H:i", strtotime($timecard->TimeIn));

        if ($shift === null) {
            return 0;
        }

        $shiftData = TableRegistry::get('TBLTShift')->getStaffShiftDetailByDate($staffID, $timecard->Day->i18nFormat('yyyy-MM-dd'));
        $alApplyData = TableRegistry::get('TBLTALApply')->getStaffValidALByDate($staffID, $timecard->Day->i18nFormat('yyyy-MM-dd'));

        //Add new case for H employee = 20200507 - Baonguyen
        if (!$alApplyData && strpos($staffID, 'H') === false) {
            return Excel::_getLateMinsShit($shiftData['firstShift']['start'], $timeIn);
        }
        //Add new case for H employee = 20200507 - Baonguyen
        if ($alApplyData['Total'] == "0.5" || strpos($staffID, 'H') !== false) {
            if (strtotime($alApplyData['FromTime'] . ":00") > strtotime($shiftData['firstShift']['start'])) {
                return Excel::_getLateMinsShit($shiftData['firstShift']['start'], $timeIn);
            } else {
                return Excel::_getLateMinsShit($shiftData['secondShift']['start'], $timeIn);
            }
        } else {
            return Excel::_getLateMinsShit($shiftData['firstShift']['start'], $timeIn);
        }
    }

    private static function _getLateMinsShit($startShift, $staffStartTime)
    {
        if (strtotime($startShift) > strtotime($staffStartTime)) {
            return 0;
        }

        $datetime1 = new \DateTime($startShift);
        $datetime2 = new \DateTime($staffStartTime);
        $interval = $datetime1->diff($datetime2);
        return $interval->format('%i');
    }

    private static function getNumberOfWorkingDaysWithQuitJobDate($quitJobDate, $month, $year)
    {
        $type = CAL_GREGORIAN;
        $workdays = array();
        $day_count = cal_days_in_month($type, $month, $year);
        $getMonth = explode("-", date("Y-m-d", strtotime('-1 day', strtotime($quitJobDate))));
        if ($getMonth[0] == $year && $getMonth[1] == $month) {
            $day_count = $getMonth[2];
        }
        for ($i = 1; $i <= $day_count; $i++) {
            $date = $year . '/' . $month . '/' . $i; //format date
            $get_name = date('l', strtotime($date)); //get week day
            $day_name = substr($get_name, 0, 3); // Trim day name to 3 chars
            //if not a weekend add day to array
            if ($day_name != 'Sun' && $day_name != 'Sat') {
                $workdays[] = $i;
            }
        }
        return count($workdays);
    }

    public static function getNumberOfStaffWorkingDays($staffID, $month, $year, $isIntern)
    {
        $normalCondition = [
            'SLeaderApproval' => 1,
            'MLeaderApproval' => 1,
            'ShuninApproval' => 1,
            'BOApproval' => 1,
        ];
        $hrCondition = [
            'BOApproval' => 1,
        ];
        $additionalCondition = (strpos($staffID, 'H') !== false) ? $hrCondition : $normalCondition;
        $alApply = TableRegistry::get('TBLTALApply')->find()
            ->select()
            ->where([
                'TBLTALApply.StaffID' => $staffID,
                'MONTH(TBLTALApply.FromDate)' => $month,
                'YEAR(TBLTALApply.FromDate)' => $year,
                $additionalCondition,
            ])
            ->all()->toArray();
        $count = 0;
        $numWorkingDays = Excel::getNumberOfWorkingDays($staffID, $month, $year);
        if ($isIntern) {
            $numWorkingDays = Excel::getValidNumberOfWorkingDays($staffID, $month, $year);
        }
        $staff = TableRegistry::get('TBLMStaff')->find()
            ->where(['StaffID' => $staffID])->first();
        if ($staff->QuitJobDate != null) {
            $numWorkingDays = Excel::getNumberOfWorkingDaysWithQuitJobDate($staff->QuitJobDate, $month, $year);
        }
        foreach ($alApply as $al) {
            if ($al->Type == '4') {
                $numWorkingDays = $numWorkingDays - $al->Total;
            }
        }
        return $numWorkingDays;
    }

    //Create for counting business days in range - 20200507 - Baonguyen
    public static function _getNumberOfBussinessDaysInRange($staffID, $timecards, $fromDate, $toDate, $dateExport)
    {
        $countBusinessDate = 0;
        $interval = \DateInterval::createFromDateString('1 day');
        $toDate = new \DateTime($toDate); //Inteval missing last date so add 1 more date
        $toDate = $toDate->modify('+1 day');
        $period = new \DatePeriod(new \DateTime($fromDate), $interval, $toDate);
        foreach ($period as $dt) {
            if ($dt->format('D') != 'Sun' && $dt->format('D') != 'Sat') {
                $countBusinessDate++;
            }
        };
        return $countBusinessDate;
    }

    public static function _getNumberOfStaffWorkingDaysInRange($staffID, $timecards, $fromDate, $toDate, $dateExport, $countShift = false)
    {
        $timecards = $timecards->toArray();
        $dateExport = new \DateTime($dateExport);
        $monthExport = $dateExport->format('m');
        $yearExport = $dateExport->format('Y');

        $validOffDay = TableRegistry::get('TBLTALApply')->getValidULByMonth($staffID, $yearExport, $monthExport)->toArray();
        $shifts = TableRegistry::get('TBLTShift')->getStaffShiftByMonth($staffID, $yearExport, $monthExport)->toArray();
        $validWorkingDateCount = 0;
        $interval = \DateInterval::createFromDateString('1 day');
        $toDate = new \DateTime($toDate); //Inteval missing last date so add 1 more date
        $toDate = $toDate->modify('+1 day');
        $period = new \DatePeriod(new \DateTime($fromDate), $interval, $toDate);
        foreach ($period as $dt) {
            $timecard = array_filter($timecards, function ($timecard) use ($dt) {
                return (new \DateTime($timecard->Day) == $dt);
            });
            $timecard = end($timecard);

            $shift = array_filter($shifts, function ($shift) use ($dt) {
                return (new \DateTime($shift->Date) == $dt);
            });
            $shift = end($shift);

            $al = array_filter($validOffDay, function ($al) use ($dt) {
                return (new \DateTime($al->FromDate) >= $dt && new \DateTime($al->ToDate) <= $dt);
            });
            $al = end($al);

            if (!isset($timecard) || !isset($timecard->TimeIn)) {
                continue;
            }

            if (!isset($shift->FromTime)) {
                continue;
            }

            if (isset($al) && $al != false) {
                continue;
            }

            $validWorkingDateCount++;
        }
        return $validWorkingDateCount;
    }

    public static function getNumberOfStaffWorkingDaysOLD($staffID, $month, $year)
    {
        $workDays = TableRegistry::get('tblTTimeCard')->find()
            ->select()
            ->where([
                'tblTTimeCard.StaffID' => $staffID,
                'MONTH(tblTTimeCard.Day)' => $month,
                'YEAR(tblTTimeCard.Day)' => $year,
            ])
            ->all()->toArray();
        if ($workDays != null) {
            $alApply = TableRegistry::get('TBLTALApply')->find()
                ->select()
                ->where([
                    'TBLTALApply.StaffID' => $staffID,
                    'MONTH(TBLTALApply.FromDate)' => $month,
                    'YEAR(TBLTALApply.FromDate)' => $year,
                    'OR' => [
                        'Type <>' => '4',
                        [
                            'AND' => [
                                'Type' => '4',
                                'Total' => '0.5',
                            ],
                        ],

                    ],
                ])
                ->all()->toArray();
            $count = 0;
            foreach ($alApply as $al) {
                $count += $al->Total;
            }
            $numWorkingDays = count($workDays) + $count;
            return $numWorkingDays;
        }
        return 0;
    }

    // get overtime staff detail on week days
    public static function getOvertimeStaffDetails($staffID, $month, $year)
    {
        $timecards = Excel::realTimecards($staffID, $month, $year);
        $countBefore = 0;
        $countAfter = 0;
        $holidays = TableRegistry::get('TBLVNHoliday')->find()->select()->all()->toArray();
        $holidays = array_map(function ($o) {
            return $o->HolidayDate->i18nFormat('yyyy-MM-dd');
        }, $holidays);
        if ($timecards) {
            foreach ($timecards as $timecard) {
                $overtime = TableRegistry::get('OTApply')->find()->select()
                    ->where([
                        "StaffID" => $staffID,
                        'MONTH(OTApply.Date)' => $month,
                        'YEAR(OTApply.Date)' => $year,
                        'OTApply.Date' => $timecard->Day,
                        'WEEKDAY(OTApply.Date) >=' => 0,
                        'WEEKDAY(OTApply.Date) <=' => 4,
                        'OTApply.LeaderApproved' => "1",
                        'OTApply.Date NOT IN' => $holidays,
                    ])->all()->toArray();
                if ($overtime != null) {
                    foreach ($overtime as $ov) {
                        $check = Excel::checkBeforeTime($ov->FromTime);
                        if ($check == true) {
                            $countBefore += (int) $ov->Total;
                        } else {
                            $countAfter += (int) $ov->Total;
                        }
                    }
                }
            }
        }
        return array(
            'CountBefore' => $countBefore,
            'CountAfter' => $countAfter,
        );
    }

    private static function realTimecards($staffID, $month, $year)
    {
        $timecards = TableRegistry::get('tblTTimeCard')->find()->select()
            ->join([
                'table' => 'tblTALApply',
                'alias' => 'c',
                'type' => 'LEFT',
                'conditions' => 'c.StaffID = tblTTimeCard.StaffID',
            ])
            ->where([
                "tblTTimeCard.StaffID" => $staffID,
                'MONTH(tblTTimeCard.Day)' => $month,
                'YEAR(tblTTimeCard.Day)' => $year,
                'OR' => [
                    'tblTTimeCard.Day <' => 'c.FromDate',
                    'tblTTimeCard.Day >' => 'c.ToDate',
                ],
            ])
            ->group(['tblTTimeCard.Day'])->all()->toArray();
        return $timecards;
    }
    // get overtime staff detail on weekends
    private static function getOvertimeWeekendStaffDetails($staffID, $month, $year)
    {
        $timecards = Excel::realTimecards($staffID, $month, $year);
        $countBefore = 0;
        $countAfter = 0;
        if ($timecards) {
            foreach ($timecards as $timecard) {
                $overtime = TableRegistry::get('OTApply')->find()->select()
                    ->where([
                        "StaffID" => $staffID,
                        'MONTH(OTApply.Date)' => $month,
                        'YEAR(OTApply.Date)' => $year,
                        'OTApply.Date' => $timecard->Day,
                        'WEEKDAY(OTApply.Date) >=' => 5,
                        'WEEKDAY(OTApply.Date) <=' => 6,
                        'OTApply.LeaderApproved' => "1",
                    ])->all()->toArray();

                if ($overtime != null) {
                    foreach ($overtime as $ov) {
                        $check = Excel::checkBeforeTime($ov->FromTime);
                        if ($check == true) {
                            $countBefore += (int) $ov->Total;
                        } else {
                            $countAfter += (int) $ov->Total;
                        }
                    }
                }
            }
        }
        return array(
            'CountBefore' => $countBefore,
            'CountAfter' => $countAfter,
        );
    }

    public static function checkBeforeTime($time)
    {
        if (strtotime($time) < strtotime("22:00:00")) {
            return true;
        } else if (strtotime($time) >= strtotime("22:00:00")) {
            return false;
        } else {
            return false;
        }
    }

    private static function checkHolidayDate($sheet1, $date, $staffID, $basicSalary, $numberWorkingDays, $currentRow = 46, $tetOTBefore10, $tetOTAfter10)
    {
        $data = [];
        $getMonth = explode("-", $date);
        $month = $getMonth[1];
        $year = $getMonth[0];
        $timecards = Excel::realTimecards($staffID, $month, $year);
        $holidays = TableRegistry::get('TBLVNHoliday')->find()->select()->all()->toArray();
        $minsTetHolidayTotal = 0;
        $countBefore = 0;
        $countAfter = 0;
        if ($timecards) {
            foreach ($timecards as $timecard) {
                $overtime = TableRegistry::get('OTApply')->find()->select()
                    ->where([
                        "StaffID" => $staffID,
                        'MONTH(OTApply.Date)' => $month,
                        'YEAR(OTApply.Date)' => $year,
                        'OTApply.Date' => $timecard->Day,
                        'OTApply.LeaderApproved' => 1,
                    ])->group(['OTApply.Date'])->first();
                if ($overtime != null) {
                    foreach ($holidays as $holiday) {
                        if (strtotime($overtime->Date) == strtotime($holiday->HolidayDate)) {
                            $check = Excel::checkBeforeTime($overtime->FromTime);
                            if ($check == true) {
                                $countBefore += (int) $overtime->Total;
                            } else {
                                $countAfter += (int) $overtime->Total;
                            }
                            $minsTetHolidayTotal += $overtime->Total;
                        }
                    }
                }
            }
        }
        if ($minsTetHolidayTotal > 0) {
            // $tetHolidayTotal = $basicSalary / $numberWorkingDays / 8 / 60 * $minsTetHolidayTotal;
            $tetHolidayTotal = ($basicSalary / 22 / 8 / 60) * (($tetOTBefore10 / 100) * $countBefore + ($tetOTAfter10 / 100) * $countAfter);
            $tetHolidayTotalFormula = "=(" . $basicSalary . "/22/8/60)*((" . $tetOTBefore10 . "/100)*" . $countBefore . "+(" . $tetOTAfter10 . "/100)*" . $countAfter . ")";
            $cellBData = $countBefore + $countAfter;
            if ($countAfter > 0) {
                $cellBData .= "(" . $countAfter . ")";
            }

            $sheet1->setCellValue("B" . $currentRow, $cellBData);
            $data['counter'] = $cellBData;
            $sheet1->setCellValue("D" . $currentRow, $tetHolidayTotalFormula);
            $data['value'] = ($basicSalary / 22 / 8 / 60) * (($tetOTBefore10 / 100) * $countBefore + ($tetOTAfter10 / 100) * $countAfter);
        } else {
            $sheet1->setCellValue("D" . $currentRow, "0");
            $data['value'] = 0;
        }
        return $data;
    }

    public static function isJStaff($staffID)
    {
        if (strpos($staffID, 'J') !== false) {
            return true;
        } else {
            return false;
        }
    }

    private static function salaryStaff($sheet, $staff_id, $date, $timecards, $currentRow = 40, $totalLateMins)
    {
        $basicSalaryAll = 0;
        $shiftOne = 0;
        $shiftThree = 0;
        $kyuot = 0;
        $overtimeAfterWeekDays = 0;
        $overtimeBeforeWeekend = 0;
        $overtimeAfterWeekend = 0;
        $nThreeAllowance = 0;
        $nTwoAllowance = 0;
        $nOneAllowance = 0;
        $accommodation = 0;
        $parkingFeeSetting = 0;
        $insuranceSetting = 0;
        $tetOTBefore10 = 0;
        $tetOTAfter10 = 0;
        $noOffNoLateSetting = 0;
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staff_id);
        $_isPartInternPartStaff = Excel::_isPartInternPartStaff($staff_id, $date);
        $_isIntern = Excel::_isIntern($staff_id, $date);
        $_isProgrammer = Excel::_isProgrammer($staff_id);
        $data_salary_staff = [];
        $data_salary_staff['total_amount'] = 0;

        $itStaffs = TableRegistry::get('TBLMStaff3')->fetchITStaffsIds()->toArray();

        //!OPTIMIZE NEEDED
        $salaryConfigs = Excel::retrieveSalaryItems();
        foreach ($salaryConfigs as $salaryItem) {
            $basicSalaryAll = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['basic_salary']) ? $salaryItem['Value'] : $basicSalaryAll;
            $shiftOne = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['shift_1']) ? $salaryItem['Value'] : $shiftOne;
            $shiftThree = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['shift_3']) ? $salaryItem['Value'] : $shiftThree;
            $kyuot = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['kyu_ot']) ? $salaryItem['Value'] : $kyuot;
            $overtimeAfterWeekDays = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['overtime_after_week_days']) ? $salaryItem['Value'] : $overtimeAfterWeekDays;
            $overtimeBeforeWeekend = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['overtime_before_weekend']) ? $salaryItem['Value'] : $overtimeBeforeWeekend;
            $overtimeAfterWeekend = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['overtime_after_weekend']) ? $salaryItem['Value'] : $overtimeAfterWeekend;
            $nThreeAllowance = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['N3']) ? $salaryItem['Value'] : $nThreeAllowance;
            $nTwoAllowance = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['N2']) ? $salaryItem['Value'] : $nTwoAllowance;
            $nOneAllowance = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['N1']) ? $salaryItem['Value'] : $nOneAllowance;
            $accommodation = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['accommodation']) ? $salaryItem['Value'] : $accommodation;
            $parkingFeeSetting = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['parking_fee']) ? $salaryItem['Value'] : $parkingFeeSetting;
            $insuranceSetting = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['insurance']) ? $salaryItem['Value'] : $insuranceSetting;
            $tetOTBefore10 = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['tetotbefore10']) ? $salaryItem['Value'] : $tetOTBefore10;
            $tetOTAfter10 = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['tetotafter10']) ? $salaryItem['Value'] : $tetOTAfter10;
            $noOffNoLateSetting = ($salaryItem['Code'] == Constants::SALARY_CONFIG_KEYS['no_off_no_late']) ? $salaryItem['Value'] : $noOffNoLateSetting;
        }
        $getMonth = explode("-", $date);
        $month = $getMonth[1];
        $year = $getMonth[0];
        /**
         * QUERY GET DATA FOR SUMMARY DETAIL
         * Shift count
         */
        $_totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        //----------------------- salary
        // $salaryLevel = TableRegistry::get('TBLTSalaryLevel')
        //     ->getSalary($staff_id);
        $salarylevel = TableRegistry::get('TBLTSalaryLevel')
            ->find()
            ->select(['TBLMSalaryRange.Amount', 'TBLTSalaryLevel.DateUpdated'])
            ->join([
                'table' => 'tblMSalaryRange',
                'alias' => 'TBLMSalaryRange',
                'type' => 'LEFT',
                'conditions' => 'TBLMSalaryRange.SalaryLevel = TBLTSalaryLevel.SalaryLevel',
            ])
            ->where([
                'TBLTSalaryLevel.StaffID' => $staff_id,
            ])
            ->all();
        foreach ($salarylevel as $l) {
            $split = explode("/", $l->DateUpdated);
            if ($split[0] == $year && $split[1] == $month) {
                $salaryLevelAmount = str_replace(",", "", $l->TBLMSalaryRange['Amount']);
            }
        }
        $numberWorkingDays = Excel::getNumberOfWorkingDays($staff_id, $month, $year);
        $numberStaffWorkingDays = Excel::getNumberOfStaffWorkingDays($staff_id, $month, $year, $_isIntern);
        // var_dump($numberWorkingDays);
        // var_dump($numberStaffWorkingDays);die;
        $cellMapping = Excel::_getCellMappings($staff_id, $currentRow, $date);
        // 1: basic salary
        $basicSalary = (int) $basicSalaryAll / $numberWorkingDays * $numberStaffWorkingDays;
        $basicSalaryFormula = "=" . $basicSalaryAll . "/" . $numberWorkingDays . "*" . $numberStaffWorkingDays;
        $sheet->setCellValue("D" . $cellMapping['basicSalary'], $basicSalaryFormula);
        $data_salary_staff['basic_salary'] = $basicSalaryAll / $numberWorkingDays * $numberStaffWorkingDays;
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['basic_salary'];

        // 2. Performance fee
        // $performanceFee = ((int) $salaryLevelAmount - (int) $basicSalaryAll) / $numberWorkingDays * $numberStaffWorkingDays;
        $performanceFee = "=(" . $salaryLevelAmount . "-" . $basicSalaryAll . ")" . "/" . $numberWorkingDays . "*" . $numberStaffWorkingDays;
        $sheet->setCellValue("D" . $cellMapping['performanceFee'], $performanceFee);
        $data_salary_staff['performance_fee'] = ($salaryLevelAmount - $basicSalaryAll) / $numberWorkingDays * $numberStaffWorkingDays;
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['performance_fee'];

        // 3: Shift 1 || Meal Allowance data
        // $shiftSalary1 = Excel::countShiftNum(1, $staff_id, $month, $year);
        if ($userData['TBLMStaff3']['Team'] == '3ka') {
            $sheet->setCellValue("A" . $cellMapping['shift1'], "Meal Allowance");
            $data_salary_staff['shift1Name'] = "Meal Allowance";
            $mealAllowance = Excel::_getMeal($staff_id, $numberWorkingDays, $salaryConfigs, $_totalDaysInMonth, $year, $month, $date, $timecards);
            $sheet->setCellValue("D" . $cellMapping['shift1'], $mealAllowance['formula']);
            $data_salary_staff['shift1Value'] = $mealAllowance['number'];
        } else {
            $data_salary_staff['shift1Name'] = "Shift 1";
            $shift1AllowanceCounter = Excel::_countValidAllowanceDays($staff_id, $date, $timecards, $cellMapping, $salaryConfigs, 1);
            $sheet->setCellValue("B" . $cellMapping['shift1'], $shift1AllowanceCounter);
            $data_salary_staff['shift1Counter'] = $shift1AllowanceCounter;
            $shift1AllowanceFomular = Excel::_calculateShift1Allowance($staff_id, $cellMapping, $salaryConfigs, $date);
            $sheet->setCellValue("D" . $cellMapping['shift1'], $shift1AllowanceFomular['formula']);
            $data_salary_staff['shift1Value'] = $shift1AllowanceCounter * $shift1AllowanceFomular['number'];
        }

        // 3: Shift 3 || BUD Allowance data
        // $shiftSalary3 = Excel::countShiftNum(3, $staff_id, $month, $year);
        if ($userData['TBLMStaff3']['Team'] == '3ka') {
            $sheet->setCellValue("A" . $cellMapping['shift3'], "BUD Skill Allowance");
            $data_salary_staff['shift3Name'] = "BUD Skill Allowance";
            $budSkillAllowance = Excel::_getBUDAllowance($staff_id, $numberWorkingDays, $salaryConfigs, $_totalDaysInMonth, $year, $month, $date, $timecards);
            $sheet->setCellValue("D" . $cellMapping['shift3'], $budSkillAllowance['formula']);
            $data_salary_staff['shift3Value'] = $budSkillAllowance['number'];
        } else {
            $data_salary_staff['shift3Name'] = "Shift 3";
            $shift3AllowanceCounter = Excel::_countValidAllowanceDays($staff_id, $date, $timecards, $cellMapping, $salaryConfigs, 3);
            $sheet->setCellValue("B" . $cellMapping['shift3'], ($shift3AllowanceCounter) ? $shift3AllowanceCounter : "0");
            $data_salary_staff['shift3Counter'] = $shift3AllowanceCounter;
            $sheet->setCellValue("D" . $cellMapping['shift3'], "=B" . $cellMapping['shift3'] . "*" . $shiftThree);
            $data_salary_staff['shift3Value'] = $shift3AllowanceCounter * $shiftThree;
        }

        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['shift1Value'];
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['shift3Value'];

        // 4. KyuOT (Min)
        $overtimeKyuMin = Excel::getOvertimeStaffDetails($staff_id, $month, $year);
        $kyuMinsBeforeTimeTotal = $overtimeKyuMin["CountBefore"];
        $kyuMinsAfterTimeTotal = $overtimeKyuMin["CountAfter"];

        $kyuMinTotal = $kyuMinsBeforeTimeTotal * ($kyuot / 100) + $kyuMinsAfterTimeTotal * ($overtimeAfterWeekDays / 100);
        $finalKyuTotal = $salaryLevelAmount / 22 / 8 / 60 * ($kyuMinTotal);
        $finalKyuTotalFormula = "=" . $salaryLevelAmount . "/22/8/60*(" . $kyuMinTotal . ")";

        if ($kyuMinTotal > 0) {
            $_kyuOTCellValue = ($kyuMinsAfterTimeTotal > 0) ? ($kyuMinsBeforeTimeTotal + $kyuMinsAfterTimeTotal) . "(" . $kyuMinsAfterTimeTotal . ")" : $kyuMinsBeforeTimeTotal;
            $sheet->setCellValue("B" . $cellMapping['kyuOT'], $_kyuOTCellValue);
            $data_salary_staff['kyuOTCounter'] = ($kyuMinsAfterTimeTotal > 0) ? ($kyuMinsBeforeTimeTotal + $kyuMinsAfterTimeTotal) . "(" . $kyuMinsAfterTimeTotal . ")" : $kyuMinsBeforeTimeTotal;
        }

        $sheet->setCellValue("D" . $cellMapping['kyuOT'], $finalKyuTotalFormula);
        $data_salary_staff['kyuOTValue'] = $salaryLevelAmount / 22 / 8 / 60 * $kyuMinTotal;
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['kyuOTValue'];
        // 5. ShuOT (Min)
        $overtimeShuOTMin = Excel::getOvertimeWeekendStaffDetails($staff_id, $month, $year);
        $shuOTMinsBeforeTimeTotal = $overtimeShuOTMin["CountBefore"];
        $shuOTMinsAfterTimeTotal = $overtimeShuOTMin["CountAfter"];
        $shuOTMinTotal = 0;

        if ($shuOTMinsBeforeTimeTotal > 0) {
            $shuOTMinTotal += ($shuOTMinsBeforeTimeTotal * (int) $overtimeBeforeWeekend / 100);
        }
        if ($shuOTMinsAfterTimeTotal > 0) {
            $shuOTMinTotal += ($shuOTMinsAfterTimeTotal * (int) $overtimeAfterWeekend / 100);
        }
        $finalShuOTTotal = $salaryLevelAmount / 22 / 8 / 60 * $shuOTMinTotal;
        $finalShuOTTotalFormula = "=" . $salaryLevelAmount . "/22/8/60*" . $shuOTMinTotal;

        if ($shuOTMinTotal > 0) {
            $_shuOTCellValue = ($shuOTMinsAfterTimeTotal > 0) ? ($shuOTMinsBeforeTimeTotal + $shuOTMinsAfterTimeTotal) . "(" . $shuOTMinsAfterTimeTotal . ")" : $shuOTMinsBeforeTimeTotal;
            $sheet->setCellValue("B" . $cellMapping['shuOT'], $_shuOTCellValue);
            $data_salary_staff['shuOTCounter'] = $_shuOTCellValue;
        }

        $sheet->setCellValue("D" . $cellMapping['shuOT'], $finalShuOTTotalFormula);
        $data_salary_staff['shuOTValue'] = $salaryLevelAmount / 22 / 8 / 60 * $shuOTMinTotal;
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['shuOTValue'];
        // 6. TetOT (Min)
        Excel::checkHolidayDate($sheet, $date, $staff_id, $salaryLevelAmount, $numberWorkingDays, $cellMapping['tetOT'], $tetOTBefore10, $tetOTAfter10);
        $data = Excel::checkHolidayDate($sheet, $date, $staff_id, $salaryLevelAmount, $numberWorkingDays, $cellMapping['tetOT'], $tetOTBefore10, $tetOTAfter10);
        if ($data['value'] != 0) {
            $data_salary_staff['tetOTCounter'] = $data['counter'];
            $data_salary_staff['tetOTValue'] = $data['value'];
        } else {
            $data_salary_staff['tetOTValue'] = $data['value'];
        }
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['tetOTValue'];
        // 7.Extra Wage
        $_extraWageMins = Excel::_calculateExtraWageMins($staff_id, $numberWorkingDays, $month, $year);
        $sheet->setCellValue("B" . $cellMapping['extraWage'], $_extraWageMins);
        $data_salary_staff['extra_wageCounter'] = $_extraWageMins;
        $sheet->setCellValue("D" . $cellMapping['extraWage'], "=ROUNDDOWN(B" . $cellMapping['extraWage'] . "/60,0)*10000");
        $data_salary_staff['extra_wageValue'] = floor($_extraWageMins / 60) * 10000;
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['extra_wageValue'];
        // 8.Late (Min)
        $lateBlockOfMins = (ceil($totalLateMins) % 30 === 0) ? ceil($totalLateMins) : round(($totalLateMins + 30 / 2) / 30) * 30;
        $sheet->setCellValue("B" . $cellMapping['late'], ($totalLateMins != 0) ? $totalLateMins : "");
        if ($totalLateMins != 0) {
            $data_salary_staff['lateCounter'] = $totalLateMins;
        } else {
            $data_salary_staff['lateCounter'] = "";
        }
        $sheet->setCellValue("D" . $cellMapping['late'], "=-(" . $salaryLevelAmount . "/22/8/60)*" . $lateBlockOfMins);
        $data_salary_staff['lateValue'] = -1 * abs($salaryLevelAmount / 22 / 8 / 60 * $lateBlockOfMins);
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['lateValue'];
        // 9.Parking fee
        /**
         * Update logic for programmer
         * 20200814 - Baonguyen
         */
        if ($_isProgrammer) {
            $sheet->setCellValue("A" . $cellMapping['parkingFee'], "Fine");
            $findFee = Excel::_calculateFine($staff_id, $year, $month, $salaryConfigs);
            $sheet->setCellValue("D" . $cellMapping['parkingFee'], $findFee);
            $data_salary_staff['parking_fee'] = $findFee;
        } else {
            $parkingFee = Excel::_calculateParkingFee($staff_id, $year, $month, $parkingFeeSetting);
            $sheet->setCellValue("D" . $cellMapping['parkingFee'], $parkingFee);
            $data_salary_staff['parking_fee'] = $parkingFee;
        }
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['parking_fee'];
        // 10.Leader??allowance
        $leaderAllowance = Excel::_calculateHighManagerAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $timecards, $date, $numberStaffWorkingDays);
        $data_salary_staff['leader_alName'] = "Leader Allowance";
        if ($userData['TBLMStaff3']['Position'] == "Shunin") {
            $sheet->setCellValue("A" . $cellMapping['leaderAllowance'], "Shunin allowance");
            $data_salary_staff['leader_alName'] = "Shunin allowance";
        }
        if (!$_isProgrammer && $leaderAllowance['number']) {
            $sheet->setCellValue("D" . $cellMapping['leaderAllowance'], $leaderAllowance['formula']);
            $data_salary_staff['leader_alValue'] = $leaderAllowance['number'];
        } elseif ($_isProgrammer && $userData['TBLMStaff3']['Position'] == "Leader") {
            $sheet->setCellValue("A" . $cellMapping['leaderAllowance'], "Leader allowance");
            $pgLeaderAllowance = Excel::_calculatePGLeaderAllowance($staff_id, $numberWorkingDays, $numberStaffWorkingDays);
            $data_salary_staff['leader_alValue'] = $pgLeaderAllowance['number'];
            $sheet->setCellValue("D" . $cellMapping['leaderAllowance'], $pgLeaderAllowance['formula']);
        } else {
            $sheet->setCellValue("D" . $cellMapping['leaderAllowance'], $leaderAllowance);
            $data_salary_staff['leader_alValue'] = 0;
        }

        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['leader_alValue'];
        // 11.Saturday allowance
        /**
         * Update logic for programmer
         * 20200814 - Baonguyen
         */
        if ($_isProgrammer) {
            $sheet->setCellValue("A" . $cellMapping['saturdayAllowance'], "System Allowance");
            $systemAllowance = Excel::_calculateSystemAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays, $timecards, $date);
            $sheet->setCellValue("D" . $cellMapping['saturdayAllowance'], $systemAllowance['formula']);
            $data_salary_staff['saturday_al'] = $systemAllowance['number'];
        } else {
            $saturdayAllowance = Excel::_calculateSaturDayAllowance($staff_id, $timecards, $salaryLevelAmount, $numberWorkingDays, $date);
            $sheet->setCellValue("D" . $cellMapping['saturdayAllowance'], $saturdayAllowance['formula']);
            $data_salary_staff['saturday_al'] = $saturdayAllowance['number'];
        }
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['saturday_al'];
        // 12.IT allowance
        // Update logic get IT Staff from Database - 20200709 - Baonguyen
        if (!in_array($staff_id, $itStaffs)) {
            $sheet->removeRow(($cellMapping['saturdayAllowance'] + 1));
        } else {
            $saturdayAllowance = Excel::_calculateITAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays, $timecards, $date);
            $sheet->setCellValue("D" . $cellMapping['itAllowance'], $saturdayAllowance['formula']);
            $data_salary_staff['it_al'] = $saturdayAllowance['number'];
            $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['it_al'];
        }

        // if (Excel::isJStaff($staff_id)) {
        // } else {
        //     // 12.N3
        //     $sheet->setCellValue("D" . $cellMapping['n3Allowance'], "0");
        //     // 14.N2
        //     $sheet->setCellValue("D" . $cellMapping['n2Allowance'], "0");
        //     // 15.N1
        //     $sheet->setCellValue("D" . $cellMapping['n1Allowance'], "0");
        //     // // 17. Accommodation
        //     // $sheet->setCellValue("D" . $cellMapping['accommodationAllowance'], "0");

        // }

        // 13.N3 = 800,000/(number of working days in a month) * number of days staff go to work.
        $n3 = ($userData['TBLMStaff3']['JLPTCertification'] == "N3") ? Excel::_calculateNAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $timecards, $date, $numberStaffWorkingDays) : 0;
        $sheet->setCellValue("D" . $cellMapping['n3Allowance'], $n3);
        $data_salary_staff['n3_al'] = $n3;
        // 14.N2 = 1,500,000/(number of working days in a month) * number of days staff go to work.
        $n2 = ($userData['TBLMStaff3']['JLPTCertification'] == "N2") ? Excel::_calculateNAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $timecards, $date, $numberStaffWorkingDays) : 0;
        $sheet->setCellValue("D" . $cellMapping['n2Allowance'], $n2);
        $data_salary_staff['n2_al'] = $n2;
        // 15.N1 = 2,000,000/(number of working days in a month) * number of days staff go to work.
        $n1 = ($userData['TBLMStaff3']['JLPTCertification'] == "N1") ? Excel::_calculateNAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $timecards, $date, $numberStaffWorkingDays) : 0;
        $sheet->setCellValue("D" . $cellMapping['n1Allowance'], $n1);
        $data_salary_staff['n1_al'] = $n1;

        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['n1_al'];
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['n2_al'];
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['n3_al'];
        // 17. Accommodation allowance = 1,000,000/(number of working days in a month) * number of days staff go to work.
        $accomAllowance = Excel::_calculateAccommodationAllowance($staff_id, $salaryConfigs, $numberWorkingDays, $timecards, $date, $numberStaffWorkingDays);
        if ($accomAllowance['formula']) {
            $sheet->setCellValue("D" . $cellMapping['accommodationAllowance'], $accomAllowance['formula']);
            $data_salary_staff['accommodation_al'] = $accomAllowance['number'];
        } else {
            $data_salary_staff['accommodation_al'] = 0;
            $sheet->setCellValue("D" . $cellMapping['accommodationAllowance'], $accomAllowance);
        }

        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['accommodation_al'];
        // 16.No off, no late
        $noOffNoLate = Excel::_calculateNoOffNoLate($staff_id, $totalLateMins, $year, $month, $noOffNoLateSetting);
        $sheet->setCellValue("D" . $cellMapping['noOffNoLate'], $noOffNoLate);
        $data_salary_staff['no_off'] = $noOffNoLate;
        $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['no_off'];

        /**
         * Created new Netsurf Allowance for HR 0004
         * 20200416
         * Baonguyen
         */
        // 18-2 .Netsurf Allowance for HR 0004
        if ($staff_id == "H0004") {
            $netsurfAllowance = Excel::_calculateNetsurfAllowanceExcel($staff_id, $salaryConfigs, $numberWorkingDays, $timecards, $date, $numberStaffWorkingDays);
            $sheet->setCellValue("D" . $cellMapping['netsurfAllowance'], $netsurfAllowance['formula']);
            $data_salary_staff['netsurf_al'] = $netsurfAllowance['number'];
            $data_salary_staff['total_amount'] = $data_salary_staff['total_amount'] + $data_salary_staff['netsurf_al'];
        } else {
            $sheet->removeRow(($cellMapping['accommodationAllowance'] + 1));
        }

        if ($_isPartInternPartStaff) {
            //Update formula for Trainee and Staff row - 20200507 - Baonguyen
            $data_salary_staff['is_PartInternPartStaff'] = true;
            $workingDetail = Excel::_getValidWorkingDayDetailForTrainee($staff_id, $numberWorkingDays, $date, $timecards);
            $sheet->setCellValue("A" . $cellMapping['traineeTotal'], "Trainee (85% " . $workingDetail['trainee']['period'] . ")");
            $data_salary_staff['traineeName'] = "Trainee (85% " . $workingDetail['trainee']['period'] . ")";
            // $sheet->setCellValue("B" . $cellMapping['traineeTotal'], $workingDetail['trainee']['count']);
            $sheet->setCellValue("D" . $cellMapping['traineeTotal'], "=(D" . $cellMapping['totalAmount'] . "/" . $numberWorkingDays . ")*" . $workingDetail['trainee']['businessDays'] . "*0.85");
            $data_salary_staff['traineeValue'] = $data_salary_staff['total_amount'] / $numberWorkingDays * $workingDetail['trainee']['businessDays'] * 0.85;

            $sheet->setCellValue("A" . $cellMapping['staffTotal'], "Staff (100% " . $workingDetail['staff']['period'] . ")");
            $data_salary_staff['staffName'] = "Staff (100% " . $workingDetail['staff']['period'] . ")";
            // $sheet->setCellValue("B" . $cellMapping['staffTotal'], $workingDetail['staff']['count']);
            $sheet->setCellValue("D" . $cellMapping['staffTotal'], "=(D" . $cellMapping['totalAmount'] . "/" . $numberWorkingDays . ")*" . $workingDetail['staff']['businessDays'] . "*1");
            $data_salary_staff['staffValue'] = $data_salary_staff['total_amount'] / $numberWorkingDays * $workingDetail['staff']['businessDays'];
        }

        //19. Insurance
        if ($_isIntern) {
            $sheet->setCellValue("A" . $cellMapping['insurance'], "Trial Tearm(85%)");
            $data_salary_staff['insuranceName'] = "Trial Tearm(85%)";
            $insurance = "=D" . ($cellMapping['insurance'] - 1) . "*0.85";
            $sheet->setCellValue("D" . $cellMapping['insurance'], $insurance);
            $data_salary_staff['insuranceValue'] = $data_salary_staff['total_amount'] * 0.85;
        } else {
            $sheet->setCellValue("A" . $cellMapping['insurance'], "Insurance (" . str_replace('.', ',', $insuranceSetting) . "%)");
            $data_salary_staff['insuranceName'] = "Insurance (" . str_replace('.', ',', $insuranceSetting) . "%)";
            $insurance = Excel::_calculateInsurance($staff_id, $year, $month);
            $sheet->setCellValue("D" . $cellMapping['insurance'], $insurance);
            $data_salary_staff['insuranceValue'] = $insurance;
        }
        //20. PIT
        $pit = Excel::_calculatePIT($staff_id, $year, $month);
        $sheet->setCellValue("D" . $cellMapping['pit'], $pit);
        $data_salary_staff['pit'] = $pit;

        if ($_isIntern) {
            $realPay = "=D" . $cellMapping['insurance'] . "-" . "D" . $cellMapping['pit'];
            $sheet->setCellValue("D" . $cellMapping['realPay'], $realPay);
            $data_salary_staff['real_pay'] = $data_salary_staff['insuranceValue'] - $data_salary_staff['pit'];
        }
        if ($_isPartInternPartStaff) {
            $data_salary_staff['TOTAL'] = $data_salary_staff['traineeValue'] + $data_salary_staff['staffValue'] - $data_salary_staff['insuranceValue'] - $data_salary_staff['pit'];
        } else {
            $data_salary_staff['TOTAL'] = $data_salary_staff['total_amount'] - $data_salary_staff['insuranceValue'] - $data_salary_staff['pit'];
        }

        //-------------------------End salary
        return $data_salary_staff;
    }

    private static function _getMeal($staffID, $numberWorkingDays, $salaryConfigs, $totalDaysInMonth, $year, $month, $dateExport, $timecards)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $configKey = Constants::SALARY_CONFIG_KEYS['mealAllowance'];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $mealAllowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);
        if ($experience > 1) {
            if ($experience >= 2) {
                $experience = 2;
            }

            if ($mealAllowance > 0) {
                $mealAllowance = $mealAllowance + (50000 * ($experience - 1));
            }
        }

        $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        $workingDateAsStaff = Excel::_getValidWorkingFullDateInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);

        $formula = '=(' . $mealAllowance . '/22)*' . $workingDateAsStaff;
        $number = ($mealAllowance / 22) * $workingDateAsStaff;
        $data = [];
        $data['formula'] = $formula;
        $data['number'] = $number;
        return $data;
    }

    private static function _getBUDAllowance($staffID, $numberWorkingDays, $salaryConfigs, $totalDaysInMonth, $year, $month, $dateExport, $timecards)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $configKey = Constants::SALARY_CONFIG_KEYS['budSkillAllowance'];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $mealAllowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);
        if ($experience > 1) {
            if ($experience >= 2) {
                $experience = 2;
            }

            $mealAllowance = $mealAllowance + (50000 * ($experience - 1));
        }

        $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        $workingDateAsStaff = Excel::_getValidWorkingFullDateInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);

        $formula = '=(' . $mealAllowance . '/22)*' . $workingDateAsStaff;
        $number = ($mealAllowance / 22) * $workingDateAsStaff;
        $data = [];
        $data['formula'] = $formula;
        $data['number'] = $number;
        return $data;
    }

    private static function _getCellMappings($staffID, $currentRow, $dateExport)
    {
        $itStaffs = TableRegistry::get('TBLMStaff3')->fetchITStaffsIds()->toArray();
        $itCase = [
            'basicSalary' => $currentRow,
            'performanceFee' => $currentRow + 1,
            'shift1' => $currentRow + 2,
            'shift3' => $currentRow + 3,
            'kyuOT' => $currentRow + 4,
            'shuOT' => $currentRow + 5,
            'tetOT' => $currentRow + 6,
            'extraWage' => $currentRow + 7,
            'late' => $currentRow + 8,
            'parkingFee' => $currentRow + 9,
            'leaderAllowance' => $currentRow + 10,
            'saturdayAllowance' => $currentRow + 11,
            'itAllowance' => $currentRow + 12,
            'n3Allowance' => $currentRow + 13,
            'n2Allowance' => $currentRow + 14,
            'n1Allowance' => $currentRow + 15,
            'noOffNoLate' => $currentRow + 16,
            'accommodationAllowance' => $currentRow + 17,
            'totalAmount' => $currentRow + 21,
            'insurance' => $currentRow + 22,
            'pit' => $currentRow + 23,
            'realPay' => $currentRow + 24,
        ];

        $normalCase = [
            'basicSalary' => $currentRow,
            'performanceFee' => $currentRow + 1,
            'shift1' => $currentRow + 2,
            'shift3' => $currentRow + 3,
            'kyuOT' => $currentRow + 4,
            'shuOT' => $currentRow + 5,
            'tetOT' => $currentRow + 6,
            'extraWage' => $currentRow + 7,
            'late' => $currentRow + 8,
            'parkingFee' => $currentRow + 9,
            'leaderAllowance' => $currentRow + 10,
            'saturdayAllowance' => $currentRow + 11,
            'n3Allowance' => $currentRow + 12,
            'n2Allowance' => $currentRow + 13,
            'n1Allowance' => $currentRow + 14,
            'noOffNoLate' => $currentRow + 15,
            'accommodationAllowance' => $currentRow + 16,
            'totalAmount' => $currentRow + 20,
            'insurance' => $currentRow + 21,
            'pit' => $currentRow + 22,
            'realPay' => $currentRow + 23,
        ];

        $partInternPartStaff = [
            'basicSalary' => $currentRow,
            'performanceFee' => $currentRow + 1,
            'shift1' => $currentRow + 2,
            'shift3' => $currentRow + 3,
            'kyuOT' => $currentRow + 4,
            'shuOT' => $currentRow + 5,
            'tetOT' => $currentRow + 6,
            'extraWage' => $currentRow + 7,
            'late' => $currentRow + 8,
            'parkingFee' => $currentRow + 9,
            'leaderAllowance' => $currentRow + 10,
            'saturdayAllowance' => $currentRow + 11,
            'n3Allowance' => $currentRow + 12,
            'n2Allowance' => $currentRow + 13,
            'n1Allowance' => $currentRow + 14,
            'noOffNoLate' => $currentRow + 15,
            'accommodationAllowance' => $currentRow + 16,
            'totalAmount' => $currentRow + 19,
            'traineeTotal' => $currentRow + 20,
            'staffTotal' => $currentRow + 21,
            'insurance' => $currentRow + 22,
            'pit' => $currentRow + 23,
            'realPay' => $currentRow + 24,
        ];

        /**
         * Add new case for HR 0004
         * 20200416
         * Baonguyen
         */
        $hrSpecialStaff = [
            'basicSalary' => $currentRow,
            'performanceFee' => $currentRow + 1,
            'shift1' => $currentRow + 2,
            'shift3' => $currentRow + 3,
            'kyuOT' => $currentRow + 4,
            'shuOT' => $currentRow + 5,
            'tetOT' => $currentRow + 6,
            'extraWage' => $currentRow + 7,
            'late' => $currentRow + 8,
            'parkingFee' => $currentRow + 9,
            'leaderAllowance' => $currentRow + 10,
            'saturdayAllowance' => $currentRow + 11,
            'n3Allowance' => $currentRow + 12,
            'n2Allowance' => $currentRow + 13,
            'n1Allowance' => $currentRow + 14,
            'noOffNoLate' => $currentRow + 15,
            'accommodationAllowance' => $currentRow + 16,
            'netsurfAllowance' => $currentRow + 17,
            'totalAmount' => $currentRow + 21,
            'insurance' => $currentRow + 22,
            'pit' => $currentRow + 23,
            'realPay' => $currentRow + 24,
        ];

        // Update logic get IT Staff from Database - 20200709 - Baonguyen
        if (in_array($staffID, $itStaffs)) {
            return $itCase;
        }

        if (Excel::_isPartInternPartStaff($staffID, $dateExport)) {
            return $partInternPartStaff;
        }

        /**
         * Add new case for HR 0004
         * 20200416
         * Baonguyen
         */
        if ($staffID == "H0004") {
            return $hrSpecialStaff;
        }

        return $normalCase;
    }

    private static function _calculateITAllowance($staffID, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays, $timecards, $dateExport)
    {
        $configKey = Constants::SALARY_CONFIG_KEYS['itAllowance'];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $itAllowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        $workingDateAsStaff = Excel::_getNumberOfStaffWorkingDaysInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);

        $data = [];
        $data['formula'] = "=(" . $itAllowance . "/" . $numberWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = $itAllowance / $numberWorkingDays * $numberStaffWorkingDays;
        return $data;
    }

    private static function _calculateSystemAllowance($staffID, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays, $timecards, $dateExport)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);

        $configKey = Constants::SALARY_CONFIG_KEYS['system_allowance'];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $systemAllowance = ($config && isset($config)) ? $salaryConfigs{$config}['Value'] : 0;

        if (!$userData['TBLMStaff3']['SystemAllowance']) $systemAllowance = 0;
        $data = [];
        $data['formula'] = "=(" . $systemAllowance . "/" . $numberWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = $systemAllowance / $numberWorkingDays * $numberStaffWorkingDays;
        return $data;
    }

    private static function _calculatePGLeaderAllowance($staffID, $numberWorkingDays, $numberStaffWorkingDays)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $pgLeaderAllowance = 1000000;
        $data = [];
        $data['formula'] = "=(" . $pgLeaderAllowance . "/" . $numberWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = $pgLeaderAllowance / $numberWorkingDays * $numberStaffWorkingDays;
        return $data;
    }

    private static function _calculateNoOffNoLate($staffID, $totalLateMins, $year, $month, $noOffNoLateSetting)
    {
        $salaryMetaData = TableRegistry::get('TBLTSalaryMeta')->getCurrentSalaryMetaData($staffID, $year, $month);
        $isNoOffNoLate = $salaryMetaData['NoOffNoLate'];
        return ($isNoOffNoLate == true) ? $noOffNoLateSetting : 0;
    }

    private static function _calculateInsurance($staffID, $year, $month)
    {
        $salaryMetaData = TableRegistry::get('TBLTSalaryMeta')->getCurrentSalaryMetaData($staffID, $year, $month);
        $insuranceVal = $salaryMetaData['Insurance'];
        return ($insuranceVal) ? $insuranceVal : 0;
    }

    private static function _calculatePIT($staffID, $year, $month)
    {
        $salaryMetaData = TableRegistry::get('TBLTSalaryMeta')->getCurrentSalaryMetaData($staffID, $year, $month);
        $pitVal = $salaryMetaData['PIT'];
        return ($pitVal) ? $pitVal : 0;
    }

    private static function _calculateParkingFee($staffID, $year, $month, $parkingFeeSetting)
    {
        $salaryMetaData = TableRegistry::get('TBLTSalaryMeta')->getCurrentSalaryMetaData($staffID, $year, $month);
        $isParking = $salaryMetaData['Parking'];
        return ($isParking == true) ? ($parkingFeeSetting * (-1)) : 0;
    }

    private static function _calculateFine($staffID, $year, $month, $parkingFeeSetting)
    {
        $salaryMetaData = TableRegistry::get('TBLTSalaryMeta')->getCurrentSalaryMetaData($staffID, $year, $month);
        return ($salaryMetaData['Fine']) ? ($salaryMetaData['Fine'] * (-1)) : 0;
    }

    private static function _getMinimumSalaryLevel($staffID, $dateExport)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $jpCertificated = ['N1', 'N2', 'N3'];
        if (in_array($userData['TBLMStaff3']['JLPTCertification'], $jpCertificated) && strpos($staffID, 'H') === false) {
            $miniumLevel = 40;
            $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);
            return "S-" . ($miniumLevel + (10 * ($experience)));
        } elseif (strpos($staffID, 'H') !== false) {
            /**
             * Add new case for HR
             * 20200416
             * Baonguyen
             */
            $miniumLevel = 80;
            $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);
            return "S-" . ($miniumLevel + (10 * ($experience)));
        } else {
            $miniumLevel = 0;
            $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);
            if ($experience == 0) {
                return "S-0";
            }

            return "S-" . ($miniumLevel + (10 * ($experience)));
        }
    }

    private static function _calculateSaturDayAllowance($staffID, $timecards, $totalSalary, $numberWorkingDays, $dateExport)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);

        // Update Saturday working counter logic - 20200803 - Baonguyen
        $tblSaturdayWorking = TableRegistry::get('TBLSaturdayWorking');
        $dateExport = new \DateTime($dateExport);
        $totalWorkingOnSat = $tblSaturdayWorking->find('all')->where([
            'StaffID' => $staffID,
            'SaturdayDate LIKE' => $dateExport->format('Y-m')."%"
        ])->count();

        // $totalWorkingOnSat = 0;
        // foreach ($timecards as $timecard) {
        //     if ((date('N', strtotime($timecard->Day)) == 6)) {
        //         if ($timecard->TotalTime != 0) {
        //             $totalWorkingOnSat++;
        //         }
        //     }
        // }
        $bonusStep = Excel::_generateYearOfExpirienceAmount($experience);
        $formula = "=(%d-%d)/%d*%d*200%%";
        $result = sprintf($formula, $totalSalary, $bonusStep, 22, $totalWorkingOnSat);
        $data = [];
        $data['number'] = ($totalSalary - $bonusStep) / 22 * $totalWorkingOnSat * 2;
        $data['formula'] = $result;
        return $data;
    }

    private static function _calculateExperienceYear($experienceStartDate, $dayExport)
    {
        $experienceStartDate = new \DateTime($experienceStartDate);
        $now = new \DateTime($dayExport);
        $diff = $now->diff($experienceStartDate);
        $experience = $diff->y;
        return $experience;
    }

    private static function _generateYearOfExpirienceAmount($experience)
    {
        if ($experience < 1) {
            return 0;
        }

        // if ($experience == 2) return 250000;
        return (250000 * ($experience));
    }

    private static function _calculateHighManagerAllowance($staffID, $salaryConfigs, $numberWorkingDays, $timecards, $dateExport, $numberStaffWorkingDays)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $bossPositions = ['Leader', 'Shunin'];
        if (!in_array($userData['TBLMStaff3']['Position'], $bossPositions)) {
            return 0;
        }

        $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        $workingDateAsStaff = Excel::_getValidWorkingFullDateInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);

        $result = ($userData['TBLMStaff3']['Position'] == "Leader") ?
            Excel::_calculateLeaderAllowance($userData, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays) :
            Excel::_calculateShuninAllowance($userData, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays);
        return $result;
    }

    private static function _calculateLeaderAllowance($userData, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays)
    {
        $positionAllowanceLevel = Excel::_getYearsHoldPosition($userData);
        if ($positionAllowanceLevel >= 36) {
            $positionAllowanceLevel = 36;
        }

        $configKey = Constants::SALARY_CONFIG_KEYS['leaderallowance' . $positionAllowanceLevel];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $leaderAllowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $data = [];
        //Update fomular 20200507 BaoNguyen
        $data['formula'] = "=(" . $leaderAllowance . "/" . $numberWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = ($leaderAllowance / $numberWorkingDays) * $numberStaffWorkingDays;
        if ($leaderAllowance == 0) {
            return "=(" . $leaderAllowance . "/" . $numberWorkingDays . ")*" . $numberStaffWorkingDays;
        } else {
            return $data;
        }
    }

    private static function _calculateShuninAllowance($userData, $salaryConfigs, $numberWorkingDays, $numberStaffWorkingDays)
    {
        $positionAllowanceLevel = Excel::_getYearsHoldPosition($userData);
        $configKey = Constants::SALARY_CONFIG_KEYS['shuninallowance' . $positionAllowanceLevel];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $leaderAllowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $data = [];
        $data['formula'] = "=(" . $leaderAllowance . "/" . $numberWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = $leaderAllowance / $numberWorkingDays * $numberStaffWorkingDays;
        return $data;
    }

    private static function _getYearsHoldPosition($userData)
    {
        if ($userData['TBLMStaff3']['PromotionDate'] == null) {
            return 6;
        }
        //BCK 20200507
        // $dateHoldPosition = time() - strtotime($userData['TBLMStaff3']['PromotionDate']);
        // $dateHoldPosition = round($dateHoldPosition / (60 * 60 * 24));
        // $yearHoldPosition = $dateHoldPosition / 360;
        // $monthHoldPosition = $yearHoldPosition * 12;
        // $multipleVal = 6;

        //Update logic calculate months between date - 20200507 - Baonguyen
        $startDate = new \DateTime(date("Y/m/d", strtotime($userData['TBLMStaff3']['PromotionDate'])));
        $endDate = new \DateTime(date("Y/m/d"));
        $months = $endDate->diff($startDate);
        $monthHoldPosition = (($months->y) * 12) + ($months->m);
        $multipleVal = 6;

        $positionAllowanceLevel = (round($monthHoldPosition) % $multipleVal === 0) ? round($monthHoldPosition) : round(($monthHoldPosition + $multipleVal / 2) / $multipleVal) * $multipleVal;
        return $positionAllowanceLevel;
    }

    private static function _calculateNAllowance($staffID, $salaryConfigs, $totalWorkingDays, $timecards, $dateExport, $numberStaffWorkingDays)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $validCertificate = ['N1', 'N2', 'N3', 'N4'];
        if (!in_array($userData['TBLMStaff3']['JLPTCertification'], $validCertificate)) {
            return 0;
        }

        // $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        // $workingDateAsStaff = Excel::_getValidWorkingFullDateInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);

        $configKey = Constants::SALARY_CONFIG_KEYS[$userData['TBLMStaff3']['JLPTCertification']];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $allowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        return ((float) $allowance / $totalWorkingDays) * $numberStaffWorkingDays;
    }

    private static function _calculateAccommodationAllowance($staffID, $salaryConfigs, $totalWorkingDays, $timecards, $dateExport, $numberStaffWorkingDays)
    {
        if (strpos($staffID, 'H') !== false) {
            return 0;
        }

        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $validCertificate = ['N1', 'N2', 'N3'];
        $exceptCase = ['A0026'];
        if (!in_array($userData['TBLMStaff3']['JLPTCertification'], $validCertificate) && !Excel::isJStaff($staffID) && !in_array($staffID, $exceptCase)) {
            return 0;
        }
        // $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        // $workingDateAsStaff = Excel::_getValidWorkingFullDateInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, false);

        $config = array_search("accommodation", array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $allowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $data = [];
        $data['formula'] = "=(" . $allowance . "/" . $totalWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = $allowance / $totalWorkingDays * $numberStaffWorkingDays;

        if ($allowance == 0) {
            return "=(" . $allowance . "/" . $totalWorkingDays . ")*" . $numberStaffWorkingDays;
        } else {
            return $data;
        }
    }

    /**
     * Created new calcumate NetsurfAllowance method
     * 20200416
     * Baonguyen
     */
    private static function _calculateNetsurfAllowanceExcel($staffID, $salaryConfigs, $totalWorkingDays, $timecards, $dateExport, $numberStaffWorkingDays)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);

        $config = array_search("netsurf_allowance", array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $allowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $data = [];
        $data['formula'] = "=(" . $allowance . "/" . $totalWorkingDays . ")*" . $numberStaffWorkingDays;
        $data['number'] = $allowance / $totalWorkingDays * $numberStaffWorkingDays;

        return $data;
    }

    private static function _calculateShift1Allowance($staffID, $cellMapping, $salaryConfigs, $dateExport)
    {
        $userData = TableRegistry::get('TBLMStaff')->getUserData($staffID);
        $experience = Excel::_calculateExperienceYear($userData['TBLMStaff3']['ExperienceStartDate'], $dateExport);
        $experience = $experience + 1; //!NEED CHECK THIS CASE
        $configKey = ($experience < 3) ? Constants::SALARY_CONFIG_KEYS['shift_1'] : Constants::SALARY_CONFIG_KEYS['shift_1_3'];
        $config = array_search($configKey, array_map(function ($o) {
            return $o->Code;
        }, $salaryConfigs));
        $shit1Allowance = ($config && isset($config)) ? $salaryConfigs{
        $config}['Value'] : 0;
        $data = [];
        $data['formula'] = "=B" . $cellMapping['shift1'] . "*" . $shit1Allowance;
        $data['number'] = $shit1Allowance;
        return $data;
    }

    private static function _countValidAllowanceDays($staffID, $dateExport, $timecards, $cellMapping, $salaryConfigs, $shift)
    {
        if (strpos($staffID, 'H') !== false) {
            return 0;
        }

        $traineeAndStaffWorkingDayInMonth = Excel::_getTraineeAndStaffWorkingDayInMonth($staffID, $dateExport);
        $workingDateAsStaff = Excel::_getValidWorkingFullDateInRange($staffID, $timecards, $traineeAndStaffWorkingDayInMonth['staff']['fromDate'], $traineeAndStaffWorkingDayInMonth['staff']['toDate'], $dateExport, $shift, true);
        return $workingDateAsStaff;
    }

    private static function countShiftNum($shiftNumber, $staff_id, $month, $year)
    {

        $timecards = TableRegistry::get('tblTTimeCard')->find()->select()
            ->join([
                'table' => 'tblTALApply',
                'alias' => 'c',
                'type' => 'LEFT',
                'conditions' => 'c.StaffID = tblTTimeCard.StaffID',
            ])
            ->where([
                "tblTTimeCard.StaffID" => $staff_id,
                'MONTH(tblTTimeCard.Day)' => $month,
                'YEAR(tblTTimeCard.Day)' => $year,
                'OR' => [
                    'tblTTimeCard.Day <' => 'c.FromDate',
                    'tblTTimeCard.Day >' => 'c.ToDate',
                ],
            ])
            ->group(['tblTTimeCard.Day'])->all()->toArray();
        $count = 0;
        foreach ($timecards as $timecard) {
            $shiftSalary = TableRegistry::get('TBLTShift')->find()->select()
                ->where([
                    "StaffID" => $staff_id,
                    'MONTH(TBLTShift.Date)' => $month,
                    'YEAR(TBLTShift.Date)' => $year,
                    'Shift' => $shiftNumber,
                    'TBLTShift.Date' => $timecard->Day,
                ])->first();
            if ($shiftSalary != null) {
                $count++;
            }
        }
        return $count;
    }
    // get all salary items
    private static function retrieveSalaryItems()
    {
        $salaryItems = TableRegistry::get('TBLMItem')
            ->find()
            ->all()->toArray();
        return $salaryItems;
    }
}
