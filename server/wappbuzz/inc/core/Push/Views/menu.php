<div class="mb-3 m-l-19 m-r-19">
    <select class="form-select mb-2 sb-push-domain" data-control="select2">
        <?php if (!empty($domains)): ?>
            <?php foreach ($domains as $key => $value): ?>
                <option value="<?php _ec( $value->id )?>" data-img="<?php _ec( get_file_url($value->icon) )?>" <?php _ec( $value->id == PUSH_DOMAIN_ID?"selected":"" )?> ><?php _e( $value->domain )?></option>
            <?php endforeach ?>
        <?php endif ?>
    </select>
    <!-- <a class="btn btn-dark w-100" href="<?php _ec( base_url("push_domains") )?>" ><?php _e("Manager domain")?></a> -->
</div>




<style type="text/css">
    body.sidebar-close select.sb-push-domain,
    body.sidebar-small select.sb-push-domain{
        display: none;
    }

    body.sidebar-small .sidebar-wrapper .select2,
    body.sidebar-close .sidebar-wrapper .select2 {
        width: 40px!important;
    }

    body.sidebar-small .sidebar-wrapper .select2 .form-select,
    body.sidebar-close .sidebar-wrapper .select2 .form-select{
        padding: 6px 0px;
        width: 44px !important;
        height: 44px !important;
        border-radius: 60px;
        background: none;
        overflow: hidden;
        transition: all 0.1s;
    }

    body.sidebar-small .sidebar-wrapper .select2 .form-select .select-option-text,
    body.sidebar-close .sidebar-wrapper .select2 .form-select .select-option-text{
        display: none;
    }

    body.sidebar-small .sidebar-wrapper .select2 .form-select .select-option-img,
    body.sidebar-close .sidebar-wrapper .select2 .form-select .select-option-img{
        min-height: 44px!important;
        min-width: 44px!important;
    }

    body.sidebar-small .sidebar-wrapper .select2 .form-select .select-option-img,
    body.sidebar-close .sidebar-wrapper .select2 .form-select .select2-selection__rendered{
        padding-left: 0px;
        padding-right: 0px;
        margin-left: -1px;
    }

    body.sidebar-small .select2-container,
    body.sidebar-close .select2-container{
        min-width: 200px;
    }

    body.sidebar-hover .sidebar-wrapper .select2 {
        width: 100%!important;
    }
</style>