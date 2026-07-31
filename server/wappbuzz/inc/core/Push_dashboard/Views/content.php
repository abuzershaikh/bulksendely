<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center mt-4 mb-3">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 text-gray-800 mb-2"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e($config['name'])?></h2>
        <p><?php _e( $config['desc'] )?></p>
    </div>
</div>


<div class="container pt-4">

    <div class="row">
        <?php if (!empty( $block_push_dashboard )): ?>
                
            <?php foreach ($block_push_dashboard as $key => $value): ?>

                <?php if ( isset($value['data']['html']) ): ?>
                    <?php _ec( $value['data']['html'] )?>
                <?php endif ?>
                
            <?php endforeach ?>

        <?php endif ?>
        
        <div class="col-md-4 mb-4">
            <div class="bg-success border p-20 b-r-10">
                <div class="d-flex justify-content-between mb-2">
                    <div class="me-2">
                        <div class="fs-25 fw-6 text-white"><?php _e( $info['subscibers'] )?></div>
                        <div class="fs-16 fw-6 text-white"><?php _e("Total subscribers")?></div>
                        <div class="fs-12 text-white"><?php _e("Current total subscribers")?></div>
                    </div>
                    <div class="fs-25 fw-6 text-white">
                        <i class="fad fa-users-crown pe-0 fs-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="bg-primary border p-20 b-r-10">
                <div class="d-flex justify-content-between mb-2">
                    <div class="me-2">
                        <div class="fs-25 fw-6 text-white"><?php _e( $info['notifications']['count'] )?></div>
                        <div class="fs-16 fw-6 text-white"><?php _e("Sent notifications")?></div>
                        <div class="fs-12 text-white"><?php _e("Total notifications have reached the subscribers")?></div>
                    </div>
                    <div class="fs-25 fw-6 text-white">
                        <i class="fad fa-tasks pe-0 fs-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="bg-warning border p-20 b-r-10">
                <div class="d-flex justify-content-between mb-2">
                    <div class="me-2">
                        <div class="fs-25 fw-6 text-white"><?php _e( $info['notifications']['delivered']!=0?round($info['notifications']['clicked']/$info['notifications']['delivered']*100,2):0 )?><?php _e("%")?></div>
                        <div class="fs-16 fw-6 text-white"><?php _e("CTR")?></div>
                        <div class="fs-12 text-white"><?php _e("Total notifications have been interacted")?></div>
                    </div>
                    <div class="fs-25 fw-6 text-white">
                        <i class="fad fa-mouse-pointer pe-0 fs-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
        <div class="bd-search position-relative me-auto mt-4 mb-2">
            <h4 class="mb-0 text-gray-800 mb-2"> <?php _e("Notifications")?></h4>
            <p><?php _e("Understand how effectively your notifications reach and engage your audience")?></p>
        </div>
        <div class="">
            <div>
                <form class="actionForm" action="<?php _e( get_module_url("insights") )?>" method="POST" data-result="html" data-content="insights" date-redirect="false" data-loading="false">
                    <div class="daterange"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 insights">
    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
</div>

<script type="text/javascript">
    $(function(){
        Core.datarange();
    });
</script>