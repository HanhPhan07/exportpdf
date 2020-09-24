<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A fully featured admin theme which can be used to build CRM, CMS, etc.">
    <meta name="author" content="Coderthemes">

    <!-- App favicon -->
    <link rel="shortcut icon" href="<?php echo $this->Url->build('/', true); ?>img/favicon.ico">
    <!-- App title -->
    <title>NSV New System</title>

    <!-- App css -->
    <?php
    echo $this->Html->css('bootstrap.min.css').PHP_EOL;
    echo $this->Html->css('jquery-ui.min.css').PHP_EOL;
    echo $this->Html->css('nestable/jquery.nestable.css').PHP_EOL;
    echo $this->Html->css('icons.css?'.date('YmdHis')).PHP_EOL;
    echo $this->Html->css('admin/style.css?'.date('YmdHis')).PHP_EOL;
    echo $this->Html->css('custom.css?ver='.filemtime("css/custom.css")).PHP_EOL;
    echo $this->Html->css('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.12/css/select2.min.css').PHP_EOL;
    ?>

    <script type="text/javascript">
        var __baseUrl = "<?php echo $this->Url->build('/', true); ?>";
        var baseUrl   = "<?php echo $this->Url->build('/', true); ?>";
        var csrfToken = '<?php echo $this->request->getParam('_csrfToken') ?>';
    </script>

    <?php echo $this->fetch('head-end'); ?>
</head>
<body>


<!-- Navigation Bar-->
<header id="topnav" class="">
    <div class="topbar-main navbar p-0 ">
        <div class="container-fluid">

            <!-- Logo container-->
            <div class="topbar-left">
                <a href="#" >
                    <img src="<?php echo $this->Url->build('/', true); ?>/img/logo-in.png" alt="" class="logo1 float-left">
                </a>
                <div class="mt-1a float-left" style="color:#145296">NSV Vietnam Company</div>
                <div class="clearfix"></div>
            </div>
            <!-- End Logo container-->
            <div class="menu-extras">
                <ul class="nav navbar-right float-right">
                    <li class="dropdown navbar-c-items">
                        <a href="<?php echo $this->Url->build('/', true); ?>admin/users/logout"><i class="fas fa-power-off"></i> Logout </a>
                    </li>
                </ul>

            </div>
            <!-- end menu-extras -->
        </div> <!-- end container-fluid -->
    </div>
    <!-- end topbar-main -->
</header>
<!-- End Navigation Bar-->

<div class="wrapper">
    <div class="container-fluid">
        <?php echo $this->fetch('content'); ?>
    </div>
    <!-- Footer -->
    <footer class="footer text-right">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center">
                    Copyright © 2019 by NSV. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
    <!-- End Footer -->
</div>
<!-- End wrapper -->
    <!-- jQuery  -->
    <?php
    echo $this->Html->script('jquery.min.js').PHP_EOL;
    echo $this->Html->script('jquery-ui.min.js') . PHP_EOL;
    echo $this->Html->script('jquery.ui.monthpicker.js') . PHP_EOL;
    echo $this->Html->script('bootstrap.bundle.min.js').PHP_EOL;
    echo $this->Html->script('detect.js').PHP_EOL;
    echo $this->Html->script('fastclick.js').PHP_EOL;
    echo $this->Html->script('jquery.blockUI.js').PHP_EOL;
    echo $this->Html->script('waves.js').PHP_EOL;
    echo $this->Html->script('jquery.slimscroll.js').PHP_EOL;
    echo $this->Html->script('jquery.scrollTo.min.js').PHP_EOL;
    echo $this->Html->script('date_time.js').PHP_EOL;
    echo $this->Html->script('sweetalert.min.js').PHP_EOL;
    echo $this->Html->script('common_module.js').PHP_EOL;
    echo $this->Html->script('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.12/js/select2.full.min.js').PHP_EOL;

    ?>
    <!-- App js -->
    <?php
    echo $this->Html->script('jquery.core.js').PHP_EOL;
    echo $this->Html->script('jquery.app.js').PHP_EOL;
    ?>

    <?php if (file_exists(WWW_ROOT . 'js' . DS . 'admin' . DS .mb_strtolower($this->request->getParam('controller')) . '.js')): ?>
        <?= $this->Html->script('admin/'.mb_strtolower($this->request->getParam('controller')).'.js?='.filemtime(WWW_ROOT . 'js' . DS . 'admin' . DS .mb_strtolower($this->request->getParam('controller')) . '.js')); ?>
    <?php endif;?>

    <?php echo $this->fetch('body-end'); ?>
</body>
</html>
