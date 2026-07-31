<div class="container">
    <div class="px-4 py-5 mih-1000">
        <div class="mw-700 text-center mx-auto m-b-70">
            <h1 class="fs-40 fw-6"><?php _e("Privacy Policy")?></h1>
            <p class="fs-16"><?php _e("The information below provides details about our privacy policy and we ask that you take the time to read it.")?></p>
        </div>
    
        <?php _ec( htmlspecialchars_decode( get_option("privacy_policy", ""), ENT_QUOTES) )?>
    </div>
</div>