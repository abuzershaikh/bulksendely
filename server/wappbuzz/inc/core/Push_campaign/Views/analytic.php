<form class="actionForm" action="<?php _e( get_module_url("insights/".$campaign->id) )?>" method="POST" data-result="html" data-content="insights" date-redirect="false" data-loading="false">
<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 py-2 text-gray-800"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( sprintf( __("Campaign: %s"), $campaign->name ) )?></h2>
        <p><?php _e($campaign->desc)?></p>
    </div>
    <div class="d-flex">
        <div class="daterange"></div>
        <a class="btn btn-sm btn-light btn-light-success ms-2 px-3" href="<?php _ec( get_module_url() )?>" title="<?php _e("All campaigns")?>" data-toggle="tooltip" data-placement="top"><i class="<?php _ec( $config['icon'] )?> px-0"></i></a>
    </div>
</div>

<div class="container my-4 insights">
    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
</div>
</form>

<script type="text/javascript">
    $(function(){
        Core.datarange();
    });
</script>

