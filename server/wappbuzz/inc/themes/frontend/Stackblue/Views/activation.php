<div class="w-100 p-t-50 p-b-50">
    <?php if ($status): ?>
        <div class="">
            <h1 class="text-success"><i class="fad fa-shield-check fs-70"></i></h1>
            <h5 class="fw-6 text-success mb-4"><?php _e("Activation successful")?></h5>
            <p class="mb-4"><?php _e("Thank you for choosing us. Sign in and get started.")?></p>
            <a href="<?php _ec( base_url("login") )?>" class="btn mb-2 btn-dark w-100 mb-md-3 fw-6 text-uppercase fs-16"><?php _e("Login")?></a>
        </div>
    <?php else: ?>
        <div class="">
            <h1 class="text-danger"><i class="fad fa-frown-open fs-70"></i></h1>
            <h5 class="fw-6 text-danger mb-4"><?php _e("Activation unsuccessful")?></h5>
            <p class="mb-4"><?php _e("Incorrect or invalid activation code")?></p>
            <a href="<?php _ec( base_url("resend_activation") )?>" class="btn mb-2 btn-dark w-100 mb-md-3 fw-6 text-uppercase fs-16"><?php _e("Resend activation email")?></a>
        </div>
    <?php endif ?>
</div>
