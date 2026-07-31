<div class="header bg-white align-items-stretch">
    <div class="container-fluid d-flex align-items-stretch justify-content-between h-100">
        <div class="d-flex justify-content-between flex-lg-grow-1">
            <div class="d-flex justify-content-between align-items-center flex-grow-1 ms-2 ms-md-0 ms-lg-0">
                <div class="d-flex align-items-stretch ms-2 ms-md-0 ms-lg-0">
                    <div class="d-flex align-items-center">
                        <div class="d-lg-none d-md-none d-sm-block d-xs-block d-block">
                            <a href="javascript:void(0);" class="btn btn-light-primary px-3 btn-open-sidebar">
                                <i class="fad fa-bars p-r-0 fs-20"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-stretch ms-2 ms-md-0 ms-lg-0">
                    <div class="d-flex align-items-center">
                        <div class="d-lg-none d-md-none d-sm-none d-none">
                            <a href="javascript:void(0);" class="btn btn-light-primary p-l-17 p-r-17 btn-open-sub-sidebar">
                                <i class="fad fa-chevron-right pe-0"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="d-none d-lg-block">
                        <div class="fw-bold text-gray-800 fs-20"><?php _e("WaGrow Workspace") ?></div>
                        <div class="text-gray-500 fs-13"><?php _e("CRM, WhatsApp automation, and campaigns in one place") ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-stretch flex-shrink-0 me-1 me-lg-3">
            <?php
                $request = \Config\Services::request();
                $topbars = $request->topbars;
            ?>

            <?php if ( !empty($topbars) ): ?>
                
                <?php foreach ($topbars as $key => $value): ?>
                    <?php _ec( $value['topbar'] )?>
                <?php endforeach ?>

            <?php endif ?>
        </div>
    </div>
</div>
