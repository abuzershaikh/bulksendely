<?php 
$request = \Config\Services::request();
if ( !$request->isAJAX() ) {
?>
    <?php 
        _ec( $this->extend('Backend\Stackmin\Views\index'), false);
    ?>

    <?php echo $this->section('content') ?>
    <?php _e( $this->include('Core\Push_opt_in_box\Views\sidebar'), false);?>
    
    <div class="main-wrapper flex-grow-1 n-scroll push-main">
        <?php echo $content ?>
    </div>

    <?php echo $this->endSection() ?>

<?php }else{ ?>

    <?php echo $content ?>

<?php } ?>