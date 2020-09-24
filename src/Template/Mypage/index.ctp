<?php
echo $this->Html->script("admin/export.js?v=" . filemtime('js/admin/export.js'), ['block' => 'body-end']);
echo $this->Html->script("admin/salary.js", ['block' => 'body-end']);
echo $this->Html->script("admin/customer.js", ['block' => 'body-end']);
echo $this->Html->css("admin/style4.css", ['block' => 'head-end']);

?>
<!-- Page-Title -->
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <ol class="breadcrumb float-right p-0 m-0 mb-1">
                <li class="breadcrumb-item">
                    <a href="#">Admin page</a>
                </li>
            </ol>
            <h4 class="page-title"></h4>
        </div>
        <div class="clearfix"></div>
        <div class="w-100 mt-5">
            <div class="div-left card1 blog-shadow-dreamy">
                <div id="label-user">MASTER</div>
                <div class="reg-buttons">
                    <button type="button" class="btn btn-primary btn-sm btn-block text-left"  data-toggle="modal" data-target="#exportModal">Staff Contact List</button>
                    <button type="button" id="btn-staff-info-export" class="btn btn-primary btn-sm btn-block text-left">Staff Information List<i class="far fa-file-excel float-right mt-1"></i></button>
                    <button type="button" id="confirm-list" class="btn btn-primary btn-sm btn-block text-left">Confirmation List<i class="far fa-file-excel float-right mt-1"></i></button>
                    <button type="button" id="btn-holiday" class="btn btn-primary btn-sm btn-block text-left">Holiday Setting</button>
                    <button type="button" id="btn-export-organizational-systems" onclick="window.location.href='/admin/export/organizationSystemsView'" class="btn btn-primary btn-sm btn-block text-left" class="btn btn-primary btn-sm btn-block text-left">NSV Organization Chart</button>
                    <button type="button" onclick="window.location.href='/admin/company-regulations'" class="btn btn-primary btn-sm btn-block text-left">Company Regulations</button>
                    <button type="button" onclick="window.open('/admin/customer','_blank')" class="btn btn-primary btn-sm btn-block text-left">Customer</button>
                    <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-body">
                                Choice, please<br>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Excel</button>
                                <button type="button" class="btn btn-primary" onclick="window.location.href=('/exportPDF')">PDF</button>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            <!-- end col -->

            <div class="div-right card1 blog-shadow-dreamy">
                <div id="label-user">TRANSACTION</div>
                <div class="reg-buttons">
                    <button type="button" id="staff-level" class="btn btn-primary btn-sm btn-block text-left">Staff Level <i class="far fa-file-excel float-right mt-1"></i></button>
                    <button type="button" id="shift-leave" class="btn btn-primary btn-sm btn-block text-left">Shift Leave<i class="far fa-file-excel float-right mt-1"></i></button>
                    <button type="button" id="show-yellow-card-modal" class="btn btn-primary btn-sm btn-block text-left">Yellow Card</button>

                    <button type="button" id="insert-shift" class="btn btn-primary btn-sm btn-block text-left">Insert Shift</button>
                    <button type="button" id="import-salary" class="btn btn-primary btn-sm btn-block text-left">Salary Import</button>
                    <button type="button" id="change-team" class="btn btn-primary btn-sm btn-block text-left">Change Team</button>
                    <button type="button" onclick="window.open('/admin/red-invoice','_blank')" class="btn btn-primary btn-sm btn-block text-left">Red Invoice</button>
                </div>
            </div>

            <div class="clearfix"></div>
            <div class="div-left card1 blog-shadow-dreamy">
                <div id="label-user">REPORT</div>
                <div class="reg-buttons">
                    <button type="button" id="staff-export-overtime" class="btn btn-primary btn-sm btn-block text-left">Staff Overtime<i class="far fa-file-excel float-right"></i></button>
                    <button type="button" id="btn-overtime-info-export" class="btn btn-primary btn-sm btn-block text-left">Overtime File Exported</button>
                    <button type="button" id="btn-export-overtime-aggregation" class="btn btn-primary btn-sm btn-block text-left">Overtime Aggregation<i class="far fa-file-excel float-right"></i></button>
                    <button type="button" id="btn-overtime-summary" class="btn btn-primary btn-sm btn-block text-left">Overtime Summary</button>
                    <button type="button" id="import-salary-detail" class="btn btn-primary btn-sm btn-block text-left">Salary Details</button>
                </div>
            </div>
            <!-- end col -->

            <div class="div-right card1 blog-shadow-dreamy">
                <div id="label-user">OTHERS</div>
                <div class="reg-buttons">
                    <button type="button" onclick="window.open('https://docs.google.com/spreadsheets/d/1xBZjhHAgRyaUe5UN8MlBeYBuicFxy2Q1xetH92Em-eI/edit#gid=1212451668','_blank')" class="btn btn-primary btn-sm btn-block text-left">Red Invoice Management</button>
                    <button type="button" onclick="window.open('https://docs.google.com/spreadsheets/d/1QCnGChPB8c3JPfUbVsCAWGbEE69AMIs_wlqqgy_8dl8/edit#gid=0', '_blank')" class="btn btn-primary btn-sm btn-block text-left">UPS Management</button>
                    <button type="button" onclick="window.open('https://docs.google.com/spreadsheets/d/1R0PtlBJ80IkkBTdkMIOuMYNVZl2S8wp_ofkNErJ6KcU/edit?ts=5ee0b646#gid=0','_blank')" class="btn btn-primary btn-sm btn-block text-left">Customer Management</button>
                    <button type="button" onclick="window.location.href='/admin/score'" class="btn btn-primary btn-sm btn-block text-left">Team Score</button>
                    <button type="button" onclick="window.location.href='/admin/album'" class="btn btn-primary btn-sm btn-block text-left">Album Management</button>
                    <button type="button" id="" onclick="window.location.href='/admin/test/result-analysis'" class="btn btn-primary btn-sm btn-block text-left">Test Result Analysis</button>
                    <button type="button" id="evaluation-period" class="btn btn-primary btn-sm btn-block text-left">Evaluation System</button>
                    <button type="button" id="login-as-staff" class="btn btn-primary btn-sm btn-block text-left">Login Each Staff</button>
                    <button type="button" id="btn-reset-password-staff" class="btn btn-primary btn-sm btn-block text-left">Reset Password Staff</button>
                </div>
            </div>
            <!-- end col -->
        </div>
        <!-- end row -->
    </div>
</div>
<!-- end page title end breadcrumb -->


