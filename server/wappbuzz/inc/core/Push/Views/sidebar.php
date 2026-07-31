<?php if( uri('segment', 1) != "push" && PUSH_DOMAIN_ID ): ?>
    <?php if ( !empty($modules) ): ?>
        
        <?php $count = 0; ?>

        <?php foreach ($modules as $id => $module): ?>

            <?php if (!empty($module)): ?>

                <?php
                    $have_permission = false;
                ?>

                <?php 
                foreach ($module as $key => $value) {
                    if ( permission($value['config']['id']) ) {
                        $have_permission = true;
                    }
                }
                ?>

                <?php if ($have_permission): ?>
                <div class="menu-item menu-item-desc">
                    <div class="menu-content pb-2 p-b-10">
                        <span class="menu-section text-muted text-uppercase fs-12 ls-1">
                            <?php _e( $module[0]['config']["parent"]["name"] )?>
                        </span>
                    </div>
                </div>
                <?php endif ?>

                <?php foreach ($module as $key => $value): ?>

                    <?php if ( permission($value['config']['id']) ): ?>
                    <li class="nav-item mb-2">
                        <a href="<?php _e( base_url($value['config']['id']) )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 1) == $value['config']['id']?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.$value['config']['name'].'" data-toggle="tooltip" data-placement="right"':'' )?> >
                            <i class="<?php _e( $value['config']['icon'] )?> fs-20"  style="<?php _e( ( $value['config']['color'] )?"color: ".$value['config']['color']:"" )?>" ></i>
                            <span class="text-gray-600 fw-5"><?php _e( $value['config']['name'] )?></span>
                        </a>
                    </li>
                    <?php endif ?>

                <?php endforeach ?>

            <?php endif ?>


        <?php endforeach ?>

    <?php endif ?>

<?php else: ?>

    <li class="nav-item mb-2">
        <a href="<?php _e( base_url("push") )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 1) == "push"?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.__("Manage website").'" data-toggle="tooltip" data-placement="right"':'' )?> >
            <i class="fad fa-bell-on text-primary fs-20"  style="<?php _ec( get_option("sidebar_icon_color")?get_option("site_icon_color"):"" )?>" ></i>
            <span class="text-gray-600 fw-5"><?php _e("Manage website")?></span>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a href="<?php _e( base_url("profile/index/account") )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 3) == "account"?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.__("Manage website").'" data-toggle="tooltip" data-placement="right"':'' )?> >
            <i class="fad fa-user text-success fs-20"  style="<?php _ec( get_option("sidebar_icon_color")?get_option("site_icon_color"):"" )?>" ></i>
            <span class="text-gray-600 fw-5"><?php _e("Profile")?></span>
        </a>
    </li>


    <li class="nav-item mb-2">
        <a href="<?php _e( base_url("profile/index/plan") )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 3) == "plan"?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.__("Manage website").'" data-toggle="tooltip" data-placement="right"':'' )?> >
            <i class="fad fa-box-open text-danger fs-20"  style="<?php _ec( get_option("sidebar_icon_color")?get_option("site_icon_color"):"" )?>" ></i>
            <span class="text-gray-600 fw-5"><?php _e("Plan")?></span>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a href="<?php _e( base_url("profile/index/billing") )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 3) == "billing"?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.__("Manage website").'" data-toggle="tooltip" data-placement="right"':'' )?> >
            <i class="fad fa-credit-card text-warning fs-20"  style="<?php _ec( get_option("sidebar_icon_color")?get_option("site_icon_color"):"" )?>" ></i>
            <span class="text-gray-600 fw-5"><?php _e("Billing")?></span>
        </a>
    </li>

    <li class="nav-item mb-2">
        <a href="<?php _e( base_url("profile/index/settings") )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 3) == "settings"?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.__("Manage website").'" data-toggle="tooltip" data-placement="right"':'' )?> >
            <i class="fad fa-cog text-info fs-20"  style="<?php _ec( get_option("sidebar_icon_color")?get_option("site_icon_color"):"" )?>" ></i>
            <span class="text-gray-600 fw-5"><?php _e("Settings")?></span>
        </a>
    </li>
    <li class="nav-item mb-2">
        <a href="<?php _e( base_url("profile/index/change_password") )?>" class="nav-link d-flex p-t-12 p-b-12 <?php _e( uri('segment', 3) == "change_password"?'active text-primary bg-light':'hoverable' )?>" <?php _ec( ( get_option("sidebar_type", "sidebar-small") == "sidebar-close"  )?'title="'.__("Manage website").'" data-toggle="tooltip" data-placement="right"':'' )?> >
            <i class="fad fa-key text-dark fs-20"  style="<?php _ec( get_option("sidebar_icon_color")?get_option("site_icon_color"):"" )?>" ></i>
            <span class="text-gray-600 fw-5"><?php _e("Change password")?></span>
        </a>
    </li>

<?php endif ?>
