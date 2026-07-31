<div class="card card-flush m-b-25">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bolder"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e('WhatsApp API Configuration')?></h3>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-4">
            <label for="wa_menu_type" class="form-label"><?php _ec("Show all feautures on sidebar menu")?></label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_menu_type" id="wa_menu_type_disable" <?php _e( get_option('wa_menu_type', 0)  == 0?"checked":"" )?> value="0">
                    <label class="form-check-label" for="wa_menu_type_disable"><?php _e("Hide")?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_menu_type" id="wa_menu_type_enable" <?php _e( get_option('wa_menu_type', 0)  == 1?"checked":"" )?> value="1">
                    <label class="form-check-label" for="facebook_profile_status_enable"><?php _e("Show")?></label>
                </div>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="wa_paircode" class="form-label"><?php _ec("Enable Login With Phone/Pairing Code")?></label>
            <div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_paircode" id="wa_paircode_disable" <?php _e( get_option('wa_paircode', 0)  == 0?"checked":"" )?> value="0">
                    <label class="form-check-label" for="wa_paircode_disable"><?php _e("Disable")?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="wa_paircode" id="wa_paircode_enable" <?php _e( get_option('wa_paircode', 0)  == 1?"checked":"" )?> value="1">
                    <label class="form-check-label" for="wa_paircode_enable"><?php _e("Enable")?></label>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="whatsapp_server_url" class="form-label"><?php _e('WhatsApp Server URL')?></label>
            <input type="text" class="form-control form-control-solid" id="whatsapp_server_url" name="whatsapp_server_url" value="<?php _e( get_option("whatsapp_server_url", "") )?>" placeholder="https://example.com/">
        </div>
    </div>
</div>
