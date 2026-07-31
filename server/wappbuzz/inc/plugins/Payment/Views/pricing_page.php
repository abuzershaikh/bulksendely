<div class="pricing container m-b-120" id="pricing">
    
    <div class="d-flex justify-content-center align-items-center h-100 mw-800 mx-auto text-center m-b-80" data-aos="fade-down">
        <div>
            <div class="form-check form-switch form-switch-pricing form-check-custom form-check-solid form-check-warning d-flex justify-content-center align-items-center">
                <label class="form-check-label fw-6 text-gray-800 m-l-0 ps-0" for="plan_by">
                        <?php _e("Monthly")?>
                </label>
                <input class="form-check-input plan_by" type="checkbox" id="plan_by" value="1">
                <label class="form-check-label fw-6 text-gray-800 m-l-0" for="plan_by">
                        <?php _e("Annually")?>
                </label>
            </div>
        </div>

    </div>

    <div class="row" data-aos="fade-up">
        <?php if (!empty($plans)): ?>

            <?php $post_social_networks = [];?>

            <?php foreach ($plans as $index => $plan): ?>

                <?php
                    $permissions = json_decode($plan->permissions, 1);
                ?>

                <?php if ($index == 0): ?>
                    

                    <div class="col-md-3 mb-4">
                        <div class="item py-5 d-none d-md-block">
                            
                            <div class="px-4 d-flex justify-content-center align-items-end pricing-feature-comparison">
                                <h1 class="fs-35"><?php _e("Features comparison")?></h1>
                            </div>
                                    
                            <ul class="px-4">
                                <?php
                                    $plan_items = request_service("plans");
                                ?>

                                <?php if ( !empty($plan_items) ): ?>
                                    
                                    <?php foreach ($plan_items as $plan_item): ?>
                                        <li class="fs-14 fw-6 text-primary text-uppercase pt-5 text-start"><i class="fad fa-stars text-warning fs-20"></i> <?php _e( $plan_item["label"] )?></li>

                                        <?php if (isset($plan_item['before_items']) && isset($plan_item['before_items'][$plan->id]) && is_array($plan_item['before_items'][$plan->id]) && !empty($plan_item['before_items'][$plan->id])): ?>
                                                
                                            <?php foreach ($plan_item['before_items'][$plan->id] as $custom_key => $custom_value): ?>
                                                <?php if (isset($custom_value['name'])): ?>
                                                    <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent">
                                                        <div class="h-41 fw-6 py-2 text-over-all"><i class="me-2 <?php _ec(isset($custom_value["icon"])?$custom_value["icon"]:"")?> position-relative t-3" <?php _ec( isset($custom_value["color"])?"style='color: ".$custom_value["color"]."'":"" )?>;></i> <?php _e($custom_value["name"])?></div>
                                                    </li>
                                                <?php else: ?>
                                                    <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent"></li>
                                                <?php endif ?>
                                                
                                            <?php endforeach ?>

                                        <?php endif ?>

                                        <?php if (!empty($plan_item['items'])): ?>

                                            <?php if ( $plan_item['permission'] ): ?>
                                               <li class="d-block text-center plan-post"></li>
                                            <?php else: ?>
                                                <?php foreach ($plan_item['items'] as $key => $value): ?>
                                                    <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent">
                                                        <div class="h-41 fw-6 py-2 text-over-all"><i class="me-2 <?php _ec($value["icon"])?> position-relative t-3" style="color: <?php _ec($value["color"])?>;"></i> <?php _e($value["name"])?></div>
                                                    </li>
                                                <?php endforeach ?>
                                            <?php endif ?>
                                            
                                        <?php endif ?>

                                        <?php if (isset($plan_item['after_items']) && isset($plan_item['after_items'][$plan->id]) && is_array($plan_item['after_items'][$plan->id]) && !empty($plan_item['after_items'][$plan->id])): ?>
                                                
                                            <?php foreach ($plan_item['after_items'][$plan->id] as $custom_key => $custom_value): ?>
                                                <?php if (isset($custom_value['name'])): ?>
                                                    <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent">
                                                        <div class="h-41 fw-6 py-2 text-over-all"><i class="me-2 <?php _ec(isset($custom_value["icon"])?$custom_value["icon"]:"")?> position-relative t-3" <?php _ec( isset($custom_value["color"])?"style='color: ".$custom_value["color"]."'":"" )?>;></i> <?php _e($custom_value["name"])?></div>
                                                    </li>
                                                <?php else: ?>
                                                    <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent"></li>
                                                <?php endif ?>
                                                
                                            <?php endforeach ?>

                                        <?php endif ?>
                                    <?php endforeach ?>
                                        
                                <?php endif ?>

                                <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent">
                                    <div class="h-40 fw-6 py-2"><i class="me-2 fad fa-cloud-upload text-primary"></i> <?php _e("Cloud import")?></div>
                                </li>

                                <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent">
                                    <div class="h-40 fw-6 py-2"><i class="me-2 fad fa-box-open text-success"></i> <?php _e("Max storage size")?></div>
                                </li>

                                <li class="plan-item-key d-flex justify-content-between align-items-center border-bottom-transparent">
                                    <div class="h-40 fw-6 py-2"><i class="me-2 fad fa-file-upload text-danger"></i> <?php _e("Max file size")?></div>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif ?>

                <?php
                    $plan_items = request_service("plans");
                ?>

                <?php if ( !empty($plan_items) ): ?>
                    <?php foreach ($plan_items as $plan_item): ?>
                        <?php if (!empty($plan_item['items'])): ?>
                            <?php if ( $plan_item['permission'] ): ?>
                                <?php foreach ($plan_item['items'] as $key => $value): ?>
                                    <?php 
                                        if ( isset( $permissions[ $value['id'] ] ) ){
                                            $post_social_networks[$value['id']] = $value;
                                        } 
                                    ?>
                                <?php endforeach ?>
                            <?php endif ?>
                        <?php endif ?>
                    <?php endforeach ?>
                <?php endif ?>

            <?php endforeach ?>

            <?php foreach ($plans as $plan): ?>

                <?php
                    $permissions = json_decode($plan->permissions, 1);
                ?>

                <div class="col-md-3 mb-4">
                    <div class="item shadow position-relative b-r-30 py-5">
                        <?php if ($plan->featured): ?>
                        <div class="featured bg-warning position-absolute w-100 fw-6 text-white text-uppercase"><i class="fad fa-stars"></i> <?php _e("Popular")?></div>
                        <?php endif ?>

                        <div class="pricing-top">
                            <div class="pricing-head px-4 mb-3 text-center">
                                <h1 class="fs-30 text-primary"><?php _e($plan->name)?></h1>
                                <div class="fs-16 text-gray-600"><?php _e($plan->description)?></div>
                            </div>

                            <div class="pricing-price px-4">
                                <div class="d-flex align-items-center justify-content-center text-gray-800">
                                    <div class="fs-45 fw-9">
                                        <span class="by_monthly text-gray-800"><?php _e( get_option("payment_symbol", "$") )?><?php _e($plan->price_monthly)?></span>
                                        <span class="by_annually d-none"><?php _e( get_option("payment_symbol", "$") )?><?php _e($plan->price_annually)?></span>
                                    </div>
                                    <div class="text-gray-600 fs-16 position-relative t-3"><?php _e("/month")?></div>
                                </div>
                                
                            </div>
                        </div>
                        
                        <ul class="px-4">
                            <?php
                                $plan_items = request_service("plans");
                            ?>

                            <?php if ( !empty($plan_items) ): ?>

                                <?php foreach ($plan_items as $plan_item): ?>
                                    <li class="fs-14 fw-6 text-primary text-uppercase pt-5 text-start pricing-feature-head"><i class="fad fa-stars text-warning fs-20"></i> <?php _e( $plan_item["label"] )?></li>

                                    <?php if (!empty($plan_item['items'])): ?>

                                        <?php if ( $plan_item['permission'] ): ?>
                                            <li class="d-block text-center plan-post">
                                                <div class="px-4 mb-4 w-100">
                                                    <div>
                                                        <?php if ($plan->plan_type == 1): ?>
                                                            <div class="text-warning fw-6 fs-14"><?php _e( sprintf(__("Add up to %d social accounts"), $plan->number_accounts*$total_social) )?></div>
                                                            <div class="fs-12 text-gray-600"><?php _e( sprintf(__("%d accounts on each platform"), $plan->number_accounts) )?></div>                           
                                                        <?php else: ?>
                                                            <div class="text-warning fw-6 fs-14 m-b-20 w-100"><?php _e( sprintf(__("%d Social Accounts"), $plan->number_accounts) )?></div>
                                                        <?php endif ?>
                                                    </div>
                                                </div>
                                                <?php foreach ($plan_item['items'] as $key => $value): ?>
                                                    <?php if ( isset( $permissions[ $value['id'] ] ) && isset( $post_social_networks[ $value['id'] ] ) ): ?>
                                                    <span class="fs-26 d-inline-block w-30 text-center">
                                                        <i class="<?php _ec( $value['icon'] )?>" style="color: <?php _ec( $value['color'] )?>;"></i>
                                                    </span>
                                                    <?php endif ?>
                                                    
                                                <?php endforeach ?>
                                            </li>
                                        <?php else: ?>
                                            <?php if (isset($plan_item['before_items']) && isset($plan_item['before_items'][$plan->id]) && is_array($plan_item['before_items'][$plan->id]) && !empty($plan_item['before_items'][$plan->id])): ?>
                                                
                                                <?php foreach ($plan_item['before_items'][$plan->id] as $custom_key => $custom_value): ?>
                                                    <li class="plan-item-value d-flex justify-content-between align-items-center border-bottom">
                                                        <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e($custom_value["name"])?></div>
                                                        <div class="h-40 me-2 pricing-feature-icon py-2"><?php _e($custom_value["data"])?></div>
                                                    </li>
                                                <?php endforeach ?>

                                            <?php endif ?>

                                            <?php foreach ($plan_item['items'] as $key => $value): ?>
                                                <li class="plan-item-value d-flex justify-content-between align-items-center border-bottom">
                                                    <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e($value["name"])?></div>
                                                    <div class="h-40 me-2 fs-23 pricing-feature-icon py-2 <?php _ec( isset( $permissions[ $value['id'] ] )?"text-success":"" )?>"><i class="fad fa-check-circle"></i></div>
                                                </li>
                                            <?php endforeach ?>

                                            <?php if (isset($plan_item['after_items']) && isset($plan_item['after_items'][$plan->id]) && is_array($plan_item['after_items'][$plan->id]) && !empty($plan_item['after_items'][$plan->id])): ?>
                                                
                                                <?php foreach ($plan_item['after_items'][$plan->id] as $custom_key => $custom_value): ?>
                                                    <li class="plan-item-value d-flex justify-content-between align-items-center border-bottom">
                                                        <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e($custom_value["name"])?></div>
                                                        <div class="h-40 me-2 pricing-feature-icon py-2"><?php _e($custom_value["data"])?></div>
                                                    </li>
                                                <?php endforeach ?>

                                            <?php endif ?>
                                        <?php endif ?>
                                        
                                    <?php endif ?>
                                <?php endforeach ?>

                            <?php endif ?>
                            <?php 
                            $check_cloud_import = true;
                            if ( !isset($permissions["file_manager_dropbox"]) && !isset($permissions["file_manager_google_drive"]) && !isset($permissions["file_manager_onedrive"]) ){
                                $check_cloud_import = false;
                            }
                            ?>

                            <li class="plan-item-value d-flex justify-content-between align-items-center border-bottom">
                                <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e( "Cloud import: " )?></div>
                                <div class="h-40 me-2 pricing-feature-icon py-2 text-gray-700 fs-20 position-relative b-6">
                                    <?php if ( isset($permissions["file_manager_dropbox"]) || isset($permissions["file_manager_google_drive"]) || isset($permissions["file_manager_onedrive"]) ): ?>
                                        <?php if (isset($permissions["file_manager_dropbox"])): ?>
                                        <span class="fab fa-dropbox me-2"></span>
                                        <?php endif ?>
                                        <?php if (isset($permissions["file_manager_google_drive"])): ?>
                                        <span class="fab fa-google-drive me-2"></span>
                                        <?php endif ?>
                                        <?php if (isset($permissions["file_manager_onedrive"])): ?>
                                        <span class="icon icon-onedrive position-relative t-6 fs-27"></span>
                                        <?php endif ?>
                                    <?php else: ?>
                                        <span class="small"><?php _e("No support")?></span>
                                    <?php endif ?>
                                </div>
                            </li>
                         
                            <li class="plan-item-value d-flex justify-content-between align-items-center border-bottom">
                                <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e("Max storage size")?></div>
                                <div class="h-40 me-2 fs-14 pricing-feature-icon py-2 fw-6 text-gray-700"><?php _e( sprintf( __("%sMB"), $permissions["max_storage_size"])  )?></div>
                            </li>

                            <li class="plan-item-value d-flex justify-content-between align-items-center border-bottom">
                                <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e("Max file size")?></div>
                                <div class="h-40 me-2 fs-14 pricing-feature-icon py-2 fw-6 text-gray-700"><?php _e( sprintf( __("%sMB"), $permissions["max_file_size"])  )?></div>
                            </li>
                        </ul>

                        <div class="px-4">
                            <a href="<?php _e( base_url("payment/index/{$plan->ids}/1" ) )?>" class="btn btn-dark btn-lg w-100  b-r-90 by_monthly" href=""><?php _e("Get started")?></a>
                            <a href="<?php _e( base_url("payment/index/{$plan->ids}/2" ) )?>" class="btn btn-dark btn-lg w-100  b-r-90 by_annually d-none" href=""><?php _e("Get started")?></a>
                        </div>
                    </div>
                </div>

                
            <?php endforeach ?>
        <?php endif ?>
    </div>
</div>

<script type="text/javascript">
$(function(){
    var head  = document.getElementsByTagName('head')[0];
    var link  = document.createElement('link');
    link.rel  = 'stylesheet';
    link.type = 'text/css';
    link.href = '<?php _ec( base_url("/../../../inc/plugins/Payment/Assets/css/pricing.css") ) ?>';
    link.media = 'all';
    head.appendChild(link);


    $(".plan-item-key").height( 57 );
    
    $(".plan-item-value").height( $(".plan-item-key").height(  ));

    var pricing_top_height = 50;
    $(".pricing-top").each(function(index, el){
        if(pricing_top_height < $(this).height()){
            pricing_top_height = $(this).height();
        }
    });

    $(".pricing-feature-comparison,.pricing-top").height( pricing_top_height );

    var plan_post_height = 50;
    $(".plan-post").each(function(index, el){
        if(plan_post_height < $(this).height()){
            plan_post_height = $(this).height();
        }
    });

    $(".plan-post").height( plan_post_height );

    $(document).on("change", ".plan_by", function(){
        if($(this).is(":checked")){
            $(".by_monthly").addClass("d-none");
            $(".by_annually").removeClass("d-none");
        }else{
            $(".by_monthly").removeClass("d-none");
            $(".by_annually").addClass("d-none");
        }
    });
});
</script>