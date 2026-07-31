
    
    <div class="container my-5">

        <div class="mb-5">
            <h2><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Website settings")?></h2>
            <p><?php _e( $config['desc'] )?></p>
        </div>
        <form class="flex-grow-1 n-scroll actionForm" action="<?php _e( get_module_url("save") )?>" method="POST">
            <div class="card b-r-10 border mb-4">
                <div class="card-body">
                    <div class="mb-4">
                        <label for="website_name" class="form-label"><?php _e('Website')?></label>
                        <input type="text" class="form-control form-control-solid" readonly id="website_name" name="website_name" value="<?php _ec( get_data($result, "domain") )?>">
                    </div>
                    <div class="mb-4">
                        <label for="website_id" class="form-label"><?php _e('Website ID')?></label>
                        <input type="text" class="form-control form-control-solid" readonly id="website_id" name="website_id" value="<?php _ec( get_data($result, "ids") )?>">
                    </div>

                    <div class="d-flex ">
                        <div class="me-4">
                            <div><label for="website_icon" class="form-label"><?php _e('Default Icon')?></label></div>
                            <div class="mb-4 border p-20 d-inline-block rounded b-r-10">
                                <img src="<?php _ec( get_file_url( get_data($result, "icon") ) )?>" class="img-thumbnail icon mw-100 mb-3  w-150 h-150 b-r-10">
                                <input type="text" name="icon" id="icon" class="form-control d-none" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_data($result, "icon") )?>">
                                <div class="input-group w-100 ">
                                    <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="icon">
                                        <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div><label for="website_badge" class="form-label"><?php _e('Badge Icon')?></label></div>
                            <div class="mb-4 border p-20 d-inline-block rounded b-r-10">
                                <img src="<?php _ec( get_file_url( get_data($result, "badge") ) )?>" class="img-thumbnail badge_icon mw-100 mb-3  w-150 h-150 b-r-10">
                                <input type="text" name="badge" id="badge_icon" class="form-control d-none" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_data($result, "badge") )?>">
                                <div class="input-group w-100 ">
                                    <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="badge_icon">
                                        <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-none">
                        <div class="card-header px-0">
                            <div class="card-title fs-14"><?php _e("Default UTM Params")?></div>
                        </div>
                        <div class="card-body px-0 py-0">
                            <div class="mb-4">
                                <label for="utm_source" class="form-label"><?php _e('Source')?></label>
                                <input type="text" class="form-control" id="utm_source" name="utm_source" value="<?php _ec( get_data($result, "utm_source") )?>">
                            </div>
                            <div class="mb-4">
                                <label for="utm_medium" class="form-label"><?php _e('Medium')?></label>
                                <input type="text" class="form-control" id="utm_medium" name="utm_medium" value="<?php _ec( get_data($result, "utm_medium") )?>">
                            </div>
                            <div class="mb-4">
                                <label for="utm_name" class="form-label"><?php _e('Name')?></label>
                                <input type="text" class="form-control" id="utm_name" name="utm_name" value="<?php _ec( get_data($result, "utm_name") )?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><?php _e("Save")?></button>
                </div>
            </div>
        </form>

        <form class="flex-grow-1 n-scroll actionForm" action="<?php _e( get_module_url("save_ios") )?>" method="POST" data-redirect="">
            <div class="card b-r-10 border mb-4">
                <div class="card-header">
                    <div class="card-title">
                        <?php _e("iOS/iPadOS Configuration")?>
                    </div>
                </div>
                <div class="card-body pt-0">
                    
                    <div class="card shadow-none">
                        <div class="card-header px-0">
                            <div class="card-title card-title fs-14"><?php _e("Manifest File Builder")?></div>
                        </div>
                        <div class="card-body px-0">
                            <div class="alert alert-primary d-flex align-items-top b-r-10">
                                <div class="me-3"><i class="fad fa-info-circle fs-40"></i></div>
                                <div>
                                    <div class="fw-bold"><?php _e("Notification")?></div>
                                    <div><?php _e("Please fill in all the fields below to create the Manifest.json file")?></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-9">
                                    <div class="mb-4">
                                        <label class="form-label"><?php _e("Status")?></label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="ios_status" <?php _ec( (get_data($result, "ios_status") == 1 || get_data($result, "ios_status") == "")?"checked='true'":"" ) ?> id="ios_status_enable" value="1">
                                                <label class="form-check-label" for="ios_status_enable"><?php _e('Enable')?></label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="ios_status" <?php _ec( (get_data($result, "ios_status") == 0 )?"checked='true'":"" ) ?> id="ios_status_disable" value="0">
                                                <label class="form-check-label" for="ios_status_disable"><?php _e('Disable')?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="ios_short_name" class="form-label"><?php _e('Short Name')?></label>
                                        <input type="text" class="form-control" id="ios_short_name" name="ios_short_name" value="<?php _ec( get_data($result, "ios_short_name") )?>" maxlength="12" required="" autocomplete="off">
                                    </div>
                                    <div class="mb-4">
                                        <label for="ios_name" class="form-label"><?php _e('Name')?></label>
                                        <input type="text" class="form-control ios-name" id="ios_name" name="ios_name" value="<?php _ec( get_data($result, "ios_name") )?>"  maxlength="45" required="" autocomplete="off">
                                    </div>
                                    <div class="mb-4">
                                        <label for="ios_start_url" class="form-label"><?php _e('Start URL')?></label>
                                        <input type="text" class="form-control" id="ios_start_url" name="ios_start_url" value="<?php _ec( get_data($result, "ios_start_url") )?>" required="" autocomplete="off">
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label><?php _e("Background Color")?></label>
                                            <input type="text" class="form-control input-color ios-bg-color" name="ios_bg_color" value="<?php _ec( get_data($result, "ios_bg_color")?get_data($result, "ios_bg_color"):"#ffffff" )?>"  required="" autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label><?php _e("Theme Color")?></label>
                                            <input type="text" class="form-control input-color ios-theme-color" name="ios_theme_color" value="<?php _ec( get_data($result, "ios_theme_color")?get_data($result, "ios_theme_color"):"#ffffff" )?>" required="" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-4">
                                        <div><label for="ios_icon" class="form-label"><?php _e('Icon')?></label></div>
                                        <div class="border p-20 d-inline-block rounded b-r-10">
                                            <img src="<?php _ec( get_file_url( get_data($result, "ios_icon") ) )?>" class="img-thumbnail ios_icon mw-100 mb-3  w-100 mh-100 b-r-10">
                                            <input type="text" name="ios_icon" id="ios_icon" class="form-control d-none ios-icon" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_file_url(get_data($result, "ios_icon")) )?>" >
                                            <div class="input-group w-100 ">
                                                <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="ios_icon">
                                                    <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ( get_data($result, "ios_short_name") != "" && get_data($result, "ios_name") != "" && get_data($result, "ios_start_url") != "" && get_data($result, "ios_bg_color") != "" && get_data($result, "ios_theme_color") != "" && get_data($result, "ios_icon") != "" ): ?>
                                        <a href="<?php _ec( base_url("push/download_manifest_file") )?>" class="btn btn-secondary d-block b-r-10"><i class="fad fa-download"></i> <?php _e("Download manifest.json")?></a>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>

                        
                    </div>

                    <div class="card shadow-none">
                        <div class="card-header px-0">
                            <div class="card-title card-title fs-14"><?php _e("Display guidance popup for HomeScreen settings")?></div>
                        </div>
                        <div class="card-body px-0 pb-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label><?php _e("Text Color")?></label>
                                        <input type="text" class="form-control input-color ios-popup-text-color" name="ios_popup_text_color" value="<?php _ec( get_data($result, "ios_popup_text_color")?get_data($result, "ios_popup_text_color"):"#696969" )?>">
                                    </div>
                                    <div class="mb-3">
                                        <label><?php _e("Background Color")?></label>
                                        <input type="text" class="form-control input-color ios-popup-bg-color" name="ios_popup_bg_color" value="<?php _ec( get_data($result, "ios_popup_bg_color")?get_data($result, "ios_popup_bg_color"):"#fafafa" )?>">
                                    </div>
                                    <div class="mb-3">
                                        <label><?php _e("Text")?></label>
                                        <textarea class="form-control h-120 ios-popup-text input-emoji mb-2" required="" autocomplete="off" name="ios_popup_text"><?php _ec( get_data($result, "ios_popup_text") )?></textarea>

                                        <p>
                                            <span class="d-block">{app_name}: <?php _e("Name mentioned in 'Manifest Settings'")?></span>
                                            <span class="d-block">{share_icon}: <img src="<?php _ec( get_module_path( __DIR__, "../Push/Assets/img/share_icon.png" ) )?>" width="16" height="16"></span>
                                            <span class="d-block">{add_icon}: <img src="<?php _ec( get_module_path( __DIR__, "../Push/Assets/img/add_icon.png" ) )?>" width="16" height="16"></span>
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="text-center form-label w-100"><?php _e("Preview")?></label>
                                    <div class="ios-preview mb-4">
                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary w-120"><?php _e("Save")?></button>
                </div>
            </div>
        </form>

        <form class="flex-grow-1 n-scroll actionForm" action="<?php _e( get_module_url("save_macos") )?>" method="POST">
            <div class="card b-r-10 border mb-4">
                <div class="card-header">
                    <div class="card-title">
                        <?php _e("Safari (macOS) Configuration")?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label for="safari_website_push_id" class="form-label"><?php _e('Website Push ID:')?></label>
                        <input type="text" class="form-control" id="safari_website_push_id" name="safari_website_push_id" value="<?php _ec( get_data($result, "safari_website_push_id") )?>">
                    </div>
                    <div class="mb-4">
                        <label for="safari_p12_certificate" class="form-label"><?php _e('.p12 Certificate:')?></label>
                        <div class="input-group">
                            <input type="text" name="safari_p12_certificate" id="safari_p12_certificate" class="form-control" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_data($result, "safari_p12_certificate") )?>">
                            <button type="button" class="btn btn-primary btn-sm btnOpenFileManager" data-select-multi="0" data-type="image" data-id="safari_p12_certificate">
                                <i class="fad fa-folder-open p-r-0"></i> <?php _e( "Select" )?>
                            </button>
                        </div>
                    </div>


                    <div class="mb-4">
                        <label for="safari_website_name" class="form-label"><?php _e('Website Name:')?></label>
                        <input type="text" class="form-control" id="safari_website_name" name="safari_website_name" value="<?php _ec( get_data($result, "safari_website_name") )?>">
                    </div>
                    <div class="mb-4">
                        <label for="safari_allow_subdomains" class="form-label"><?php _e('Allowed Sub-domains:')?></label>
                        <textarea class="form-control" name="safari_allow_subdomains"><?php _ec( get_data($result, "safari_allow_subdomains") )?></textarea>
                        <span class="small"><?php _e("Comma separated and enter only sub-domain name (excluding main domain name)")?></span>
                    </div>

                    <div class="d-flex ">
                        <div class="me-4">
                            <div><label for="safari_website_icon" class="form-label"><?php _e('Icon')?></label></div>
                            <div class="border p-20 d-inline-block rounded b-r-10">
                                <img src="<?php _ec( get_file_url( get_data($result, "icon") ) )?>" class="img-thumbnail safari_website_icon mw-100 mb-3  w-150 h-150 b-r-10">
                                <input type="text" name="safari_website_icon" id="safari_website_icon" class="form-control d-none" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_file_url(get_data($result, "icon")) )?>">
                                <div class="input-group w-100 ">
                                    <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="safari_website_icon">
                                        <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</form>

<div class="ios-data d-none">
    <style type="text/css">
        .vn-sp-opt-ios{
            margin: auto;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            z-index: 999999999999999;
        }

        .vn-sp-opt-ios .vn-sp-opt-content{
            max-width: 400px;
            background: {ios_popup_bg_color};
            margin: auto;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
            padding: 20px;
            color: {ios_popup_text_color};
            position: relative;
        }

        .vn-sp-opt-ios .vn-sp-opt-content .vn-sp-opt-ios-detail{
            display: flex;
        }

        .vn-sp-opt-ios .vn-sp-opt-content .vn-sp-opt-ios-detail .vn-sp-opt-ios-info{
            flex-grow: 1 !important;
            margin-left: 10px;
            font-size: 14px;
            margin-top: -3px;
        }

        .vn-sp-opt-ios .sp-opt-content .sp-opt-ios-close{
            position: absolute;
            top: 3px;
            right: 3px;
            width: 22px;
            height: 22px;
            font-size: 16px;
            background: #fff;
            border-radius: 100px;
            border: 1px solid #eee;
            color: #000;
            text-align: center;
            padding: 0;
            text-decoration: none;
            line-height: 16px;
            font-weight: 600;
        }
    </style>

    <div class="sp-opt-ios" id="sp-opt-main">
        <div class="sp-opt-content border">
            <div class="sp-opt-ios-detail">
                <div class="sp-opt-ios-icon">
                    {ios_icon}
                </div>
                <div class="sp-opt-ios-info">{ios_popup_text}</div>
                <div class="sp-opt-ios-close">x</div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function(){
    $(document).on("change", ".ios-popup-text-color, .ios-popup-bg-color, .ios-popup-text, .ios-icon, .ios-name", function(){
        var ios_popup_text_color = $(".ios-popup-text-color").val();
        var ios_popup_bg_color = $(".ios-popup-bg-color").val();
        var ios_popup_text = $(".ios-popup-text").val();
        var ios_name = $(".ios-name").val();
        var ios_icon = $(".ios-icon").val();

        var item = $(".ios-data").html();
        var preview = $(".ios-preview");
        item = item.replaceAll("{ios_popup_bg_color}", ios_popup_bg_color);
        item = item.replaceAll("{ios_popup_text_color}", ios_popup_text_color);
        item = item.replaceAll("{ios_popup_text}", ios_popup_text);
        item = item.replaceAll("{app_name}", ios_name);

        item = item.replaceAll("{share_icon}", '<img src="<?php _ec( get_module_path( __DIR__, "../Push/Assets/img/share_icon.png" ) )?>" width="16" height="16" style="margin-top: -5px;">');
        item = item.replaceAll("{add_icon}", '<img src="<?php _ec( get_module_path( __DIR__, "../Push/Assets/img/add_icon.png" ) )?>" width="16" height="16" style="margin-top: -3px;">');
        
        if(ios_icon != ""){
            item = item.replaceAll("{ios_icon}", '<img src="'+ios_icon+'" width="45" height="45">');
        }else{
            item = item.replaceAll("{ios_icon}", '');
        }
        
        item = item.replaceAll("vn-sp-", "sp-");
        preview.html("");
        preview.html(item);
    });

    $(".ios-popup-text-color").trigger("change");
});
</script>