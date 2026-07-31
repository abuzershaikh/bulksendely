<form class="actionForm" action="<?php _ec( base_url("auth/recovery_password/".uri("segment", 2)) )?>" method="POST" data-redirect="<?php _ec( base_url("login") )?>">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="w-100">
            <div class="headline mb-4">
                <h2 class="fs-25 fw-6 mb-0"><?php _e("Reset Your Password")?></h2>
                <div class="text-gray-600 fs-12"><?php _e("Nearly there, just enter your new password.")?></div>
            </div>

            <div class="mb-3">
                <input type="password" name="new_password" class="form-control fs-12 h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Enter new password")?>">
            </div>

            <div class="mb-3">
                <input type="password" name="confirm_new_password" class="form-control fs-12 h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Enter confirm new password")?>">
            </div>

            <?php if(get_option('google_recaptcha_status', 0)){?>
            <div class="g-recaptcha  mb-3" data-sitekey="<?=get_option('google_recaptcha_site_key', '')?>"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php }?>

            <div class="show-message mb-2"></div>

            <div class="mb-3">
                <button type="submit" class="btn mb-2 btn-dark w-100 mb-md-3 fw-6 text-uppercase fs-16">
                    <?php _e("Submit")?>
                </button>
            </div>

            <?php if ( get_option("signup_status", 1) ): ?>
            <div class="mb-3 text-right fs-12">
                <?php _e("Don't have an account?")?> <a href="<?php _ec( base_url("signup") )?>"><?php _e("Sign Up")?></a>
            </div>
            <?php endif ?>
        </div>
    </div>
</form>