<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto mt-4 mb-2">
        <h2 class="mb-0 text-gray-800 mb-2"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e($config['name'])?></h2>
        <p><?php _e( $config['desc'] )?></p>
    </div>
    <div class="">
        <div>
            <form class="actionForm" action="<?php _e( get_module_url("insights") )?>" method="POST" data-result="html" data-content="insights" date-redirect="false" data-loading="false">
                <div class="daterange"></div>
            </form>
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