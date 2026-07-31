<?php
$request = \Config\Services::request();

_e($this->extend('Backend\Stackmin\Views\index'), false);
?>

<?php echo $this->section('content') ?>

<!-- <form class="main-wrapper flex-grow-1 n-scroll actionForm" action="<?php _e(get_module_url("save")) ?>" method="POST"> -->
<div class="main-wrapper flex-grow-1 n-scroll ">
    <div class="container my-5">
        <div class="w-100 m-r-0 d-flex align-items-center justify-content-between">
            <h3 class="fw-bolder m-b-0 text-gray-800"><i class="fad fa-plug text-primary"></i> Update Mods</h3>
            <a href="/plugins/popup_install/" class="btn btn-light btn-active-light-success m-r-1 b-r-10 actionItem btnInstallPlugin" data-popup="InstallModal"><i class="fad fa-file-archive text-success"></i> Install Mod</a>
            <a href="/plugins/reset" class="btn btn-light btn-active-light-danger m-r-1 b-r-10 actionItem"><i class="fad fa-redo text-danger"></i> Restart App</a>
        </div>


        <div class="mw-400 container d-flex align-items-center align-self-center h-100">
            <div>
                <div class="text-center px-4">
                    <img class="mw-100 mh-300px" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty.png">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- </form> -->

<?php echo $this->endSection() ?>