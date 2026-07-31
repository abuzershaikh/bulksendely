<div class="section container position-relative z-2">
    <?php if (find_modules("payment")): ?>

    <div class="d-flex justify-content-center align-items-center h-100 mw-800 mx-auto text-center m-b-120 m-t-120" data-aos="fade-down">
        <div>
            <h1 class="fs-45 fw-6"><?php _e("Pricing")?></h1>
            <h5 class="text-gray-600 fw-4"><?php _e("We offer competitive rates and pricing plans to help you find one that fits the needs and budget of your business.")?></h5>
      <div class="form-check form-switch form-switch-pricing form-check-custom form-check-solid form-check-warning d-flex justify-content-center align-items-center">
                <label class="form-check-label text-gray-600 ps-0" for="plan_by">
                        <?php _e("Monthly")?>
                </label>
                <input class="form-check-input plan_by" type="checkbox" id="plan_by" value="1">
                <label class="form-check-label text-gray-600" for="plan_by">
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
                                        <li class="fs-14 fw-6 text-primary text-uppercase pt-5"><i class="fad fa-stars text-warning fs-20"></i> <?php _e( $plan_item["label"] )?></li>

                                        <?php if (!empty($plan_item['items'])): ?>

                                            <?php if ( $plan_item['permission'] ): ?>
                                               <li class="d-block text-center plan-post"></li>
                                            <?php else: ?>
                                                <?php foreach ($plan_item['items'] as $key => $value): ?>
                                                    <li class="d-flex justify-content-between align-items-center border-bottom-transparent py-2">
                                                        <div class="h-40 fw-6 py-2 text-over-all"><i class="me-2 <?php _ec($value["icon"])?> position-relative t-3" style="color: <?php _ec($value["color"])?>;"></i> <?php _e($value["name"])?></div>
                                                    </li>
                                                <?php endforeach ?>
                                            <?php endif ?>
                                            
                                        <?php endif ?>
                                    <?php endforeach ?>

                                <?php endif ?>

                                <li class="d-flex justify-content-between align-items-center border-bottom-transparent py-2">
                                    <div class="h-40 fw-6 py-2"><i class="me-2 fad fa-cloud-upload text-primary"></i> <?php _e("Cloud import")?></div>
                                </li>

                                <li class="d-flex justify-content-between align-items-center border-bottom-transparent py-2">
                                    <div class="h-40 fw-6 py-2"><i class="me-2 fad fa-box-open text-success"></i> <?php _e("Max storage size")?></div>
                                </li>

                                <li class="d-flex justify-content-between align-items-center border-bottom-transparent py-2">
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
                            <div class="pricing-head px-4 mb-3">
                                <h1 class="fs-30 text-primary"><?php _e($plan->name)?></h1>
                                <div class="fs-16 text-gray-600"><?php _e($plan->description)?></div>
                            </div>

                            <div class="pricing-price px-4">
                                <div class="d-flex align-items-center">
                                    <div class="fs-45 fw-9">
                                        <span class="by_monthly"><?php _e( get_option("payment_symbol", "$") )?><?php _e($plan->price_monthly)?></span>
                                        <span class="by_annually d-none"><?php _e( get_option("payment_symbol", "$") )?><?php _e($plan->price_annually)?></span>
                                    </div>
                                    <div class="text-gray-600 fs-16 position-relative t-3"><?php _e("/month")?></div>
                                </div>
                                <div>
                                    <?php if ($plan->plan_type == 1): ?>
                                        <div class="text-warning fw-6"><?php _e( sprintf(__("Add up to %d social accounts"), $plan->number_accounts*$total_social) )?></div>
                                        <div class="fs-12 text-gray-600"><?php _e( sprintf(__("%d accounts on each platform"), $plan->number_accounts) )?></div>                           
                                    <?php else: ?>
                                        <div class="text-warning fw-6"><?php _e( sprintf(__("%d Social Accounts"), $plan->number_accounts) )?></div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                        
                        <ul class="px-4">
                            <?php
                                $plan_items = request_service("plans");
                            ?>

                            <?php if ( !empty($plan_items) ): ?>

                                <?php foreach ($plan_items as $plan_item): ?>
                                    <li class="fs-14 fw-6 text-primary text-uppercase pt-5 pricing-feature-head"><i class="fad fa-stars text-warning fs-20"></i> <?php _e( $plan_item["label"] )?></li>

                                    <?php if (!empty($plan_item['items'])): ?>

                                        <?php if ( $plan_item['permission'] ): ?>
                                            <li class="d-block text-center plan-post">
                                                <?php foreach ($plan_item['items'] as $key => $value): ?>
                                                    <?php if ( isset( $permissions[ $value['id'] ] ) && isset( $post_social_networks[ $value['id'] ] ) ): ?>
                                                    <span class="fs-26 d-inline-block w-30 text-center">
                                                        <i class="<?php _ec( $value['icon'] )?>" style="color: <?php _ec( $value['color'] )?>;"></i>
                                                    </span>
                                                    <?php endif ?>
                                                    
                                                <?php endforeach ?>
                                            </li>
                                        <?php else: ?>
                                            <?php foreach ($plan_item['items'] as $key => $value): ?>
                                                <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                                                    <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e($value["name"])?></div>
                                                    <div class="h-40 me-2 fs-23 pricing-feature-icon py-2 <?php _ec( isset( $permissions[ $value['id'] ] )?"text-success":"" )?>"><i class="fad fa-check-circle"></i></div>
                                                </li>
                                            <?php endforeach ?>
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

                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
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
                         
                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="p-t-4 p-b-4 pricing-feature-name"><?php _e("Max storage size")?></div>
                                <div class="h-40 me-2 fs-14 pricing-feature-icon py-2 fw-6 text-gray-700"><?php _e( sprintf( __("%sMB"), $permissions["max_storage_size"])  )?></div>
                            </li>

                            <li class="d-flex justify-content-between align-items-center border-bottom py-2">
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
<?php endif ?>

<section class="sp-rating m-b-90">

    <div class="container">
        <div class="mx-auto text-center font-one mb-6 px-5">
            <div class="headline fs-40 fw-6 mb-3 mw-900 mx-auto"><?php _e("What <span class='text-warning'>our clients</span> say", 0)?></div>
            <div class="headline_desc fs-18 mw-650 mx-auto text-gray-700"><?php _e("Our clients praise us for our great results, personable service and expert knowledge. Here are what just a few of them had to say.")?></div>
        </div>

        <div class="position-relative">
            <div class="py-3 position-relative mw-1000 mx-auto">
                <div class="mw-550 mx-auto text-center position-relative mx-auto zindex-2">
                    <div class="owl-carousel rating-slider owl-theme">
                        <div class="position-relative pe-3 b-r-10 p-30 me-4">
                            <div class="text-gray-500 fs-30 zindex-1 opacity-25 r-20 t-7"><i class="fad fa-quote-left"></i></div>
                            <div class="position-relative zindex-2">
                                <div class="w-110 mb-3 text-center mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/stars.png" class="w-100 d-inline-block">
                                </div>
                                <div class="fw-6 fs-18 mb-3"><?php _e("Complete Tool for Social Media Agencies")?></div>
                                <div class="mb-3 text-gray-700">
                                    <?php _e("It's amazing to have found a tool that integrates all the necessary resources for a Social Media Marketing agency into one comprehensive and accessible platform.")?>
                                </div>
                                <div class="mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/1.jpg" class="w-120 h-120 b-r-100 me-2 mb-3 d-inline-block">
                                    <div>
                                        <div class="fw-6 text-warning fs-16"><?php _e("John Carter")?></div>
                                        <div class="text-gray-600 fs-12"><?php _e("Digital Marketing Manager")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="position-relative pe-3 b-r-10 p-30 me-4">
                            <div class="text-gray-500 fs-30 zindex-1 opacity-25 r-20 t-7"><i class="fad fa-quote-left"></i></div>
                            <div class="position-relative zindex-2">
                                <div class="w-110 mb-3 text-center mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/stars.png" class="w-100 d-inline-block">
                                </div>
                                <div class="fw-6 fs-18 mb-3"><?php _e("Efficient Marketing Management and Expanded Presence")?></div>
                                <div class="mb-3 text-gray-700">
                                    <?php _e("With this platform, I can efficiently manage my marketing efforts, reduce the time spent on tasks, and expand my presence in multiple locations effortlessly.")?>
                                </div>
                                <div class="mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/2.jpg" class="w-120 h-120 b-r-100 me-2 mb-3 d-inline-block">
                                    <div>
                                        <div class="fw-6 text-warning fs-16"><?php _e("Emma Grace")?></div>
                                        <div class="text-gray-600 fs-12"><?php _e("SEO Specialist")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="position-relative pe-3 b-r-10 p-30 me-4">
                            <div class="text-gray-500 fs-30 zindex-1 opacity-25 r-20 t-7"><i class="fad fa-quote-left"></i></div>
                            <div class="position-relative zindex-2">
                                <div class="w-110 mb-3 text-center mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/stars.png" class="w-100 d-inline-block">
                                </div>
                                <div class="fw-6 fs-18 mb-3"><?php _e("Comprehensive Analytics and Impressive Post Management")?></div>
                                <div class="mb-3 text-gray-700">
                                    <?php _e("I really appreciate this platform for its comprehensive analytics, ability to schedule in bulk, versatile scheduling options, and impressive post appearance.")?>
                                </div>
                                <div class="mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/3.jpg" class="w-120 h-120 b-r-100 me-2 mb-3 d-inline-block">
                                    <div>
                                        <div class="fw-6 text-warning fs-16"><?php _e("Liam Scott")?></div>
                                        <div class="text-gray-600 fs-12"><?php _e("Social Media Strategist")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="position-relative pe-3 b-r-10 p-30 me-4">
                            <div class="text-gray-500 fs-30 zindex-1 opacity-25 r-20 t-7"><i class="fad fa-quote-left"></i></div>
                            <div class="position-relative zindex-2">
                                <div class="w-110 mb-3 text-center mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/stars.png" class="w-100 d-inline-block">
                                </div>
                                <div class="fw-6 fs-18 mb-3"><?php _e("One Hour a Week, Zero Social Media Worries")?></div>
                                <div class="mb-3 text-gray-700">
                                    <?php _e("It’s fantastic to spend just an hour each week adding to my library and then not have to worry about social media for the rest of the week.")?>
                                </div>
                                <div class="mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/4.jpg" class="w-120 h-120 b-r-100 me-2 mb-3 d-inline-block">
                                    <div>
                                        <div class="fw-6 text-warning fs-16"><?php _e("Ava Rose")?></div>
                                        <div class="text-gray-600 fs-12"><?php _e("Social Media Analyst")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="position-relative pe-3 b-r-10 p-30 me-4">
                            <div class="text-gray-500 fs-30 zindex-1 opacity-25 r-20 t-7"><i class="fad fa-quote-left"></i></div>
                            <div class="position-relative zindex-2">
                                <div class="w-110 mb-3 text-center mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/stars.png" class="w-100 d-inline-block">
                                </div>
                                <div class="fw-6 fs-18 mb-3"><?php _e("Doubling Growth and 68% More Productivity")?></div>
                                <div class="mb-3 text-gray-700">
                                    <?php _e("Thanks to this tool, our company has grown doubled. Without it, we would have been floundering, but now we’re accomplishing 68% more work with the same team.")?>
                                </div>
                                <div class="mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/5.jpg" class="w-120 h-120 b-r-100 me-2 mb-3 d-inline-block">
                                    <div>
                                        <div class="fw-6 text-warning fs-16"><?php _e("Noah James")?></div>
                                        <div class="text-gray-600 fs-12"><?php _e("Content Manager")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="position-relative pe-3 b-r-10 p-30 me-4">
                            <div class="text-gray-500 fs-30 zindex-1 opacity-25 r-20 t-7"><i class="fad fa-quote-left"></i></div>
                            <div class="position-relative zindex-2">
                                <div class="w-110 mb-3 text-center mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/stars.png" class="w-100 d-inline-block">
                                </div>
                                <div class="fw-6 fs-18 mb-3"><?php _e("Easy Scheduling and Metrics with the team")?></div>
                                <div class="mb-3 text-gray-700">
                                    <?php _e("This incredible platform has unified our team. It offers easy post scheduling, smooth team collaboration, and centralized metric sharing with all stakeholders, all in one convenient place.")?>
                                </div>
                                <div class="mx-auto">
                                    <img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/6.jpg" class="w-120 h-120 b-r-100 me-2 mb-3 d-inline-block">
                                    <div>
                                        <div class="fw-6 text-warning fs-16"><?php _e("Olivia Claire")?></div>
                                        <div class="text-gray-600 fs-12"><?php _e("Marketing Automation Specialist")?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <ul class="position-absolute w-100 h-100 t-0 rating-slider-nav d-none d-lg-block d-md-block d-xs-none">
                    <li class="rounded-circle border-dashed border-warning p-1  w-60 h-60 position-absolute l-70 t-30 bg-active-warning active" data-index="0">
                        <a href="javascript:void(0);"><img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/1.jpg" class="rounded-circle w-100 h-100"></a>
                    </li>
                    <li class="rounded-circle border-dashed border-warning p-1  w-95 h-95 position-absolute l-0 t-220 bg-active-warning" data-index="1">
                        <a href="javascript:void(0);"><img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/2.jpg" class="rounded-circle w-100 h-100"></a>
                    </li>
                    <li class="rounded-circle border-dashed border-warning p-1  w-74 h-74 position-absolute l-70 b-30 bg-active-warning" data-index="2">
                        <a href="javascript:void(0);"><img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/3.jpg" class="rounded-circle w-100 h-100"></a>
                    </li>
                    <li class="rounded-circle border-dashed border-warning p-1  w-74 h-74 position-absolute r-70  t-30 bg-active-warning" data-index="3">
                        <a href="javascript:void(0);"><img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/4.jpg" class="rounded-circle w-100 h-100"></a>
                    </li>
                    <li class="rounded-circle border-dashed border-warning p-1  w-95 h-95 position-absolute r-0 t-220 bg-active-warning" data-index="4">
                        <a href="javascript:void(0);"><img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/5.jpg" class="rounded-circle w-100 h-100"></a>
                    </li>
                    <li class="rounded-circle border-dashed border-warning p-1  w-74 h-74 position-absolute r-70 b-40 bg-active-warning" data-index="5">
                        <a href="javascript:void(0);"><img src="<?php _ec( get_frontend_url() )?>Assets/images/ratings/6.jpg" class="rounded-circle w-100 h-100"></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</section>

<section class="bg-gray-100 p-t-150 p-b-150 position-relative overflow-hidden">
    <img src="<?php _ec( get_frontend_url() )?>Assets/images/shape-1.png" class="position-absolute ln-120 tn-200 mw-600 opacity-50">
    <img src="<?php _ec( get_frontend_url() )?>Assets/images/shape-2.png" class="position-absolute rn-120 bn-200 mw-600 opacity-25">
    <div class="mx-auto container text-center position-relative zindex-1">
        <div class="fs-40 fw-9 mb-4">
            <?php _e("Join 5,000 happy customers like you who trust us to help them manage their social media calendars.")?>
        </div>
        <div class="fs-16 text-gray-700 mb-4"><?php _e("You can get started in mere minutes.")?></div>
        <a class="btn btn-dark" href="<?php _ec( base_url("login") )?>"><?php _e("Sign up for Free")?></a>
    </div>
</section>