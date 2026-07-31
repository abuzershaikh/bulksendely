<div class="section blogs container m-b-250 position-relative z-2">
    
    <div class="d-flex justify-content-center align-items-center h-100 mw-1200 mx-auto text-center m-b-120 m-t-120" data-aos="fade-down">
        <div>
            <h1 class="fs-45 fw-6"><?php _ec($result->title)?></h1>
            <h5 class="text-gray-600"><?php _ec($result->desc)?></h5>
        </div>
    </div>

    <div class="mw-800 mx-auto">
        <img class="w-100 b-r-20 m-b-20 m-20" src="<?php _ec( get_file_url($result->img) )?>" class="img-fluid card-img-top" alt="<?php _ec($result->title)?>">
        <div class="card-body">
            <?php _ec($result->content)?>
        </div>
        <div class="d-flex justify-content-end text-gray-600 fs-14 mb-2">
            <div><?php _e("Blog")?></div>
            <div class="px-2">|</div>
            <div><?php _ec( date_show( $result->created ) )?></div>
        </div>
    </div>

</div>