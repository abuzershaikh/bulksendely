<div class="card card-flush m-b-25 b-r-10 border">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bolder"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( $config['name'] )?></h3>
        </div>
    </div>
    <div class="card-body">
        <div class="menu-content pb-2 p-b-10">
                <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                    <?php _e("Theme")?>
                </span>
            </div>

            <div class="mb-3">
                <select class="form-select opt-theme" name="push_opt_theme" data-control="select2">
                    <option value="none" <?php _ec( get_option("push_opt_theme", "box")=="none"?"selected":"" )?> ><?php _e("None")?></option>
                    <option value="mini" <?php _ec( get_option("push_opt_theme", "box")=="mini"?"selected":"" )?>><?php _e("Mininal")?></option>
                    <option value="box" <?php _ec( get_option("push_opt_theme", "box")=="box"?"selected":"" )?>><?php _e("Box")?></option>
                    <option value="popup" <?php _ec( get_option("push_opt_theme", "box")=="popup"?"selected":"" )?>><?php _e("Popup")?></option>
                    <option value="bar" <?php _ec( get_option("push_opt_theme", "box")=="bar"?"selected":"" )?>><?php _e("Bar")?></option>
                    <option value="native" <?php _ec( get_option("push_opt_theme", "box")=="native"?"selected":"" )?>><?php _e("Native (One-click)")?></option>
                </select>
            </div>

            <div class="sp-otp-options">
                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Choose Trigger")?></label>
                            <select class="form-select opt-trigger" name="push_opt_trigger" data-control="select2">
                                <option value="on_landing" <?php _ec( get_option("push_opt_trigger", "on_landing")=="on_landing"?"selected":"" )?>><?php _e("On Landing")?></option>
                                <option value="on_scroll" <?php _ec( get_option("push_opt_trigger", "on_landing")=="on_scroll"?"selected":"" )?>><?php _e("On Scroll")?></option>
                                <option value="on_inactivity" <?php _ec( get_option("push_opt_trigger", "on_landing")=="on_inactivity"?"selected":"" )?>><?php _e("On Inactivity")?></option>
                                <option value="on_pageviews" <?php _ec( get_option("push_opt_trigger", "on_landing")=="on_pageviews"?"selected":"" )?>><?php _e("On Pageviews")?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <div class="card border shadow-0 b-r-10">
                                <div class="card-body">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label"><?php _e("On Scroll default")?></label>
                                            <input type="text" class="form-control opt-on-scroll" name="push_opt_on_scroll" value="<?php _ec( get_option("push_opt_on_scroll", "20") )?>">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label"><?php _e("On Inactivity default (seconds)")?></label>
                                            <input type="text" class="form-control opt-on_inactivity" name="push_opt_on_inactivity" value="<?php _ec( get_option("push_opt_on_inactivity", "7") )?>">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label"><?php _e("On Pageviews default (pages)")?></label>
                                            <input type="text" class="form-control opt-pageviews" name="push_opt_on_pageviews" value="<?php _ec( get_option("push_opt_on_pageviews", "3") )?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Overlay Opacity")?></label>
                            <input type="text" class="form-control opt-opacity" name="push_opt_opacity" value="<?php _ec( get_option("push_opt_opacity", "0") )?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Background")?></label>
                            <input type="text" class="form-control input-color opt-bg" name="push_opt_bg" value="<?php _ec( get_option("push_opt_bg", "#fff") )?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Delay (seconds)")?></label>
                            <input type="text" class="form-control opt-delay" name="push_opt_delay" value="<?php _ec( get_option("push_opt_delay", "3") )?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Position")?></label>
                            <input type="text" class="form-control opt-position" name="push_opt_position" value="<?php _ec( get_option("push_opt_position", "top") )?>">
                        </div>
                    </div>
                </div>

                <div class="menu-content px-0 p-b-10">
                    <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                        <?php _e("Allow Button")?>
                    </span>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Background color")?></label>
                            <input type="text" class="form-control input-color opt-allow-btn-bg" name="push_opt_allow_btn_bg"  value="<?php _ec( get_option("push_opt_allow_btn_bg", "#00b0ff") )?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Text color")?></label>
                            <input type="text" class="form-control input-color opt-allow-btn-text" name="push_opt_allow_btn_text" value="<?php _ec( get_option("push_opt_allow_btn_text", "#fff") )?>">
                        </div>
                    </div>
                </div>

                <div class="menu-content px-0 p-b-10">
                    <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                        <?php _e("Deny Button")?>
                    </span>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Background color")?></label>
                            <input type="text" class="form-control input-color opt-deny-btn-bg" name="push_opt_deny_btn_bg" value="<?php _ec( get_option("push_opt_deny_btn_bg", "#f3f3f3") )?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Text color")?></label>
                            <input type="text" class="form-control input-color opt-deny-btn-text" name="push_opt_deny_btn_text" value="<?php _ec( get_option("push_opt_deny_btn_text", "#717171") )?>">
                        </div>
                    </div>
                </div>

                <div class="menu-content px-0 p-b-10">
                    <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                        <?php _e("Text")?>
                    </span>
                </div>


                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Title")?></label>
                            <input type="text" class="form-control opt-title" name="push_opt_title" value="<?php _ec( get_option("push_opt_title", "Get our Latest News and Updates") )?>">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Description")?></label>
                            <input type="text" class="form-control opt-desc" name="push_opt_desc" value="<?php _ec( get_option("push_opt_desc", "Click on Allow to get notifications") )?>">
                        </div>
                    </div>
                </div>
            </div>


    </div>
</div>


<script type="text/javascript">
    
    $(function(){
        Core.select2(); Core.input_color();
    });
</script>