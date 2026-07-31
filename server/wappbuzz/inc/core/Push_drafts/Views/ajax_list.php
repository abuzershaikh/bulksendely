<?php if ( !empty($result) ): ?>

    <?php foreach ($result as $key => $value): ?>

        <?php
        $data = false;
        ?>
        <div class="card border item mb-4 b-r-10">

            <?php if ($value->status == 1){ ?>
                <div class="ribbon ribbon-triangle ribbon-top-start border-primary rounded btl-r-10">
                    <div class="ribbon-icon mn-t-22 mn-l-22">
                        <i class="fs-20 fas fa-circle-notch fa-spin fs-2 text-white"></i>
                    </div>
                </div>
            <?php }else if($value->status == 3){ ?>
                <div class="ribbon ribbon-triangle ribbon-top-start border-success rounded btl-r-10">
                    <div class="ribbon-icon mn-t-22 mn-l-22">
                        <i class="fs-20 fad fa-check-double fs-2 text-white"></i>
                    </div>
                </div>
            <?php } ?>
            
            <div class="card-header px-3 border-0">
                
                <div class="card-title fw-normal fs-12">
                    
                    <div class="d-flex flex-stack">
                        <div class="symbol symbol-45px me-3">
                            <img src="<?php _ec( get_file_url($value->post_icon) )?>" class="align-self-center rounded-circle border" alt="">
                        </div>
                        <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                            <div class="flex-grow-1 me-2 text-over-all">
                                <a href="<?php _ec( $value->url )?>"  target="_blank" class="text-gray-800 text-hover-primary fs-14 fw-bold">
                                    <i class="<?php _ec( $value->icon )?>" style="color: <?php _ec( $value->color )?>;"></i> <?php _ec( $value->domain )?>
                                </a>
                                <?php if ($value->type == 3): ?>
                                <span class="bg-success px-2 p-t-2 p-b-2 b-r-10 position-relative t-4 d-inline-block fs-10 text-white ms-1"><?php _e("A/B Testing")?></span>
                                <?php endif ?>
                                <span class="text-muted fw-semibold d-block fs-12"><i class="fal fa-calendar-alt"></i> <?php _ec( datetime_show($value->created) )?></span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-toolbar">
                    <?php if ($value->type == 3): ?>
                    <a href="<?php _ec( base_url("push_ab_testing/index/".$value->pid) )?>" class="btn btn-sm p-l-11 p-r-11 b-r-60 btn-dark me-2">
                        <i class="fad fa-pencil-alt fs-14 pe-0"></i>
                    </a>    
                    <?php else: ?>
                    <a href="<?php _ec( base_url("push_composer/index/".$value->ids) )?>" class="btn btn-sm p-l-11 p-r-11 b-r-60 btn-dark me-2">
                        <i class="fad fa-pencil-alt fs-14 pe-0"></i>
                    </a>
                    <?php endif ?>
                    
                    <a href="<?php _ec( base_url("push_schedules/delete") )?>" class="btn btn-sm p-l-11 p-r-11 b-r-60 btn-danger actionItem" data-remove="item" data-id="<?php _ec($value->ids)?>" data-confirm="<?php _e("Are you sure to delete this items?")?>">
                        <i class="fal fa-trash-alt fs-14 pe-0"></i>
                    </a>
                </div>

            </div>

            <div class="card-body p-20">
                
                <div class="d-flex">
                    <div class="symbol symbol-100px me-3 overflow-hidden w-99 border rounded b-r-10">

                        <?php if($value->large_image != ""){?>
                            <div class="item w-100 h-99" style="background-image: url('<?php _ec( get_file_url($value->large_image) )?>');"></div>
                        <?php }else{?>
                            <div class="d-flex align-items-center justify-content-center w-99 h-99 fs-30 text-primary bg-light-primary"><i class="fal fa-align-center"></i></div>
                        <?php }?>

                    </div>
                    <div class="d-flex flex-row-fluid flex-wrap">
                        <div class="flex-grow-1 me-2">

                            <span class="text-gray-600 d-block h-99 overflow-auto">
                                <div class="mb-1 fw-6 text-dark"><?php _ec( nl2br($value->title) )?></div>
                                <?php _ec( nl2br($value->message) )?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <?php if ( $value->status == 3 ): ?>

                <?php  $data = json_decode($value->result); ?>

                <div class="card-footer bg-light-success text-success py-3 px-4 d-flex justify-content-between">
                    <span class="me-2"><?php _e("Post successed")?></span> <a href="<?php _e( $data->url )?>" class="text-dark text-hover-primary" target="_blank"><i class="fad fa-eye"></i> <?php _e("View post")?></a>
                </div>
            <?php endif ?>
        </div>

    <?php endforeach ?>

<?php else: ?>
    
    <div class="mw-400 m-auto d-flex align-items-center align-self-center h-100 stretch py-5">
        <div>
            <div class="text-center px-4">
                <img class="mw-100 mh-200 mb-4" alt="" src="<?php _e( get_theme_url() ) ?>Assets/img/empty.png">
                <div>
                    <a class="btn btn-secondary btn-sm b-r-30 mb-3 open-schedule-calendar d-lg-none d-md-none d-sm-block d-xs-block d-block" href="javascript:void(0);" >
                        <i class="fad fa-chevron-left"></i> <?php _ec("Back")?>
                    </a>
                    <a class="btn btn-primary btn-sm b-r-30 mb-3 d-block" href="<?php _e( base_url('push_composer') )?>" >
                        <i class="fad fa-plus"></i> <?php _e("Create notification")?>
                    </a>

                </div>
            </div>
        </div>
    </div>

<?php endif ?>
</div>