<div class="sub-sidebar bg-white d-flex flex-column flex-row-auto">
    <input type="hidden" name="query_id" value="<?php _e( post("query_id") )?>">
    <input type="hidden" name="schedule_time" value="<?php _e( uri('segment', 6) )?>">

    <div class="d-flex mb-10 p-20">
        <div class="d-flex align-items-center w-lg-400px">
            <form class="w-100 position-relative ">
                <div class="input-group sp-input-group">
                  <span class="input-group-text bg-light border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                  <input type="text" class="form-control form-control-solid ps-15 bg-light border-0 search-input" data-search="search-area" name="search" value="" placeholder="<?php _e("Search")?>" autocomplete="off">
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex mb-10 p-l-20 p-r-20 m-b-12">
        <h2 class="text-gray-800 fw-bold"><?php _e( $title )?></h2>
    </div>

    <div class="sp-menu n-scroll sp-menu-two menu menu-column menu-state-bg menu-rounded menu-title-gray-600 menu-icon-gray-400 menu-state-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500 p-l-20 p-r-20 m-b-12 fw-5">
        <div class="menu-item m-b-2 search-area">
            <a class="menu-link sp-menu-item schedule-type <?php _e( uri('segment', 3) == "queue"?"active":"" )?>" href="<?php _e( get_module_url( "index/queue/".uri('segment', 4)."/".uri('segment', 5)."/" ) )?>" >
                <span class="menu-icon">
                    <i class="text-primary fs-20 fas fa-circle-notch fa-spin"></i>
                </span>
                <span class="menu-title"><?php _e("Queue")?></span>
                <input type="radio" name="schedule_type" class="d-none" value="queue" <?php _e( uri('segment', 3) == "queue"?"checked":"" )?>>
            </a>
        </div>
        <div class="menu-item m-b-2 search-area">
            <a class="menu-link sp-menu-item schedule-type <?php _e( uri('segment', 3) == "published"?"active":"" )?>" href="<?php _e( get_module_url( "index/published/".uri('segment', 4)."/".uri('segment', 5)."/" ) )?>" >
                <span class="menu-icon">
                    <i class="text-primary fs-20 fad fa-check-double"></i>
                </span>
                <span class="menu-title"><?php _e("Published")?></span>
                <input type="radio" name="schedule_type" class="d-none" value="published" <?php _e( uri('segment', 3) == "published"?"checked":"" )?>>
            </a>
        </div>

        <?php if (!empty($categories)): ?>


            <div class="menu-item py-3">
                <div class="menu-content pb-2 ps-0">
                    <span class="menu-section text-muted text-uppercase fs-12 ls-1"><i class="fal fa-trash-alt pe-2"></i> <?php _e("Delete schedules")?></span>
                </div>
            </div>

            <form class="actionForm" action="<?php _ec( get_module_url("delete/multi") )?>" data-redirect="<?php _ec( current_url() )?>">
                <div class="card border">
                    <div class="card-body p-20">
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="schedule_delete_queue" value="queue" <?php _ec( uri("segment", 3) == "queue"?"checked":"" )?>>
                                <label class="form-check-label" for="schedule_delete_queue"><?php _e("Queue")?></label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="type" id="schedule_delete_published" value="published" <?php _ec( uri("segment", 3) == "published"?"checked":"" )?>>
                                <label class="form-check-label" for="schedule_delete_published"><?php _e("Published")?></label>
                            </div>
                        </div>
                        <div class="mb-0">
                            <button type="symbol" class="btn btn-light-danger w-100"><?php _e("Submit")?></button>
                        </div>
                    </div>
            </form>
            </div>
        <?php endif ?>
    </div>
</div>