<?php if(permission("push", "", true) ): ?>
    <?php if (PLATFORM == 3): ?>
    <div class="d-flex align-items-center ms-2 ms-lg-2">
        <?php if (!empty($list_websites)): ?>
        <form class="auto-submit actionForm" id="changePushDomain" action="<?php _ec( base_url("push/go") )?>" data-redirect="<?php _ec( base_url("push_dashboard") )?>" >
            <select class="sb-push-domain auto-submit form-select form-select-solid b-r-10 h-44" name="changePushDomain" data-control="select2">
                <?php foreach ($list_websites as $key => $value): ?>
                    <option value="<?php _ec( $value->ids )?>" data-img="<?php _ec( get_file_url($value->icon) )?>" <?php _ec( $value->id == PUSH_DOMAIN_ID?"selected":"" )?> ><?php _e( $value->domain )?></option>
                <?php endforeach ?>
            </select>
        </form>
        <?php endif ?>
        <a href="<?php _e( base_url('push/index/update') )?>" class="btn btn-active-primary actionMultiItem px-3 d-lg-block d-md-block d-sm-none d-none b-r-10 border h-44 ms-3" data-result="html" data-content="main-wrapper" title="<?php _e("Add new website")?>" data-toggle="tooltip" data-placement="top" data-history="<?php _e( base_url('push/index/update') )?>">
            <i class="fal fa-plus pe-0"></i> 
            <?php if (!$list_websites): ?>
            <span class="fw-6 ps-2"><?php _e("Add website")?></span>
            <?php endif ?>
        </a>
    </div>

    <style type="text/css">
        form#changePushDomain{
            position: relative;
        }

        form#changePushDomain .select2-container{
            left: inherit!important;
            right: 0;
        }
    </style>
    <?php endif ?>

<?php endif ?>