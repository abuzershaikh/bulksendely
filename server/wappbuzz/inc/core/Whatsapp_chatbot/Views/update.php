<form class="actionForm" action="<?php _eC(get_module_url("save/" . get_data($result, "ids"))) ?>" method="POST" data-redirect="<?php _ec(get_module_url("index/list/" . $account->ids)) ?>">
    <div class="container my-5 mw-800">
        <div class="bd-search position-relative me-auto">
            <h2 class="mb-0 py-4"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e("Chatbot item") ?></h2>
        </div>

        <div class="card b-r-6 h-100 post-schedule wrap-caption">
            <div class="card-header">
                <h3 class="card-title"><?php _e("Update") ?></h3>
                <div class="card-toolbar"></div>
            </div>
            <div class="card-body position-relative">
                <input type="text" class="form-control form-control-solid d-none" name="instance_id" value="<?php _ec($account->token) ?>" required>
                <input type="text" class="form-control form-control-solid d-none" name="ids" value="<?php _ec(get_data($result, "ids")) ?>">
                <div class="mb-4">
                    <label class="form-label"><?php _e("Status") ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" <?php _ec((get_data($result, "status") == 1 || get_data($result, "status") == "") ? "checked='true'" : "") ?> id="status_enable" value="1">
                            <label class="form-check-label" for="status_enable"><?php _e('Enable') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" <?php _ec(get_data($result, "status", "radio", 0)) ?> id="status_disable" value="0">
                            <label class="form-check-label" for="status_disable"><?php _e('Disable') ?></label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e("is not Match default response?") ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_default" <?php _ec((get_data($result, "is_default") == 0 || get_data($result, "is_default") == "") ? "checked='true'" : "") ?> id="is_default_enable" value="0">
                            <label class="form-check-label" for="is_default_enable"><?php _e('No') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="is_default" <?php _ec(get_data($result, "is_default", "radio", 1)) ?> id="is_default_disable" value="1">
                            <label class="form-check-label" for="is_default_disable"><?php _e('Yes') ?></label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e("Send to") ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="send_to" <?php _ec((get_data($result, "send_to") == 1 || get_data($result, "send_to") == "") ? "checked='true'" : "") ?> id="send_to_all" value="1">
                            <label class="form-check-label" for="send_to_all"><?php _e('All') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="send_to" <?php _ec((get_data($result, "send_to") == 2) ? "checked='true'" : "") ?> id="send_to_individual" value="2">
                            <label class="form-check-label" for="send_to_individual"><?php _e('Individual') ?></label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="send_to" <?php _ec((get_data($result, "send_to") == 3) ? "checked='true'" : "") ?> id="send_to_group" value="3">
                            <label class="form-check-label" for="send_to_group"><?php _e('Group') ?></label>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label"><?php _e("Type") ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type_search" <?php _ec((get_data($result, "type_search") == 1 || get_data($result, "type_search") == "") ? "checked='true'" : "") ?> id="type_search_1" value="1">
                            <label class="form-check-label" for="type_search_1"><?php _e('Message contains the keyword') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="type_search" <?php _ec((get_data($result, "type_search") == 2) ? "checked='true'" : "") ?> id="type_search_2" value="2">
                            <label class="form-check-label" for="type_search_2"><?php _e('Message contains whole keyword') ?></label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("Name") ?></label>
                    <input type="text" class="form-control form-control-solid" name="name" value="<?php _ec(get_data($result, "name")) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("Description") ?></label>
                    <input type="text" class="form-control form-control-solid" name="description" value="<?php _ec(get_data($result, "description")) ?>">
                </div>

                <div class="mb-3">
                    <label for="keywords" class="form-label"><?php _e("Keywords") ?></label>
                    <input type="text" class="form-control form-control-solid" data-role="tagsinput" id="keywords" name="keywords" value="<?php _ec(get_data($result, "keywords")) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("Next Bot Action") ?></label>
                    <input type="text" class="form-control form-control-solid" name="nextBot" value="<?php _ec(get_data($result, "nextBot")) ?>">
                </div>

                <div class="mb-3">
                    <label for="presence-wraper" class="form-label"><?php _e("Presence Delay") ?></label>
                    <div class="row" id="presence-wraper">
                        <div class="col-4">
                            <input type="number" min="0" max="25" class="form-control form-control-solid" name="presenceTime" value="<?php _ec(get_data($result, "presenceTime")) ?>" required>
                        </div>
                        <div class="col-8">
                            <select class="form-select form-select-solid" name="presenceType">
                                <option value="0" <?php _ec((get_data($result, "presenceType") == 0) ? "selected" : "") ?>><?php _e("Disable") ?></option>
                                <option value="1" <?php _ec((get_data($result, "presenceType") == 1) ? "selected" : "") ?>><?php _e("Typing") ?></option>
                                <option value="2" <?php _ec((get_data($result, "presenceType") == 2) ? "selected" : "") ?>><?php _e("Recording") ?></option>
                            </select>
                        </div>
                    </div>
                    <ul class="text-gray-400 fs-12">
                        <li><?php _e("Time in seconds Max: 25") ?></li>
                    </ul>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e("Use AI") ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="use_ai" <?php _ec((get_data($result, "use_ai") == 0 || get_data($result, "use_ai") == "") ? "checked='true'" : "") ?> id="use_ai_enable" value="0">
                            <label class="form-check-label" for="use_ai_enable"><?php _e('Not use AI') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="use_ai" <?php _ec(get_data($result, "use_ai", "radio", 1)) ?> id="use_ai_disable" value="1">
                            <label class="form-check-label" for="use_ai_disable"><?php _e('Use AI') ?></label>
                        </div>
                    </div>
                </div>

                <?php if (permission("whatsapp_data_capturer")) { ?>
                    <div class="mb-3">
                        <label for="presence-wraper" class="form-label"><?php _e("Save Data") ?></label>

                        <div class="mb-2">
                            <label class="i-radio i-radio--tick i-radio--brand m-r-10">
                                <input type="radio" name="save_data" checked="true" value="1" <?php _e(get_data($result, 'save_data', 'radio', 1)) ?>> <?php _e('No save response') ?>
                                <span></span>
                            </label>
                            <label class="i-radio i-radio--tick i-radio--brand m-r-10">
                                <input type="radio" name="save_data" value="2" <?php _e(get_data($result, 'save_data', 'radio', 2)) ?>> <?php _e('The response is saved') ?>
                                <span></span>
                            </label>
                        </div>

                        <div class="form-group mt-2 input-div <?php _e(get_data($result, 'save_data') == 2 ? '' : 'd-none') ?>">
                            <div class="form-group mt-2">
                                <input class="form-control" name="inputname" placeholder="<?php _e("Field Name") ?>" value="<?php _e(get_data($result, 'inputname')) ?>">
                            </div>
                        </div>

                        <script>
                            $('input[type=radio][name=save_data]').change(function() {

                                if (this.value == 2) {
                                    $('.input-div').removeClass('d-none')
                                } else {
                                    $('.input-div').addClass('d-none')
                                }
                            })
                        </script>
                    </div>

                <?php } else { ?>

                    <input type="hidden" name="save_data" value="1">

                <?php } ?>


                <?php if (permission("whatsapp_api_data")) :  ?>
                    <?php if (!empty($result)) {
                        $api_config = json_decode($result->api_config ?? '{"method": "get", "body_type": "none"}');
                    } else {
                        $api_config = [
                            'body_type' => 'none'
                        ];
                    }
                    ?>

                    <style>
                        .api-toolbar {
                            border-top: 1px solid #f4f4f4;
                            border-radius: 0 0 4px 4px;
                        }

                        .api-toolbar button,
                        .api-toolbar label {
                            border-top: none;
                            border-bottom: none;
                            border-left: none;
                            border-right: 1px solid #f4f4f4;
                            border-radius: 0 !important;
                        }

                        .api-titles {
                            border-left: 1px solid #f4f4f4;
                            border-right: 1px solid #f4f4f4;
                            border-bottom: 1px solid #f4f4f4;
                            border-top: 1px solid #f4f4f4;
                            font-weight: bold;
                        }

                        .api-card {
                            border-radius: 4px;
                        }

                        .hs-wrap .wa-lm-empty {
                            border: none !important;
                        }

                        .btn-wa-add-body-api.active {
                            color: #fff;
                            background-color: #335de7 !important;
                            border-color: #2754e6 !important;
                        }
                    </style>


                    <label for="presence-wraper" class="form-label"><?php _e('API Rest Data') ?></label>

                    <div class="mb-2">
                        <label class="i-radio i-radio--tick i-radio--brand m-r-10">
                            <input type="radio" name="get_api_data" checked="true" value="1" <?php _e(get_data($result, 'get_api_data', 'radio', 1)) ?>> <?php _e('Disabled') ?>
                            <span></span>
                        </label>
                        <label class="i-radio i-radio--tick i-radio--brand m-r-10">
                            <input type="radio" name="get_api_data" value="2" <?php _e(get_data($result, 'get_api_data', 'radio', 2)) ?>> <?php _e('Enabled') ?>
                            <span></span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <div class="form-group mt-2 input-div-api <?php _e(get_data($result, 'get_api_data') == 2 ? '' : 'd-none') ?>">
                            <div class="input-group">
                                <select name="api_config[method]" class="form-control" style="max-width: 80px;">
                                    <option value="get" <?php _e((get_data($api_config, 'method') == 'get') ? 'selected' : "") ?>>GET</option>
                                    <option value="post" <?php _e((get_data($api_config, 'method') == 'post') ? 'selected' : "") ?>>POST</option>
                                </select>
                                <input class="form-control" name="api_url" placeholder="<?php _e("URL") ?>" value="<?php _e(get_data($result, 'api_url')) ?>">
                            </div>

                            <div class="card mt-3 api-card">

                                <ul class="nav nav-pills rounded fs-14 border" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white" id="header-tab" data-bs-toggle="tab" data-bs-target="#api_header" type="button" role="tab" aria-controls="home" aria-selected="true">
                                            <?php _e("Header") ?>
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white active" id="body-tab" data-bs-toggle="tab" data-bs-target="#api_body" type="button" role="tab" aria-controls="profile" aria-selected="false">
                                            <?php _e("Body") ?>
                                        </button>
                                    </li>
                                </ul>


                                <div class="tab-content" id="nav-tabAPI">
                                    <div class="tab-pane fade" id="api_header" role="tabpanel" aria-labelledby="header-tab">

                                        <div class="hs-wrap" style="min-height: 260px;max-height: 260px;">
                                            <div class="hs-div">

                                                <div class="row m-0">
                                                    <div class="col-6 text-center api-titles">Key</div>
                                                    <div class="col-6 text-center api-titles">Value</div>
                                                </div>

                                                <ul class="list-group bg-solid-danger wa-add-header-api-items">

                                                    <?php
                                                    $header_array = get_data($api_config, 'header');

                                                    if (!empty($header_array) && count($header_array) > 0) {

                                                        foreach ($header_array as $key => $itm_header) { ?>

                                                            <li class="list-group-item px-0 py-0 post wa-add-header-api-item wa-add-header-api-item-<?php echo $key ?>">
                                                                <div class="d-flex mx-0">
                                                                    <input class="form-control border-0" name="api_config[header][<?php echo $key ?>][name]" placeholder="<?php _e("Write Key") ?>" value="<?php echo $itm_header->name ?>">
                                                                    <span style="border-left: 1px solid #f4f4f4 !important;"></span>
                                                                    <input class="form-control border-0" name="api_config[header][<?php echo $key ?>][value]" placeholder="<?php _e("Write Value") ?>" value="<?php echo $itm_header->value ?>">
                                                                </div>
                                                            </li>


                                                        <?php }
                                                    } else { ?>
                                                        <li class="list-group-item px-3 post wa-lm-empty">
                                                            <div class="d-flex flex-column justify-content-center align-items-center text-gray-500 h-100 mih-300">
                                                                <img class="mh-190 mb-4" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
                                                            </div>
                                                        </li>
                                                    <?php } ?>

                                                </ul>

                                            </div>
                                        </div>

                                        <div class="api-toolbar">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-secondary btn-wa-add-header-api" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="" data-original-title="Add Header">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-wa-rem-header-api" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="" data-original-title="Remove Header">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- API Body -->
                                    <div class="tab-pane fade show active" id="api_body" role="tabpanel" aria-labelledby="body-tab">
                                        <div class="hs-wrap" style="min-height: 260px;max-height: 260px;">
                                            <div class="hs-div">

                                                <div class="row m-0">
                                                    <div class="col-6 text-center api-titles">Key</div>
                                                    <div class="col-6 text-center api-titles">Value</div>
                                                </div>

                                                <ul class="list-group bg-solid-danger wa-add-body-api-items">

                                                    <?php
                                                    $body_array = get_data($api_config, 'body');

                                                    if (!empty($body_array) && count($body_array) > 0) {

                                                        foreach ($body_array as $key => $itm_body) { ?>

                                                            <li class="list-group-item px-0 py-0 post wa-add-body-api-item wa-add-body-api-item-<?php echo $key ?>">
                                                                <div class="d-flex mx-0">
                                                                    <input class="form-control  border-0" name="api_config[body][<?php echo $key ?>][name]" placeholder="<?php _e("Write Key") ?>" value="<?php echo $itm_body->name ?>">
                                                                    <span style="border-left: 1px solid #f4f4f4 !important;"></span>
                                                                    <input class="form-control  border-0" name="api_config[body][<?php echo $key ?>][value]" placeholder="<?php _e("Write Value") ?>" value="<?php echo $itm_body->value ?>">
                                                                </div>
                                                            </li>


                                                        <?php }
                                                    } else { ?>
                                                        <li class="list-group-item px-3 post wa-lm-empty">
                                                            <div class="d-flex flex-column justify-content-center align-items-center text-gray-500 h-100 mih-300">
                                                                <img class="mh-190 mb-4" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
                                                            </div>
                                                        </li>
                                                    <?php } ?>

                                                </ul>

                                            </div>
                                        </div>


                                        <div class="api-toolbar d-flex justify-content-between">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-secondary btn-wa-add-body-api" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="" data-original-title="Add Header">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-wa-rem-body-api" data-toggle="tooltip" data-trigger="hover" data-placement="top" title="" data-original-title="Remove Header">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <script>
                        $(function() {
                            //$(".hs-div").niceScroll({cursorcolor:"#00F"});
                            $(".hs-wrap").niceScroll(".hs-div", {
                                cursorcolor: "#dddddd"
                            });


                            $('input[type=radio][name=get_api_data]').change(function() {

                                if (this.value == 2) {
                                    $('.input-div-api').removeClass('d-none')
                                } else {
                                    $('.input-div-api').addClass('d-none')
                                }
                            })
                        });
                    </script>
                <?php else : ?>
                    <input type="hidden" name="get_api_data" value="1">
                <?php endif ?>



                <ul class="nav nav-pills mb-3 bg-white rounded fs-14 nx-scroll overflow-x-auto d-flex text-over b-r-6 border" id="pills-tab">
                    <li class="nav-item me-0">
                        <label for="type_text_media" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec((get_data($result, "type") == 1 || get_data($result, "type") == "") ? "active" : "") ?>" data-bs-toggle="pill" data-bs-target="#wa_text_and_media" type="button" role="tab"><?php _e("Text & Media") ?></label>
                        <input class="d-none" type="radio" name="type" id="type_text_media" <?php _ec((get_data($result, "type") == 1 || get_data($result, "type") == "") ? "checked='true'" : "") ?> value="1">
                    </li>
                    <?php echo view_cell('\Core\Whatsapp_button_template\Controllers\Whatsapp_button_template::widget_menu', ["result" => $result]) ?>
                    <?php echo view_cell('\Core\Whatsapp_list_message_template\Controllers\Whatsapp_list_message_template::widget_menu', ["result" => $result]) ?>
                    <?php echo view_cell('\Core\Whatsapp_poll_template\Controllers\Whatsapp_poll_template::widget_menu', ["result" => $result]) ?>
                </ul>


                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show <?php _ec((get_data($result, "type") == 1 || get_data($result, "type") == "") ? " active" : "") ?>" id="wa_text_and_media">
                        <?php echo view_cell('\Core\Whatsapp\Controllers\Whatsapp::widget_content', ["result" => $result]) ?>

                        <label class="form-label"><?php _e("Caption") ?></label>
                        <?php echo view_cell('\Core\Caption\Controllers\Caption::block', ['name' => 'caption', 'value' => get_data($result, "caption")]) ?>
                        
                        <ul class="text-gray-400 fs-12">
                            <li><?php _e("Random message by Spintax") ?></li>
                            <li><?php _e("Ex: {Hi|Hello|Hola}") ?></li>
                            <li><?php _e("Params:") ?></li>
                            <li><?php _e("[greet|good morning|good afternoon|good night] return greet acording your timezone") ?></li>
                            <li><?php _e("[wa_name] return user pushname") ?></li>
                            <li><?php _e("[me_wa_name] return your pushname") ?></li>
                            <li><?php _e("[now_format|en|DD MMM YYYY, HH:mm] return time now with languaje") ?></li>
                        </ul>
                    </div>
                    <?php echo view_cell('\Core\Whatsapp_button_template\Controllers\Whatsapp_button_template::widget_content', ["result" => $result]) ?>
                    <?php echo view_cell('\Core\Whatsapp_list_message_template\Controllers\Whatsapp_list_message_template::widget_content', ["result" => $result]) ?>
                    <?php echo view_cell('\Core\Whatsapp_poll_template\Controllers\Whatsapp_poll_template::widget_content', ["result" => $result]) ?>
                </div>

            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?php _ec(get_module_url("index/list/" . $account->ids)) ?>" class="btn btn-dark btn-hover-scale">
                        <?php _e("Back") ?>
                    </a>
                    <button type="submit" class="btn btn-primary btn-hover-scale">
                        <?php _e("Submit") ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>


<div class="wa-add-body-api-template d-none">
    <li class="list-group-item px-0 py-0 post wa-add-body-api-item wa-add-body-api-item-{count}">
        <div class="d-flex mx-0">
            <input class="form-control border-0" name="api_config[body][{count}][name]" placeholder="<?php _e("Write Key") ?>" value="">
            <span style="border-left: 1px solid #f4f4f4 !important;"></span>
            <input class="form-control border-0" name="api_config[body][{count}][value]" placeholder="<?php _e("Write Value") ?>" value="">
        </div>
    </li>
</div>

<div class="wa-add-header-api-template d-none">
    <li class="list-group-item px-0 py-0 post wa-add-header-api-item wa-add-header-api-item-{count}">
        <div class="d-flex mx-0">
            <input class="form-control border-0" name="api_config[header][{count}][name]" placeholder="<?php _e("Write Key") ?>" value="">
            <span style="border-left: 1px solid #f4f4f4 !important;"></span>
            <input class="form-control border-0" name="api_config[header][{count}][value]" placeholder="<?php _e("Write Value") ?>" value="">
        </div>
    </li>
</div>

<div class="wa-add-body-empty-template d-none">
    <li class="list-group-item px-3 post wa-lm-empty">
        <div class="d-flex flex-column justify-content-center align-items-center text-gray-500 h-100 mih-300">
            <img class="mh-190 mb-4" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
        </div>
    </li>
</div>

<script type="text/javascript">
    $(function() {
        Core.tagsinput();
    });
</script>