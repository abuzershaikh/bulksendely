<div class="card card-flush m-b-25 b-r-10 border">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bolder"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( $config['name'] )?></h3>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label"><?php _e("Auto add welcome notification when add new website")?></label>
            <select class="form-select push-welcome-status" name="push_welcome_status" data-control="select2">
                <option value="1" <?php _ec( get_option("push_welcome_status", 1) == "1"?"selected":"")?>><?php _e("Enable")?></option>
                <option value="0" <?php _ec( get_option("push_welcome_status", 1) == "0"?"selected":"")?>><?php _e("Disable")?></option>
            </select>
        </div>

        <div class="sp-otp-options">
            <div class="row">
                <div class="col-12">
                    <div class="w-100 mb-4">
                        <div><label for="push_welcome_large_image" class="form-label"><?php _e('Large image')?></label></div>
                        <?php echo view_cell('\Core\File_manager\Controllers\File_manager::select_image', [ "name" => "push_welcome_large_image", "value" => get_option("push_welcome_large_image", "")]) ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Title")?></label>
                        <input type="text" class="form-control push_welcome_text" name="push_welcome_text" value="<?php _ec( get_option("push_welcome_text", "Welcome") )?>">
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Message")?></label>
                        <input type="text" class="form-control push_welcome_message" name="push_welcome_message" value="<?php _ec( get_option("push_welcome_message", "Thanks for subscribing to us!") )?>">
                    </div>
                </div>

              
            </div>
        </div>
    </div>
</div>

