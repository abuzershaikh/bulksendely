<form class="actionForm" action="<?php _ec( base_url("auth/resend_activation") )?>" method="POST">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="w-100">
            <div class="headline mb-4">
                <h2 class="fs-25 fw-6 mb-0"><?php _e("Resend activation email")?></h2>
                <div class="text-gray-600 fs-12"><?php _e("Enter the email you registered to receive the activation email")?></div>
            </div>

            <div class="mb-3">
                <input type="text" name="email" class="form-control fs-12 h-45 b-r-6 border-gray-200" value="" placeholder="<?php _e("Enter your email")?>">
            </div>

            <?php if(get_option('google_recaptcha_status', 0)){?>
            <div class="g-recaptcha  mb-3" data-sitekey="<?=get_option('google_recaptcha_site_key', '')?>"></div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php }?>

            <div class="show-message mb-2 fs-12 fw-6"></div>

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