<div class="card card-flush m-b-25 b-r-10 border">
    <div class="card-header">
        <div class="card-title flex-column">
            <h3 class="fw-bolder"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( $config['name'] )?></h3>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label"><?php _e("Status")?></label>
            <select class="form-select widget-status" name="push_widget_status" data-control="select2">
                <option value="1" <?php _ec( get_option("push_widget_status", 1) == "1"?"selected":"")?>><?php _e("Enable")?></option>
                <option value="0" <?php _ec( get_option("push_widget_status", 1) == "0"?"selected":"")?>><?php _e("Disable")?></option>
            </select>
        </div>

        <div class="sp-otp-options">
            <div class="row">
                <div class="col-12">
                    <div class="w-100">
                        <div><label for="widget_icon" class="form-label"><?php _e('Bell Icon')?></label></div>
                        <div class="mb-4 border p-20 d-inline-block rounded b-r-10 w-150">
                            <img src="<?php _ec( get_option("push_widget_icon", base_url("inc/core/Push_widget/Assets/img/bell_icon.png") ) )?>" class="img-thumbnail widget_icon mw-100 mb-3  w-100 h-100 b-r-10 bg-gray-400">
                            <input type="text" name="widget_icon" id="widget_icon" class="form-control form-control-solid d-none widget-icon" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_option("push_widget_icon", base_url("inc/core/Push_widget/Assets/img/bell_icon.png") ) )?>">
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
                        <label class="form-label"><?php _e("Background color")?></label>
                        <input type="text" class="form-control input-color widget-bg" name="push_widget_bg" value="<?php _ec( get_option("push_widget_bg", "#0055ff") )?>">
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Position")?></label>
                        <select class="form-select widget-position w-100" name="push_widget_position" data-control="select2">
                            <option value="left" <?php _ec( get_option("push_widget_position", "right") == "left"?"selected":"")?>><?php _e("Left")?></option>
                            <option value="right" <?php _ec( get_option("push_widget_position", "right") == "right"?"selected":"")?>><?php _e("Right")?></option>
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Left (px)")?></label>
                        <input type="number" class="form-control widget-left" name="push_widget_left" value="<?php _ec( get_option("push_widget_left", 15) )?>">
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Right (px)")?></label>
                        <input type="number" class="form-control widget-right" name="push_widget_right" value="<?php _ec( get_option("push_widget_right", 15) )?>">
                    </div>
                </div>

                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label"><?php _e("Bottom (px)")?></label>
                        <input type="number" class="form-control widget-bottom" name="push_widget_bottom" value="<?php _ec( get_option("push_widget_bottom", 15) )?>">
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