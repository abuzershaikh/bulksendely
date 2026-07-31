<div class="container py-5">
    <div class="card b-r-6 h-100">

        <div class="card-header">
            <div class="row w-100">
                <div class="col-4 col-md-2 d-flex justify-content-center align-items-center">
                    <img src="<?php _ec($item->img) ?>" class="" style="max-width: 100%" alt="...">
                </div>
                <div class="col-12 col-md-10 d-flex flex-column justify-content-center align-items-center">
                    <h5 class="card-title"><?php _ec($item->title) ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted"><?php _ec($item->desc) ?></h6>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php _ec($item->content) ?>            
        </div>

    </div>
</div>