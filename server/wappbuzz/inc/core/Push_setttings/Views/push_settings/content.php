<div class="mb-5">
    <h2><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Web Push settings")?></h2>
</div>

<div class="card b-r-10 border mb-4">
    <div class="card-header">
        <div class="card-title"><?php _e("VAPID Configuration")?></div>
    </div>
    <div class="card-body">

        <div class="mb-4">
            <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row w-100 p-25 mb-10 b-r-6">
                <span class="fs-30 me-4 mb-5 mb-sm-0 text-primary">
                    <i class="fad fa-link"></i>
                </span>
                <div class="d-flex flex-column pe-0 pe-sm-10">
                    <div class="mb-1 fw-6"><?php _e("Click this link to create VAPID:")?></div>
                    <span class="m-b-0"><a href="https://vapidkeys.com/" target="_blank" >https://vapidkeys.com/</a></span>
                </div>
            </div>
        </div>

        
        <div class="mb-3">
            <label class="form-label"><?php _e("Subject")?></label>
            <input type="text" class="form-control" name="push_subject" value="<?php _ec( get_option("push_subject", "") )?>">
        </div>
        <div class="mb-3">
            <label class="form-label"><?php _e("Public key")?></label>
            <input type="text" class="form-control" name="push_public_key" value="<?php _ec( get_option("push_public_key", "") )?>">
        </div>
        <div class="mb-3">
            <label class="form-label"><?php _e("Private key")?></label>
            <input type="text" class="form-control" name="push_private_key" value="<?php _ec( get_option("push_private_key", "") )?>">
        </div>
    </div>
</div>

<div class="card b-r-10 border mb-4">
    <div class="card-header">
        <div class="card-title"><?php _e("Website Configuration")?></div>
    </div>
    <div class="card-body">
                        
        <div class="d-flex">
            <div class="mb-3 me-4">
                <div class="w-100">
                    <div><label for="push_website_icon" class="form-label"><?php _e('Default Website Icon')?></label></div>
                    <div class="mb-4 border p-20 d-inline-block rounded b-r-10 w-200">
                        <img src="<?php _ec( get_option("push_website_icon", get_module_path( __DIR__, "../../Push/Assets/img/bell_icon.png") ) )?>" class="img-thumbnail push_website_icon mw-100 mb-3 w-150 h-150 b-r-10">
                        <input type="text" name="push_website_icon" id="push_website_icon" class="form-control form-control-solid d-none widget-icon" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_option("push_website_icon", get_module_path( __DIR__, "../../Push/Assets/img/bell_icon.png") ) )?>">
                        <div class="input-group w-100 ">
                            <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="push_website_icon">
                                <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <dib class="mb-3">
                <div><label for="website_badge" class="form-label"><?php _e('Default Badge Icon')?></label></div>
                <div class="mb-4 border p-20 d-inline-block rounded b-r-10 w-200">
                    <img src="<?php _ec( get_option("push_website_badge_icon", get_module_path( __DIR__, "../../Push/Assets/img/badge_icon.png") ) )?>" class="img-thumbnail badge_icon mw-100 mb-3 w-150 h-150 b-r-10">
                    <input type="text" name="badge" id="badge_icon" class="form-control d-none" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_option("push_website_badge_icon", get_module_path( __DIR__, "../../Push/Assets/img/badge_icon.png") ) )?>">
                    <div class="input-group w-100 ">
                        <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="badge_icon">
                            <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                        </button>
                    </div>
                </div>
            </dib>

        </div>


    </div>
</div>

<div class="card b-r-10 border">
    <div class="card-header">
        <div class="card-title"><?php _e("Advance Configuration")?></div>
    </div>
    <div class="card-body">
                        
        <div class="mb-3">
            <label class="form-label"><?php _e("Delay notifications (minutes)")?></label>
            <select class="form-select mb-1" name="push_time_post">
                <?php for ($i = 0; $i <= 10; $i++): ?>
                    <option value="<?php _ec($i)?>" <?php _ec( get_option("push_time_post", "1") == $i?"selected":"" )?> ><?php _ec($i)?></option>
                <?php endfor; ?>
            </select>
            <div class="text-gray-700"><?php _e("Configure the interval time for the next notifications during a single scheduled campaign")?></div>
        </div>

        <div class="mb-3">
            <label class="form-label"><?php _e("Try times")?></label>
            <select class="form-select mb-1" name="push_try_times">
                <?php for ($i = 0; $i <= 5; $i++): ?>
                    <option value="<?php _ec($i)?>" <?php _ec( get_option("push_try_times", 1) == $i?"selected":"" )?> ><?php _ec($i)?></option>
                <?php endfor; ?>
            </select>
            <div class="text-gray-700"><?php _e("If a notification is sent unsuccessfully, it will make another attempt to send")?></div>
        </div>

        <div class="mb-3">
            <label class="form-label"><?php _e("Default Notification Expiry (hours)")?></label>
            <input type="text" class="form-control" name="push_notification_expiry" value="<?php _ec( get_option("push_notification_expiry", 24) )?>">
        </div>

    </div>
</div>


