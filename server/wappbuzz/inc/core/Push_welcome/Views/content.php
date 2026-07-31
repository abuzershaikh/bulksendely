<?php
$domain = PUSH_DOMAIN;
$domain_name = "";

if($domain){
    $domain = unserialize($domain);
    $domain_name = $domain->domain;
}
?>

<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center mt-4 mb-5">
    <div class="bd-search position-relative me-auto zindex-1">
        <h2><i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( $config['name'] )?></h2>
        <p class="mb-0"><?php _e( $config['desc'] )?></p>
    </div>
</div>

<div class="container my-5">
    <?php if (!empty($result)): ?>
            
            <?php foreach ($result as $key => $value): ?>

            <?php

            $days = 0;
            $hours = 0;
            $mins = 5;

            if($value->delay){
                $delay = json_decode($value->delay);
                $days = $delay->days;
                $hours = $delay->hours;
                $mins = $delay->mins;
            }

            $action_status = 0;
            $action_button_count = 0;
            if($value->actions != ""){
                $actions = json_decode($value->actions);
                if(!empty($actions)){
                    $action_status = 1;
                    if ( count($actions) == 1 ) {
                        $action_button1_name = $actions[0]->title;
                        $action_button1_url = $actions[0]->url;

                        $action_button2_name = "";
                        $action_button2_url = "";
                        $action_button_count = 1;
                    } else if( count($actions) == 2 ){
                        $action_button1_name = $actions[0]->title;
                        $action_button1_url = $actions[0]->url;

                        $action_button2_name = $actions[1]->title;
                        $action_button2_url = $actions[1]->url;
                        $action_button_count = 2;
                    }
                }
            }
            ?>

            <div class="mw-450 m-auto position-relative zindex-1">
                <div class="timeline-panel p-0 shadow-none border-0">

                    <div class="position-absolute t-0 mn-t-13 r-10 zindex-2 d-flex">
                        <a class="actionItem bg-gray-800 bg-hover-primary text-white b-r-20 px-3 py-2 d-block fs-10 me-1" href="<?php _e( get_module_url("popup/".$value->ids) )?>" data-popup="CreateNotificationModal">
                            <i class="fas fa-pencil-alt"></i> <span><?php _e("Edit")?></span>
                        </a>
                        <a class="actionItem bg-danger bg-hover-danger text-white b-r-20 p-l-10 p-r-10 py-2 d-block fs-10" href="<?php _e( base_url("push_schedules/delete/".$value->ids) )?>" data-redirect="" data-confirm="<?php _e("Are you sure to delete this items?")?>">
                            <i class="fal fa-trash-alt"></i>
                        </a>
                    </div>
                    <div class="m-auto mb-2">
                        <?php if ($value->large_image_status): ?>
                        <div class="pv-large-image position-relative zindex-1">
                            <img src="<?php _ec( get_file_url( $value->large_image ) )?>" class="w-100 btl-r-10 btr-r-10">
                        </div>
                        <?php endif ?>
                        <div class="p-15 bg-white border <?php _ec( $value->large_image_status?"bbl-r-10 bbr-r-10":"b-r-10" )?> ">
                            <div class="d-flex">
                                <?php if ($value->icon != ""): ?>
                                <div>
                                    <img src="<?php _ec( get_file_url( $value->icon ) )?>" class="w-54 h-54 ">
                                </div>
                                <?php endif ?>
                                <div class="px-3 w-100">
                                    <div class="fw-6"><?php _e( $value->title )?></div>
                                    <div class="fs-12"><?php _e( $value->message )?></div>
                                    <div class="fs-12 mt-2"><span><?php _ec($domain_name)?></span></div>
                                </div>
                            </div>
                            <?php if ($action_status): ?>
                            <div class="pv-btn-actions px-2">
                                <div class="row">
                                    <button class="btn btn-sm bg-gray-300 b-r-6 border mt-3 col me-1"><?php _e($action_button1_name)?></button>
                                    <?php if ($action_button_count == 2): ?>
                                    <button class="btn btn-sm bg-gray-300 b-r-6 border mt-3 col ms-1"><?php _e($action_button2_name)?></button>
                                    <?php endif ?>
                                </div>
                            </div>
                            <?php endif ?>
                        </div>
                    </div>
                        
                    <div class="justify-content-center push-action-save-time">
                        <form class="actionForm" action="<?php _e( get_module_url("save_time/".$value->ids) )?>" method="POST" data-result="html" data-content="ajax-result" date-redirect="false" data-loading="false">
                            <div class="text-center">
                                <div class="d-inline-block justify-content-start align-items-center fs-12">
                                    <span class="me-1 d-inline-block mb-1"><?php _e("After")?></span>
                                    <span class="me-1 d-inline-block mb-1">
                                        <select class="form-select form-select-sm bg-white border fs-12 auto-submit push-delay push-delay-days" name="days">
                                            <?php for ($i=0; $i <= 30; $i++) { ?>
                                                <option value="<?php _ec($i)?>" <?php _ec( $days == $i?"selected":"" )?> ><?php _ec($i)?></option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                    <span class="me-1 d-inline-block mb-1"><?php _e("days")?></span>
                                    <span class="me-1 d-inline-block mb-1">
                                        <select class="form-select form-select-sm bg-white border fs-12 auto-submit push-delay push-delay-hours" name="hours">
                                            <?php for ($i=0; $i <= 23; $i++) { ?>
                                                <option value="<?php _ec($i)?>" <?php _ec( $hours == $i?"selected":"" )?>><?php _ec($i)?></option>
                                            <?php } ?>
                                        </select>
                                    </span>
                                    <span class="me-1 d-inline-block mb-1"><?php _e("hours")?></span>
                                    <span class="me-1 d-inline-block mb-1">
                                        <select class="form-select form-select-sm bg-white border fs-12 auto-submit push-delay <?php _ec($key > 0?"push-delay-mins":"")?>" name="mins">
                                            <?php for ($i=0; $i <= 59; $i++) { ?>
                                                <?php if ($i%5==0): ?>
                                                    <?php if ($i == 0): ?>
                                                        <option value="<?php _ec($i)?>" class="<?php _ec($key > 0?"push-delay-mins-sezo":"")?>" <?php _ec( $mins == $i?"selected":"" )?>><?php _ec($i)?></option>
                                                    <?php else: ?>
                                                        <option value="<?php _ec($i)?>" <?php _ec( $mins == $i?"selected":"" )?>><?php _ec($i)?></option>
                                                    <?php endif ?>
                                                
                                                <?php endif ?>
                                            <?php } ?>
                                        </select>
                                    </span>
                                    <span class="me-1 d-inline-block mb-1"><?php _e("mins")?></span>


                                </div>
                                <div class="fs-12">
                                    <?php if ($key==0): ?>
                                        <?php _e("from when a visitor subscribes to Web Push")?>
                                    <?php else: ?>
                                        <?php _e("from previous notification")?>
                                    <?php endif ?>
                                    

                                </div>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
            <?php endforeach ?>

 

    <?php else: ?>

    <div class="mw-800 mx-auto text-center">
        <div class="my-5 m-t-70">
            <div class="mb-5">
                <h3 class="fs-20 mb-3"><?php _e("Welcome to our platform")?></h3>
                <div class="fs-16 mb-3"><?php _e("We help you enhance customer engagement through the strategic use of real-time web push notifications.")?></div>
            </div>
            <div class="fs-70 mb-5 mw-400 mx-auto"><img class="w-100" src="<?php _ec( get_module_path( __DIR__, "Assets/img/welcome_drip.png" ) )?>"></div>
            <a href="<?php _e( get_module_url("popup") )?>" class="actionItem btn btn-dark b-r-100" data-popup="CreateNotificationModal"><?php _e("Get started here")?></a>
        </div>
    </div>   

    <?php endif ?>

</div>

<script type="text/javascript">
    $(function(){
        $(document).on("change", ".push-delay", function(){
            var parent = $(this).parents(".push-action-save-time");
            var days = parent.find(".push-delay-days").val();
            var hours = parent.find(".push-delay-hours").val();
            var mins = parent.find(".push-delay-mins").val();
            if(days == 0 && hours == 0){
                parent.find(".push-delay-mins-sezo").addClass("d-none");
                parent.find(".push-delay-mins").val(5);
            }else{
                parent.find(".push-delay-mins-sezo").removeClass("d-none");
            }
        })
    });
</script>