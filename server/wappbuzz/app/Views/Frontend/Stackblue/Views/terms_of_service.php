<div class="container">
    <div class="px-4 py-5 mih-1000">
        <div class="mw-700 text-center mx-auto m-b-70">
            <h1 class="fs-40 fw-6"><?php _e("Terms & Conditions")?></h1>
            <p class="fs-16"><?php _e("The following information is important as it provides an overview of our terms of services, which we recommend you review.")?></p>
        </div>
    
        <?php _ec( htmlspecialchars_decode( get_option("terms_of_use", ""), ENT_QUOTES) )?>
    </div>
</div>