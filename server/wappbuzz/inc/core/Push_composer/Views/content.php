<?php
$title = "";
$message = "";
$url = "https://".$website->domain."/";
$icon = get_file_url($website->icon);
$campaign_id = 0;
$utm_status = 0;
$time_post = "";
$utm_source = $website->utm_source;
$utm_medium = $website->utm_medium;
$utm_name = $website->utm_name;
$auto_hide = 0;
$large_image_status = 0;
$large_image = "";
$expiry_status = 0;
$expiry = 1;
$expiry_by = "hours";
$action_button = 0;
$action_button_count = 1;
$action_button1_name = "";
$action_button1_url = "";
$action_button2_name = "";
$action_button2_url = "";

$country = "all";
$browser = "all";
$os = "all";
$device = "all";
$audience_id = 0;
$segment_id = 0;

$audience_status = 0;
$advance_option_status = 0;

if( $post ){

    $title = $post->title;
    $message = $post->message;
    $url = $post->url;
    $icon = get_file_url($post->icon);
    $campaign_id = $post->campaign_id;
    $time_post = $post->time_post;
    $auto_hide = $post->auto_hide;
    $country = $post->country;
    $browser = $post->browser;
    $os = $post->os;
    $device = $post->device;
    $segment_id = (int)$post->segment_id;
    $audience_id = (int)$post->audience_id;
    $audience_status = (int)$post->audience_status;

    $expiry = (int)$post->expiry;
    $expiry_by = $post->expiry_by;
    $expiry_status = (int)$post->expiry_status;
    
    $large_image_status = $post->large_image_status;
    $large_image = $post->large_image;
    $actions = json_decode($post->actions);

    if(!empty($actions)){
        $action_button = 1;
        if ( count($actions) == 1 ) {
            $action_button1_name = $actions[0]->title;
            $action_button1_url = $actions[0]->url;

            $action_button2_name = "";
            $action_button2_url = "";
        } else if( count($actions) == 2 ){
            $action_button1_name = $actions[0]->title;
            $action_button1_url = $actions[0]->url;

            $action_button2_name = $actions[1]->title;
            $action_button2_url = $actions[1]->url;
            $action_button_count = 2;
        }
    }

    //UTM
    $utm_params = [];

    if($post->utm != ""){
        $utm_params = json_decode( $post->utm );
    }

    $utm_status = $post->utm_status;
    if(!empty($utm_params) && $utm_params->source != "" && $utm_params->medium != "" && $utm_params->name != ""){
        $utm_source = $utm_params->source;
        $utm_medium = $utm_params->medium;
        $utm_name = $utm_params->name;
    }else{
        $utm_source = "";
        $utm_medium = "";
        $utm_name = "";
    }
    //END UTM
}
?>


<form class="actionForm" action="<?php _e( get_module_url("save") )?>" method="POST" data-result="html" data-content="ajax-result" data-redirect="" data-loading="true">
    
    <div class="container my-5">
        <div class="mb-5">
            <h2><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Send Notification")?></h2>
            <p><?php _e( $config['desc'] )?></p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card border mb-4 b-r-10">
                    <div class="card-header">
                        <div class="card-title">
                            <?php _e("New notification")?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Title")?></label>
                            <input type="text" name="title" class="form-control" value="<?php _ec( $title )?>" data-default-text="<?php _e("Title")?>" placeholder="<?php _e("Title")?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php _e("Message")?></label>
                            <?php echo view_cell('\Core\Caption\Controllers\Caption::block', ['name' => 'message', 'value' => $message, 'data-default-text' => __("Your message here...") ]) ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e("URL")?></label>
                            <input type="text" name="url" class="form-control" value="<?php _ec( $url )?>" placeholder="<?php _e("URL")?>">
                        </div>

                        <div class="mb-3 push-icon" data-default-icon="<?php _ec( $icon )?>">
                            <label class="form-label"><?php _e("Custom Icon")?></label>
                            <?php echo view_cell('\Core\File_manager\Controllers\File_manager::mini', ["type" => "image", "select_multi" => 0]) ?>

                            <script type="text/javascript">
                                $(function(){
                                    File_manager.loadSelectedFiles(["<?php _ec( remove_file_path(  $icon ) )?>"]);
                                });
                            </script>
                        </div>

                        <div class="accordion mb-3" id="accordion_utm_status">
                            <div class="accordion-header d-flex mb-3" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="utm_status" id="utm_status" value="1" data-bs-toggle="collapse" data-bs-target="#tab_utm_status" aria-expanded="true" aria-controls="tab_utm_status" <?php _e( $utm_status?"checked":"" )?>>
                                </div>
                                <label for="utm_status" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_utm_status" aria-expanded="true" aria-controls="tab_utm_status"><?php _e("Include UTM Params")?></label>
                            </div>
                            <div id="tab_utm_status" class="accordion-collapse collapse <?php _e( $utm_status?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_utm_status">
                                <div class="border b-r-6 p-20">
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Source")?></label>
                                        <input type="text" name="utm_source" class="form-control" value="<?php _ec( $utm_source )?>" placeholder="<?php _e("Campaign Source")?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Medium")?></label>
                                        <input type="text" name="utm_medium" class="form-control" value="<?php _ec( $utm_medium )?>" placeholder="<?php _e("Campaign Medium")?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Name")?></label>
                                        <input type="text" name="utm_name" class="form-control" value="<?php _ec( $utm_name )?>" placeholder="<?php _e("Campaign Name")?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php _e("Campaign")?></label>
                            <div class="input-group input-group-sm mb-3">
                                <select class="form-select form-select-solid" name="campaign">
                                    <option value="0"><?php _e("None")?></option>
                                    <?php if (!empty($campaigns)): ?>
                                        <?php foreach ($campaigns as $key => $value): ?>
                                            <option value="<?php _ec( $value->id )?>" <?php _e( $campaign_id==$value->id?"selected":"" )?> ><?php _ec( $value->name )?></option>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </select>
                                <a class="btn btn-dark" href="<?php _ec( base_url("push_campaign") )?>"><?php _e("Create campaign")?></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border b-r-10 mb-4">
                    <div class="card-header mih-40 mh-40 px-3">
                        <div class="card-title fs-14">
                            <?php _e("Additional Customization")?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordion_large_image">
                            <div class="accordion-header d-flex mb-1" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="large_image_status" id="large_image_status" value="1" data-bs-toggle="collapse" data-bs-target="#tab_large_image" aria-expanded="true" aria-controls="tab_large_image" <?php _e( $large_image_status?"checked":"" )?>>
                                </div>
                                <label for="large_image" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_large_image" aria-expanded="true" aria-controls="tab_large_image"><?php _e("Add Large Image (Supported on Chrome 56+)")?></label>
                            </div>
                            <div id="tab_large_image" class="accordion-collapse collapse <?php _e( $large_image_status?"show":"" )?> mb-3" aria-labelledby="headingOne" data-bs-parent="#accordion_large_image">
                                <div class="input-group">
                                    <input type="text" name="large_image" id="large_image" class="form-control form-control-solid" placeholder="<?php _e("Select file")?>" value="<?php _ec( $large_image )?>" data-default-image="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/large-image.svg" ) )?>">
                                    <button type="button" class="btn btn-primary btn-sm btnOpenFileManager" data-select-multi="0" data-type="image" data-id="large_image">
                                        <i class="fad fa-folder-open p-r-0"></i> <?php _e( "Select" )?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="accordion position-relative" id="accordion_action_button">
                            <div class="accordion-header d-flex mb-1" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="action_button" id="action_button" value="1" data-bs-toggle="collapse" data-bs-target="#tab_action_button" aria-expanded="true" aria-controls="tab_action_button" <?php _ec( $action_button?"checked":"" )?>>
                                </div>
                                <label for="action_button" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_action_button" aria-expanded="true" aria-controls="tab_action_button"><?php _e("Action Buttons")?></label>
                            </div>
                            <div id="tab_action_button" class="accordion-collapse collapse <?php _ec( $action_button?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_action_button">
                                <ul class="nav nav-pills mb-3 bg-white rounded fs-14 nx-scroll overflow-x-auto d-flex text-over b-r-6 border position-absolute r-0 t-0" id="pills-tab">
                                    <li class="nav-item me-0">
                                         <label for="action_button_count_1" class="nav-link bg-active-primary text-gray-700 px-3 py-2 b-r-6 text-active-white <?php _e( $action_button_count==1?"active":"" )?>" data-bs-toggle="pill" data-bs-target="#tab_action_button_count_1" type="button" role="tab"><?php _e("1 Button")?></label>
                                         <input class="d-none" type="radio" name="action_button_count" id="action_button_count_1" <?php _e( $action_button_count==1?"checked":"" )?> value="0">
                                    </li>
                                    <li class="nav-item me-0">
                                         <label for="action_button_count_2" class="nav-link bg-active-primary text-gray-700 px-3 py-2 b-r-6 text-active-white <?php _e( $action_button_count==2?"active":"" )?>" data-bs-toggle="pill" data-bs-target="#tab_action_button_count_2" type="button" role="tab"><?php _e("2 Button")?></label>
                                         <input class="d-none" type="radio" name="action_button_count" id="action_button_count_2" <?php _e( $action_button_count==2?"checked":"" )?> value="1">
                                    </li>
                                </ul>

                                <label class="form-label"><?php _e("Button 1")?></label>
                                <div class="bg-light-primary border b-r-10 p-20 mb-4">
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("Name")?></label>
                                        <input type="text" name="action_button1_name" class="form-control" value="<?php _ec( $action_button1_name )?>" data-default-text="Button 1">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label"><?php _e("URL")?></label>
                                        <input type="text" name="action_button1_url" class="form-control" value="<?php _ec( $action_button1_url )?>">
                                    </div>
                                </div>
                                
                                <div class="tab-content" id="pills-tabContent">
                                    <div class="tab-pane fade <?php _e( $action_button_count==1?"show active":"" )?>" id="tab_action_button_count_1"></div>
                                    <div class="tab-pane fade <?php _e( $action_button_count==2?"show active":"" )?>" id="tab_action_button_count_2">
                                        <label class="form-label"><?php _e("Button 2")?></label>
                                        <div class="bg-light-primary border b-r-10 p-20 mb-4">
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("Name")?></label>
                                                <input type="text" name="action_button2_name" class="form-control" value="<?php _ec( $action_button2_name )?>" data-default-text="Button 2">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php _e("URL")?></label>
                                                <input type="text" name="action_button2_url" class="form-control" value="<?php _ec( $action_button2_url )?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <div class="accordion" id="accordion_expiry_status">
                            <div class="accordion-header d-flex mb-1" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="expiry_status" id="expiry_status" value="1" data-bs-toggle="collapse" data-bs-target="#tab_expiry_status" aria-expanded="true" aria-controls="tab_expiry_status" <?php _ec( $expiry_status?"checked":"" )?>>
                                </div>
                                <label for="expiry_status" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_expiry_status" aria-expanded="true" aria-controls="tab_expiry_status"><?php _e("Set Notification Expiry")?></label>
                            </div>
                            <div id="tab_expiry_status" class="accordion-collapse collapse <?php _ec( $expiry_status?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_expiry_status">
                                <div class="bg-light-primary border b-r-10 p-20 mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <input type="text" name="expiry" class="form-control" value="<?php _ec( $expiry )?>">
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select bg-white border" name="expiry_by">
                                                <option value="minutes" <?php _ec( $expiry_by=="minutes"?"selected":"" )?>><?php _e("minutes")?></option>
                                                <option value="hours" <?php _ec( $expiry_by=="hours"?"selected":"" )?>><?php _e("hours")?></option>
                                                <option value="days" <?php _ec( $expiry_by=="days"?"selected":"" )?>><?php _e("days")?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="accordion" id="accordion_auto_hide">
                            <div class="accordion-header d-flex mb-1" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="auto_hide" id="auto_hide" value="1" data-bs-toggle="collapse" data-bs-target="#tab_auto_hide" aria-expanded="true" aria-controls="tab_auto_hide" <?php _ec( $auto_hide?"checked":"" )?>>
                                </div>
                                <label for="auto_hide" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_auto_hide" aria-expanded="true" aria-controls="tab_auto_hide"><?php _e("Auto-hide Notification (Chrome)")?></label>
                            </div>
                            <div id="tab_auto_hide" class="accordion-collapse collapse <?php _ec( $expiry_status?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_auto_hide">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border b-r-10 mb-4">
                    <div class="card-header mih-40 mh-40 px-3">
                        <div class="card-title fs-14">
                            <?php _e("Advanced Targeting")?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="accordion mb-1" id="accordion_audience">
                            <div class="accordion-header d-flex mb-1" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="audience_status" id="audience" value="1" data-bs-toggle="collapse" data-bs-target="#tab_audience" aria-expanded="true" aria-controls="tab_audience" <?php _ec( $audience_status==1?"checked":"" )?>>
                                </div>
                                <label for="audience" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_audience" aria-expanded="true" aria-controls="tab_audience"><?php _e("Select Audience")?></label>
                            </div>
                            <div id="tab_audience" class="accordion-collapse collapse <?php _ec( $audience_status==1?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_audience">
                                <select class="form-select mb-3 w-100 border" name="audience_id" data-control="select2">
                                    <option value="0"><?php _e("All")?></option>
                                    <?php if (!empty($audiences)): ?>
                                        <?php foreach ($audiences as $key => $value): ?>
                                            <option value="<?php _ec( $value->id )?>"  <?php _ec( $audience_id==$value->id?"selected":"" )?>><?php _ec( $value->name )?></option>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </select>
                            </div>

                            <div class="accordion-header d-flex mb-1" id="headingOne">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="audience_status" id="advance_option_status" value="2" data-bs-toggle="collapse" data-bs-target="#tab_advance_option_status" aria-expanded="true" aria-controls="tab_advance_option_status" <?php _ec( $audience_status==2?"checked":"" )?>>
                                </div>
                                <label for="advance_option_status" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_advance_option_status" aria-expanded="true" aria-controls="tab_advance_option_status"><?php _e("Advanced Options")?></label>
                            </div>
                            <div id="tab_advance_option_status" class="accordion-collapse collapse <?php _ec( $audience_status==2?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_audience">
                                <div class="border b-r-6 p-20">
                                    <div class="mb-2">
                                        <label class="text-gray-700 px-2 b-r-6"><?php _e("Segment")?></label>
                                        <select class="form-select mb-3 w-100 border" name="segment_id" data-control="select2">
                                            <option value="0"><?php _e("All")?></option>
                                            <?php if (!empty($segments)): ?>
                                                <?php foreach ($segments as $key => $value): ?>
                                                    <option value="<?php _ec( $value->id )?>" <?php _ec( $segment_id==$value->id?"selected":"" )?> ><?php _ec( $value->name )?></option>
                                                <?php endforeach ?>
                                            <?php endif ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="text-gray-700 px-2 b-r-6"><?php _e("Country")?></label>
                                        <select class="form-select mb-3 w-100 border" name="country" data-control="select2">
                                            <option value=""><?php _e("All")?></option>
                                            <?php foreach (list_countries() as $key => $value): ?>
                                                <option value="<?php _e($key)?>" <?php _ec( $country==$key?"selected":"" )?> ><?php _e($value)?></option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="text-gray-700 px-2 b-r-6"><?php _e("Device")?></label>
                                        <select class="form-select mb-3 w-100 border" name="device" data-control="select2">
                                            <option value=""><?php _e("All")?></option>
                                            <option value="desktop" <?php _ec( $device=="desktop"?"selected":"" )?>><?php _e("Desktop")?></option>
                                            <option value="mobile" <?php _ec( $device=="mobile"?"selected":"" )?>><?php _e("Mobile")?></option>
                                            <option value="tablet" <?php _ec( $device=="tablet"?"selected":"" )?>><?php _e("Tablet")?></option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="text-gray-700 px-2 b-r-6"><?php _e("OS")?></label>
                                        <select class="form-select mb-3 w-100 border" name="os" data-control="select2">
                                            <option value=""><?php _e("All")?></option>
                                            <option value="windows" <?php _ec( $os=="windows"?"selected":"" )?>><?php _e("Windows")?></option>
                                            <option value="mac" <?php _ec( $os=="mac"?"selected":"" )?>><?php _e("Mac")?></option>
                                            <option value="android" <?php _ec( $os=="android"?"selected":"" )?>><?php _e("Android")?></option>
                                            <option value="linux" <?php _ec( $os=="linux"?"selected":"" )?>><?php _e("Linux")?></option>
                                        </select>
                                    </div>
                                    <div class="mb-0">
                                        <label class="text-gray-700 px-2 b-r-6"><?php _e("Browser")?></label>
                                        <select class="form-select mb-3 w-100 border" name="browser" data-control="select2">
                                            <option value=""><?php _e("All")?></option>
                                            <option value="chrome" <?php _ec( $browser=="chrome"?"selected":"" )?>><?php _e("Chrome")?></option>
                                            <option value="safari" <?php _ec( $browser=="safari"?"selected":"" )?>><?php _e("Safari")?></option>
                                            <option value="firefox" <?php _ec( $browser=="firefox"?"selected":"" )?>><?php _e("Firefox")?></option>
                                            <option value="opera" <?php _ec( $browser=="opera"?"selected":"" )?>><?php _e("Opera")?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="card border b-r-10">
                        <?php if( empty($post) ){?>
                        <div class="card-header p-r-20 p-l-20 py-2 border-bottom-0">
                            <h3 class="card-title fs-14"><?php _e("When to post")?></h3>
                            <div class="card-toolbar">
                                <select class="form-select mw-150 fs-12" name="post_by">
                                    <option value="1"><?php _e("Schedule")?></option>
                                    <option value="2"><?php _e("Specific Days & Times")?></option>
                                    <option value="3"><?php _e("Draft")?></option>
                                </select>
                            </div>
                        </div>
                        <?php }else{?>

                            <?php if ($post->status==0): ?>
                                <div class="card-header p-r-20 p-l-20 py-2">
                                    <h3 class="card-title fs-14"><?php _e("When to post")?></h3>
                                    <div class="card-toolbar">
                                        <select class="form-select mw-150 fs-12" name="post_by">
                                            <option value="1"><?php _ec("Schedule & Repost")?></option>
                                            <option value="2"><?php _ec("Specific Days & Times")?></option>
                                            <option value="3" <?php _ec($post->status==0?"selected":"")?> ><?php _e("Draft")?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-none">
                                    <input type="text" name="id" value="<?php _ec( $post->id )?>">
                                    <input type="text" name="draft" value="1">
                                </div>
                            <?php else: ?>
                                <div class="d-none">
                                    <input type="text" name="post_by" value="1">
                                    <input type="text" name="id" value="<?php _ec( $post->id )?>">
                                </div>
                            <?php endif ?>
                            
                        <?php }?>
                        <div class="post-by" data-by="1">
                            <div class="card-body <?php _ec( empty($post)?"border-top":"" )?> p-20">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="fs-14"><?php _e("Time post")?></label>
                                        <input type="text" class="form-control form-control-sm datetime datetime" autocomplete="off" name="time_post" value="<?php _ec( datetime_show( $time_post ) )?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="post-by d-none" data-by="2">
                            <div class="card-body border-top p-20 listPostByDays">
                                <div class="item my-1">
                                    <div class="input-group input-group-sm input-group-solid bg-white border">
                                        <input type="text" class="form-control form-control-sm datetime fs-12" autocomplete="off" name="time_posts[]" value="">
                                        <span class="input-group-text"><i class="fal fa-calendar-alt"></i></span>
                                        <button type="button" class="btn btn-sm btn-color-gray-500 btn-active-color-danger border-start remove disabled">
                                            <i class="fad fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer py-1 p-r-20 p-l-20">
                                <a href="javascript:void(0);" class="btn btn-link btn-active-color-primary me-5 mb-0 py-2 fs-12 addSpecificDays">
                                    <i class="fal fa-plus"></i> <?php _e("Add more scheduled times")?>
                                </a>

                                <div class="tempPostByDays d-none">
                                    <div class="item my-1">
                                        <div class="input-group input-group-sm input-group-solid bg-white border">
                                            <input type="text" class="form-control form-control-sm fs-12" autocomplete="off" value="">
                                            <span class="input-group-text"><i class="fal fa-calendar-alt"></i></span>
                                            <button type="button" class="btn btn-sm btn-color-gray-500 btn-active-color-danger border-start remove">
                                                <i class="fad fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>

                </div>

                <div class="card mt-4 b-r-10">
                    <div class="card-body p-15 border b-r-10">
                        <div class="d-flex justify-content-end">
                            <?php 
                            if( empty($post) ){
                                $button = 1;
                            }else{
                                if ($post->status==0){
                                    $button = 2;
                                }else{
                                    $button = 1;
                                }
                            }
                            ?>
                            <button type="submit" href="#" class="btn btn-primary btn-hover-scale btnSchedulePost b-r-10 <?php _ec( $button == 1?"":"d-none" )?>">
                                <i class="fal fa-paper-plane"></i> <?php _e("Schedule")?>
                            </button>
                            <button type="submit" href="#" class="btn btn-primary btn-hover-scale btnSaveDraft b-r-10 <?php _ec( $button == 2?"":"d-none" )?>">
                                <i class="fad fa-save"></i> <?php _e("Save as Draft")?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border b-r-10">
                    <div class="card-header">
                        <div class="card-title">
                            <?php _e("Preview")?>
                        </div>
                    </div>
                    <div class="card-body">
                        

                        <div class="mb-5">
                            <div class="mb-3 fw-6 d-flex align-items-center">
                                <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/brands/chrome-icon.png" ) )?>" class="w-16 h-16 me-2">
                                <div><?php _e("Chrome")?></div>
                            </div>

                            <div class="border mw-350 m-auto">
                                <div class="d-flex">
                                    <div>
                                        <img src="<?php _ec( get_file_url( $website->icon ) )?>" class="w-85 h-85 piv-icon">
                                    </div>
                                    <div class="p-10 w-100">
                                        <div class="piv-title"><?php _e("Title")?></div>
                                        <div class="fs-12 piv-text"><?php _e("Your message here...")?></div>
                                        <div class="text-gray-600 fs-12 mt-2"><span><?php _ec( $website->domain )?></span></div>
                                    </div>
                                </div>
                                <div class="pv-large-image d-none">
                                    <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/large-image.svg" ) )?>" class="w-100 piv-image">
                                </div>
                                <div class="pv-btn-actions d-none">
                                    <div class="p-7 piv-btn1">Button 1</div>
                                    <div class="p-7 border-top pv-btn-action-2 d-none piv-btn2"><?php _e("Button 2")?></div>
                                </div>
                            </div>
                            
                        </div>

                        <div class="mb-5">
                            <div class="mb-3 fw-6 d-flex align-items-center">
                                <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/brands/windows-icon.png" ) )?>" class="w-16 h-16 me-2">
                                <div><?php _e("Chrome on Windows")?></div>
                            </div>

                            <div class="mw-350 m-auto">
                                <div class="pv-large-image d-none">
                                    <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/large-image.svg" ) )?>" class="w-100 piv-image">
                                </div>
                                <div class="p-15 b-r-4 bg-gray-100 border">
                                    <div class="d-flex">
                                        <div>
                                            <img src="<?php _ec( get_file_url( $website->icon ) )?>" class="w-54 h-54 piv-icon">
                                        </div>
                                        <div class="px-3 w-100">
                                            <div class="piv-title"><?php _e("Title")?></div>
                                            <div class="fs-12 piv-text"><?php _e("Your message here...")?></div>
                                            <div class="fs-12 mt-2"><span><?php _e("Google Chrome")?> •</span> <span><?php _ec( $website->domain )?></span></div>
                                        </div>
                                    </div>
                                    <div class="pv-btn-actions d-none">
                                        <div class="row">
                                            <button class="btn btn-sm bg-gray-300 b-r-6 border mt-3 col me-1 piv-btn1">Button 1</button>
                                            <button class="btn btn-sm bg-gray-300 b-r-6 border mt-3 col ms-1 pv-btn-action-2 d-none piv-btn2">Button 2</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="mb-3 fw-6 d-flex align-items-center">
                                <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/brands/android-icon.png" ) )?>" class="w-16 h-16 me-2">
                                <div><?php _e("Chrome on Android")?></div>
                            </div>

                            <div class="border mw-350 m-auto p-15 b-r-15 shadow border">
                                <div class="d-flex justify-content-between">
                                    <div class="pe-3 w-100">
                                        <div class="fs-12 text-gray-600 mb-2"><i class="fab fa-chrome fs-14"></i> <span><?php _e("Google Chrome")?> •</span> <span><?php _ec( $website->domain )?></span></div>
                                        <div class="fw-5 mb-2 piv-title"><?php _e("Title")?></div>
                                        <div class="fs-12 piv-text"><?php _e("Your message here...")?></div>
                                    </div>
                                    <div>
                                        <img src="<?php _ec( get_file_url( $website->icon ) )?>" class="w-40 h-40 piv-icon">
                                    </div>
                                </div>
                                <div class="mt-3 pv-large-image d-none">
                                    <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/large-image.svg" ) )?>" class="w-100 piv-image">
                                </div>
                                <div class="d-flex text-center text-uppercase fs-12 mt-3 fw-5 row">
                                    <div class="col-8">
                                        <div class="row pv-btn-actions d-none">
                                            <div class="col piv-btn1"><?php _e("Button 1")?></div>
                                            <div class="col pv-btn-action-2 d-none piv-btn2"><?php _e("Button 2")?></div>
                                        </div>
                                    </div>
                                    <div class="col-4"><?php _e("Settings")?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="mb-3 fw-6 d-flex align-items-center">
                                <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/brands/apple-icon.png" ) )?>" class="w-16 h-16 me-2">
                                <div><?php _e("Apple iOS")?></div>
                            </div>

                            <div class="d-flex border mw-350 m-auto p-15 b-r-15 shadow border align-items-center">
                                <div>
                                    <img src="<?php _ec( get_file_url( $website->icon ) )?>" class="w-54 h-54 piv-icon">
                                </div>
                                <div class="px-3 w-100">
                                    <div class="piv-title"><?php _e("Title")?></div>
                                    <div class="fs-12 piv-text"><?php _e("Your message here...")?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="mb-3 fw-6 d-flex align-items-center">
                                <img src="<?php _ec( get_module_path( __DIR__, "/../Push/Assets/img/brands/firefox-icon.png" ) )?>" class="w-16 h-16 me-2">
                                <div><?php _e("Firefox")?></div>
                            </div>

                            <div class="border mw-350 m-auto p-15 border">
                                <div class="fw-5 piv-title"><?php _e("Title")?></div>
                                <div class="d-flex">
                                    <div>
                                        <img src="<?php _ec( get_file_url( $website->icon ) )?>" class="w-80 h-80 piv-icon">
                                    </div>
                                    <div class="px-3 position-relative w-100">
                                        <div class="fs-12 position-relative mb-4 piv-text"><?php _e("Your message here...")?></div>
                                        <div class="fs-12 mt-2 text-gray-400 position-absolute bottom-0 w-100"><span><?php _e("via")?></span> <span><?php _ec( $website->domain )?></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

</form>
<?php if ($post): ?>
<script type="text/javascript">
    var title_el = $(".push-main input[name='title']");
    var text = title_el.val();
    var c = title_el.attr("data-default-text");
    if(text == "" && c != ""){
        text = c;
    }
    $(".piv-title").html(text);

    var large_image = $(".push-main input[name='large_image']");
    var img = large_image.val();
    var c = large_image.attr("data-default-image");
    if(img == "" && c != ""){
        img = c;
    }
    $(".piv-image").attr("src", img);

    <?php if ($large_image_status): ?>
    $(".pv-large-image").removeClass("d-none");
    <?php endif ?>
</script>
<?php endif ?>
