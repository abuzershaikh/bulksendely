<div class="sub-sidebar bg-white d-flex flex-column flex-row-auto">

    <div class="d-flex p-20">
        <h2 class="text-gray-800 fw-bold text-over"><?php _e( $title )?></h2>
        <div class="<?php _e( $desc )?>"></div>
    </div>

        <div class="sp-menu n-scroll sp-menu-two menu menu-column menu-fit menu-rounded menu-title-gray-600 menu-icon-gray-400 menu-state-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500 p-l-20 p-r-20 m-b-12 fw-5 h-100">
            <div class="menu-item">
                <form class="actionForm" action="<?php _ec( get_module_url("save") )?>" method="POST">
                    <div class="mb-3">
                        <select class="form-select widget-status" name="widget_status" data-control="select2">
                            <option value="1" <?php _ec($widget_options['widget_status'] == "1"?"selected":"")?>><?php _e("Enable")?></option>
                            <option value="0" <?php _ec($widget_options['widget_status'] == "0"?"selected":"")?>><?php _e("Disable")?></option>
                        </select>
                    </div>

                    <div class="sp-widget-options d-none">
                        <div class="row">

                            <div class="col-12">
                                <div class="w-100">
                                    <div><label for="widget_icon" class="form-label"><?php _e('Bell Icon')?></label></div>
                                    <div class="mb-4 border p-20 d-inline-block rounded b-r-10 w-100">
                                        <img src="<?php _ec( $widget_options['widget_icon'] )?>" class="img-thumbnail widget_icon mw-100 mb-3  w-100 h-100 b-r-10 bg-gray-400">
                                        <input type="text" name="widget_icon" id="widget_icon" class="form-control form-control-solid d-none widget-icon" placeholder="<?php _e("Select file")?>" value="<?php _ec( $widget_options['widget_icon'] )?>">
                                        <div class="input-group w-100 ">
                                            <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="widget_icon">
                                                <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Background color")?></label>
                                    <input type="text" class="form-control input-color widget-bg" name="widget_bg" value="<?php _ec($widget_options['widget_bg'])?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <label><?php _e("Position")?></label>
                                    <select class="form-select widget-position w-100" name="widget_position" data-control="select2">
                                        <option value="left" <?php _ec($widget_options['widget_position'] == "left"?"selected":"")?>><?php _e("Left")?></option>
                                        <option value="right" <?php _ec($widget_options['widget_position'] == "right"?"selected":"")?>><?php _e("Right")?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 widget-position-option d-none" data-type="left">
                                <div class="mb-3">
                                    <label><?php _e("Left (px)")?></label>
                                    <input type="number" class="form-control widget-left" name="widget_left" value="<?php _ec($widget_options['widget_left'])?>">
                                </div>
                            </div>

                            <div class="col-12 widget-position-option d-none" data-type="right">
                                <div class="mb-3">
                                    <label><?php _e("Right (px)")?></label>
                                    <input type="number" class="form-control widget-right" name="widget_right" value="<?php _ec($widget_options['widget_right'])?>">
                                </div>
                            </div>

                            <div class="col-12" data-type="bottom">
                                <div class="mb-3">
                                    <label><?php _e("Bottom (px)")?></label>
                                    <input type="number" class="form-control widget-bottom" name="widget_bottom" value="<?php _ec($widget_options['widget_bottom'])?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sp-widget-disable d-none">
                        <div class="col-12">
                            <div class="fs-14 fw-4">
                                <?php _e("When you select the Disable option, the widget icon image will be hidden on your website, making it invisible to your visitors. If you want to display the widget icon again, you just need to activate it again.")?>
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