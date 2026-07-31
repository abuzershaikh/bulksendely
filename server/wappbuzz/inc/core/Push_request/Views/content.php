<form class="actionForm" action="<?php _e( get_module_url("save") )?>" method="POST" data-result="html" data-content="ajax-result" date-redirect="false" data-loading="true">
    
    <div class="container my-5">
        <div class="mb-5">
            <h2><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Send Notification")?></h2>
            <p><?php _e( $config['desc'] )?></p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-header">
                        <div class="card-title">
                            <?php _e("New notification")?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Title")?></label>
                            <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Title")?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Message")?></label>
                            <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Your message here...")?>">
                        </div>

                        <div class="accordion" id="accordion_utm_status">
                            <div class="accordion-header d-flex mb-3" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="type" id="utm_status" value="1" data-bs-toggle="collapse" data-bs-target="#tab_utm_status" aria-expanded="true" aria-controls="tab_utm_status">
                                </div>
                                <label for="utm_status" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_utm_status" aria-expanded="true" aria-controls="tab_utm_status"><?php _e("Include UTM Params")?></label>
                            </div>
                            <div id="tab_utm_status" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_utm_status">
                                <div class="border b-r-6 p-20">
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Source")?></label>
                                        <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Medium")?></label>
                                        <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Name")?></label>
                                        <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e("Campaign")?></label>
                            <div class="input-group mb-3">
                                <select class="form-select form-select-solid">
                                    <option value="0">None</option>
                                </select>
                                <button class="btn btn-dark" type="button" id="button-addon2"><?php _e("Create campaign")?></button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e("Custom Icon")?></label>
                            <?php echo view_cell('\Core\File_manager\Controllers\File_manager::mini', ["type" => "image,video,doc,pdf,audio,other", "select_multi" => 0]) ?>

                            <script type="text/javascript">
                                $(function(){
                                    File_manager.loadSelectedFiles(["<?php _ec( remove_file_path(  get_data($result, "media") ) )?>"]);
                                });
                            </script>
                        </div>

                        <div class="card border b-r-6 mb-3">
                            <div class="card-header mih-40 mh-40">
                                <div class="card-title fs-14">
                                    <?php _e("Additional Customization")?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="accordion_large_image">
                                    <div class="accordion-header d-flex mb-1" id="headingOne">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="type" id="large_image" value="1" data-bs-toggle="collapse" data-bs-target="#tab_large_image" aria-expanded="true" aria-controls="tab_large_image">
                                        </div>
                                        <label for="large_image" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_large_image" aria-expanded="true" aria-controls="tab_large_image"><?php _e("Add Large Image (Supported on Chrome 56+)")?></label>
                                    </div>
                                    <div id="tab_large_image" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_large_image">
                                        <div class="border b-r-6 p-20">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Source")?></label>
                                                <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Medium")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion" id="accordion_large_image">
                                    <div class="accordion-header d-flex mb-1" id="headingOne">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="type" id="action_button" value="1" data-bs-toggle="collapse" data-bs-target="#tab_action_button" aria-expanded="true" aria-controls="tab_action_button">
                                        </div>
                                        <label for="action_button" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_action_button" aria-expanded="true" aria-controls="tab_action_button"><?php _e("Action Buttons")?></label>
                                    </div>
                                    <div id="tab_action_button" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_action_button">
                                        <div class="border b-r-6 p-20">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Source")?></label>
                                                <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Medium")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion" id="accordion_large_image">
                                    <div class="accordion-header d-flex mb-1" id="headingOne">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="type" id="set_notification_expiry" value="1" data-bs-toggle="collapse" data-bs-target="#tab_set_notification_expiry" aria-expanded="true" aria-controls="tab_set_notification_expiry">
                                        </div>
                                        <label for="set_notification_expiry" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_set_notification_expiry" aria-expanded="true" aria-controls="tab_set_notification_expiry"><?php _e("Set Notification Expiry")?></label>
                                    </div>
                                    <div id="tab_set_notification_expiry" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_set_notification_expiry">
                                        <div class="border b-r-6 p-20">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Source")?></label>
                                                <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Medium")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion" id="accordion_large_image">
                                    <div class="accordion-header d-flex mb-1" id="headingOne">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="type" id="auto_hide" value="1" data-bs-toggle="collapse" data-bs-target="#tab_auto_hide" aria-expanded="true" aria-controls="tab_auto_hide">
                                        </div>
                                        <label for="auto_hide" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_auto_hide" aria-expanded="true" aria-controls="tab_auto_hide"><?php _e("Auto-hide Notification (Chrome)")?></label>
                                    </div>
                                    <div id="tab_auto_hide" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_auto_hide">
                                        <div class="border b-r-6 p-20">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Source")?></label>
                                                <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Medium")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border b-r-6 mb-3">
                            <div class="card-header mih-40 mh-40">
                                <div class="card-title fs-14">
                                    <?php _e("Advanced Targeting")?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="accordion_audience">
                                    <div class="accordion-header d-flex mb-1" id="headingOne">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="type" id="audience" value="1" data-bs-toggle="collapse" data-bs-target="#tab_audience" aria-expanded="true" aria-controls="tab_audience">
                                        </div>
                                        <label for="audience" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_audience" aria-expanded="true" aria-controls="tab_audience"><?php _e("Select Audience")?></label>
                                    </div>
                                    <div id="tab_audience" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_audience">
                                        <div class="border b-r-6 p-20">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Source")?></label>
                                                <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Medium")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion" id="accordion_large_image">
                                    <div class="accordion-header d-flex mb-1" id="headingOne">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" name="type" id="action_button" value="1" data-bs-toggle="collapse" data-bs-target="#tab_action_button" aria-expanded="true" aria-controls="tab_action_button">
                                        </div>
                                        <label for="action_button" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_action_button" aria-expanded="true" aria-controls="tab_action_button"><?php _e("Advanced Options")?></label>
                                    </div>
                                    <div id="tab_action_button" class="accordion-collapse collapse bg-light-primary" aria-labelledby="headingOne" data-bs-parent="#accordion_action_button">
                                        <div class="border b-r-6 p-20">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Source")?></label>
                                                <input type="text" name="source" class="form-control" value="" placeholder="<?php _e("Campaign Source")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Medium")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Medium")?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="title" class="form-control" value="" placeholder="<?php _e("Campaign Name")?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        
                        <button class="btn btn-primary"><i class="fad fa-paper-plane"></i> <?php _e("Send")?></button>
                        <button class="btn btn-dark"><i class="fad fa-eye"></i> <?php _e("Preview")?></button>

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-header">
                        <div class="card-title">
                            <?php _e("Preview")?>
                        </div>
                    </div>
                    <div class="card-body">
                        sss
                    </div>
                </div>
            </div>
        </div>

    </div>

</form>
