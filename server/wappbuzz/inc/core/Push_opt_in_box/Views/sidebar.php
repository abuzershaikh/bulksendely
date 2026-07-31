<div class="sub-sidebar bg-white d-flex flex-column flex-row-auto">

    <div class="d-flex p-20">
        <h2 class="text-gray-800 fw-bold text-over"><?php _e( $title )?></h2>
        <div class="<?php _e( $desc )?>"></div>
    </div>

        <div class="sp-menu n-scroll sp-menu-two menu menu-column menu-fit menu-rounded menu-title-gray-600 menu-icon-gray-400 menu-state-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500 p-l-20 p-r-20 m-b-12 fw-5 h-100">
            <div class="menu-item">
                <div class="opt-mode d-flex m-auto">
                    <ul class="nav nav-pills mb-3 bg-white fs-14 nx-scroll overflow-x-auto text-over b-r-6 border r-0 t-0 m-auto" id="pills-tab">
                        <li class="nav-item me-0">
                             <label for="opt_mode_1" class="nav-link bg-active-primary text-gray-700 px-3 py-2 btl-r-6 bbl-r-6 btr-r-0 bbr-r-0 text-active-white active w-45 text-center float-start" data-bs-toggle="pill" data-bs-target="#tab_mode_1" type="button" role="tab" aria-selected="true"><i class="fal fa-desktop"></i></label>
                             <input class="d-none" type="radio" name="opt_mode" id="opt_mode_1" checked="" value="0">
                        </li>
                        <li class="nav-item me-0">
                             <label for="opt_mode_2" class="nav-link bg-active-primary text-gray-700 px-3 py-2 btl-r-0 bbl-r-0 btr-r-6 bbr-r-6 text-active-white w-45 text-center float-start" data-bs-toggle="pill" data-bs-target="#tab_mode_2" type="button" role="tab" aria-selected="false"><i class="fad fa-mobile"></i></label>
                             <input class="d-none" type="radio" name="opt_mode" id="opt_mode_2" value="1">
                        </li>
                    </ul>
                </div>

                <div class="clearfix"></div>

                <form class="actionForm clearfix" action="<?php _ec( get_module_url("save") )?>" method="POST">
                    <div class="menu-content pb-2 ps-0 p-b-10">
                        <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                            <?php _e("Theme")?>
                        </span>
                    </div>

                    <div class="mb-3">
                        <select class="form-select opt-theme" name="opt_theme" data-control="select2">
                            <option value="none" <?php _ec($opt_options['opt_theme'] == "none"?"selected":"")?>><?php _e("None")?></option>
                            <option value="mini" <?php _ec($opt_options['opt_theme'] == "mini"?"selected":"")?>><?php _e("Mininal")?></option>
                            <option value="box" <?php _ec($opt_options['opt_theme'] == "box"?"selected":"")?>><?php _e("Box")?></option>
                            <option value="popup" <?php _ec($opt_options['opt_theme'] == "popup"?"selected":"")?>><?php _e("Popup")?></option>
                            <option value="bar" <?php _ec($opt_options['opt_theme'] == "bar"?"selected":"")?>><?php _e("Bar")?></option>
                            <option value="native" <?php _ec($opt_options['opt_theme'] == "native"?"selected":"")?>><?php _e("Native (One-click)")?></option>
                        </select>
                    </div>

                    <div class="mb-3 sp-opt-none bg-light-primary b-r-10 p-20 fs-14 fw-4 <?php _ec( ($opt_options['opt_theme'] == "none")?"":"d-none" )?>">
                        <?php _e('When "None" is selected, a Widget icon will appear in the bottom right corner of the website. Click here to configure your settings.')?>
                    </div>

                    <div class="sp-otp-options <?php _ec( ($opt_options['opt_theme'] == "none" || $opt_options['opt_theme'] == "native")?"d-none":"" )?>">
                        <div class="mb-3 opt-banner d-none">
                            <label class="form-label"><?php _e("Banner")?></label>
                            <?php echo view_cell('\Core\File_manager\Controllers\File_manager::mini', ["type" => "image", "select_multi" => 0, "name" => "opt_banner", "id" => ""]) ?>

                            <script type="text/javascript">
                                $(function(){
                                    File_manager.loadSelectedFiles(["<?php _ec( remove_file_path( get_file_url( $opt_options['opt_banner'] ) ) )?>"], "opt_banner");
                                });
                            </script>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Choose Trigger")?></label>
                                    <select class="form-select opt-trigger w-100" name="opt_trigger" data-control="select2">
                                        <option value="on_landing" <?php _ec($opt_options['opt_trigger'] == "on_landing"?"selected":"")?>><?php _e("On Landing")?></option>
                                        <option value="on_scroll" <?php _ec($opt_options['opt_trigger'] == "on_scroll"?"selected":"")?>><?php _e("On Scroll")?></option>
                                        <option value="on_inactivity" <?php _ec($opt_options['opt_trigger'] == "on_inactivity"?"selected":"")?>><?php _e("On Inactivity")?></option>
                                        <option value="on_pageviews" <?php _ec($opt_options['opt_trigger'] == "none"?"on_pageviews":"")?>><?php _e("On Pageviews")?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 opt-trigger-option d-none" data-type="on_scroll">
                                <div class="mb-3">
                                    <label><?php _e("When user scrolls (percent)")?></label>
                                    <input type="text" class="form-control opt-on-scroll" name="opt_on_scroll" value="<?php _ec($opt_options['opt_on_scroll'])?>">
                                </div>
                            </div>
                            <div class="col-12 opt-trigger-option d-none" data-type="on_inactivity">
                                <div class="mb-3">
                                    <label><?php _e("Display after (seconds)")?></label>
                                    <input type="text" class="form-control opt-on-inactivity" name="opt_on_inactivity" value="<?php _ec($opt_options['opt_on_inactivity'])?>">
                                </div>
                            </div>
                            <div class="col-12 opt-trigger-option d-none" data-type="on_pageviews">
                                <div class="mb-3">
                                    <label><?php _e("When user visits (pages)")?></label>
                                    <input type="text" class="form-control opt-on-pageviews" name="opt_on_pageviews" value="<?php _ec($opt_options['opt_on_pageviews'])?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Overlay Opacity")?></label>
                                    <input type="text" class="form-control opt-opacity" name="opt_opacity" value="<?php _ec($opt_options['opt_opacity'])?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Background")?></label>
                                    <input type="text" class="form-control input-color opt-bg" name="opt_bg" value="<?php _ec($opt_options['opt_bg'])?>">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Delay (seconds)")?></label>
                                    <input type="text" class="form-control opt-delay" name="opt_delay" value="<?php _ec($opt_options['opt_delay'])?>">
                                </div>
                            </div>
                            <div class="col-12 opt-position-wrap d-none">
                                <div class="mb-3">
                                    <label><?php _e("Position")?></label>
                                    <select class="form-select opt-position w-100" name="opt_position" data-control="select2">
                                        <option value="top" <?php _ec($opt_options['opt_position'] == "top"?"selected":"")?>><?php _e("Top")?></option>
                                        <option value="bottom" <?php _ec($opt_options['opt_position'] == "bottom"?"selected":"")?>><?php _e("Bottom")?></option>
                                    </select>
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
                                    <label><?php _e("Background color")?></label>
                                    <input type="text" class="form-control input-color opt-allow-btn-bg" name="opt_allow_btn_bg" value="<?php _ec($opt_options['opt_allow_btn_bg'])?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Text color")?></label>
                                    <input type="text" class="form-control input-color opt-allow-btn-text" name="opt_allow_btn_text" value="<?php _ec($opt_options['opt_allow_btn_text'])?>">
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
                                    <label><?php _e("Background color")?></label>
                                    <input type="text" class="form-control input-color opt-deny-btn-bg" name="opt_deny_btn_bg" value="<?php _ec($opt_options['opt_deny_btn_bg'])?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Text color")?></label>
                                    <input type="text" class="form-control input-color opt-deny-btn-text" name="opt_deny_btn_text" value="<?php _ec($opt_options['opt_deny_btn_text'])?>">
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
                                    <label><?php _e("Title")?></label>
                                    <input type="text" class="form-control opt-title" name="opt_title" value="<?php _ec($opt_options['opt_title'])?>">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label><?php _e("Description")?></label>
                                    <input type="text" class="form-control opt-desc" name="opt_desc" value="<?php _ec($opt_options['opt_desc'])?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 d-flex justify-content-between">
                        <a class="btn btn-secondary b-r-80 mt-4 actionItem me-3" href="<?php _ec( get_module_url("reset") )?>" data-confirm="<?php _e("Are you sure you want to reset?")?>" data-redirect=""><?php _e("Reset")?></a>
                        <button class="btn btn-dark b-r-80 mt-4"><?php _e("Save")?></button>
                    </div>

                </form>
            </div>
        </div>
</div>