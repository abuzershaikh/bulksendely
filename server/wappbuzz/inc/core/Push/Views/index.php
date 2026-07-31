<?php 
$request = \Config\Services::request();
if ( !$request->isAJAX() ) {
?>
    <?php 
        _ec( $this->extend('Backend\Stackmin\Views\index'), false);
    ?>

    <?php echo $this->section('content') ?>

    <div class="main-wrapper flex-grow-1 n-scroll push-main">
        <?php
        $push_subscibers = check_push_subscibers();
        ?>
        
        <?php if ($push_subscibers['percent'] > 99): ?>
        <div class="container mx-auto m-t-40 m-b-20">
            <div class="alert alert-warning d-flex align-items-center b-r-10 mb-4">
                <div class="me-3"><i class="fad fa-info-circle fs-40"></i></div>
                <div>
                    <div><?php _e("You have reached the maximum number of subscribers allowed in your current plan. To continue adding new subscribers, please upgrade your plan.")?></div>
                </div>
            </div>
        </div>
        <?php endif ?>

        <?php echo $content ?>
    </div>

    <?php echo $this->endSection() ?>

<?php }else{ ?>

    <?php echo $content ?>

<?php } ?>