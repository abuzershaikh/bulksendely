<div class="row widget-main">
    <div class="col-md-3 mb-4">
        <div class="bg-white border p-20 b-r-10">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-success"><?php _e("Sent")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Notifications have been sent successfully")?></div>
                </div>
                <div class="fs-25 fw-6 text-success"><?php _e( $chart['sent'] )?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="bg-white border p-20 b-r-10">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-primary"><?php _e("Delivered")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Notifications have reached the subscribers")?></div>
                </div>
                <div class="fs-25 fw-6 text-primary"><?php _e( $chart['delivered'] )?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="bg-white border p-20 b-r-10">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-danger"><?php _e("Clicked")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Notifications have been interacted with by clicking")?></div>
                </div>
                <div class="fs-25 fw-6 text-danger"><?php _e( $chart['clicked'] )?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="bg-white border p-20 b-r-10">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-info"><?php _e("CTR")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Ratio subscribers clicked on delivered notifications")?></div>
                </div>
                <div class="fs-25 fw-6 text-info"><?php _e( $chart['delivered']!=0?round($chart['clicked']/$chart['delivered']*100,2):0 )?><?php _e("%")?></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        
        <div class="card border mb-4 b-r-10">
            <div class="card-body mt-4">
                <div id="user_register_chart" class="h-450">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center mb-4">
    <div class="bd-search position-relative me-auto">
    </div>
    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search")?>" autocomplete="off">
            </div>
        </div>
    </div>
</div>

<div class="card card-flush b-r-10 border">
    <div class="card-body py-0 px-0 pb-5">

        <?php if ( isset($datatable) ): ?>

            <div class="<?php _e( get_data($datatable, "responsive")? "table-responsive":"" )?>">

                <?php if ( is_array( get_data($datatable, "columns") ) ): ?>

                    <table 
                        class="ajax-pages table table-border align-middle table-row-dashed fs-13 gy-5" 
                        data-url="<?php _ec( get_module_url("ajax_list?daterange=".post("daterange")) )?>" 
                        data-response=".ajax-result" 
                        data-per-page="<?php _ec( get_data($datatable, "per_page") )?>"
                        data-current-page="<?php _ec( get_data($datatable, "current_page") )?>"
                        data-total-items="<?php _ec( get_data($datatable, "total_items") )?>"
                    >
                        <thead>
                            <tr class="text-start text-muted fw-bolder text-uppercase gs-0">

                                <?php foreach ( get_data($datatable, "columns") as $key => $value ): ?>

                                    <?php if ( $key == "id" ): ?>
                                    <td class="w-50 border-bottom py-4 ps-4 fw-6 fs-12 text-nowrap text-center text-dark"><?php _e("#")?></td>
                                    <?php else: ?>
                                    <td class="border-bottom py-4 fw-6 fs-12 text-nowrap text-dark <?php _ec( $key != "title"?"text-center":"" )?>" ><?php _e( $value )?></td>
                                    <?php endif ?>
                                <?php endforeach ?>
                                <td class="border-bottom py-4 pe-4 fw-6 fs-12 text-nowrap text-dark text-center"><?php _e("CTR")?></td>
                            </tr>
                        </thead>
                        <tbody class="ajax-result"></tbody>
                    </table>

                <?php endif ?>

            </div>
            
        <?php endif ?>

       <?php if (get_data($datatable, "total_items") != 0): ?>
        <nav class="m-t-50 ajax-pagination m-auto text-center mb-4"> </nav>
        <?php endif ?>

    </div>
</div>

<script type="text/javascript">
    $(function(){
        Core.ajax_pages();
    });
</script>

<script type="text/javascript">
    $(function(){

        Core.chart({
            id: 'user_register_chart',
            categories: <?php _ec( $chart['date'] )?>,
            legend: true,
            yvisible: true,
            xvisible: true,
            crosshairs: true,
            shared: true,
            data: [{
                name: '<?php _e("Sent")?>',
                lineColor: 'rgba(80, 205, 137, 1)',
                fillColor: {
                    linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
                    stops: [
                        [0, 'rgba(80, 205, 137, 1)'],
                        [1, 'rgba(255,255,255,.5)']
                    ]
                },
                color: 'rgba(80, 205, 137, 1)',
                data: <?php _ec( $chart['sent_str'] )?>,
            },
            {
                name: '<?php _e("Delivered")?>',
                lineColor: 'rgba(0, 158, 247, 1)',
                fillColor: {
                    linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
                    stops: [
                        [0, 'rgba(0, 158, 247, 1)'],
                        [1, 'rgba(255,255,255,.5)']
                    ]
                },
                color: 'rgba(0, 158, 247, 1)',
                data: <?php _ec( $chart['delivered_str'] )?>,
            },
            {
                name: '<?php _e("Clicked")?>',
                lineColor: 'rgba(241, 65, 108, 1)',
                fillColor: {
                    linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
                    stops: [
                        [0, 'rgba(241, 65, 108, 1)'],
                        [1, 'rgba(255,255,255,.5)']
                    ]
                },
                color: 'rgba(241, 65, 108, 1)',
                data: <?php _ec( $chart['clicked_str'] )?>,
            }]
        });

        Core.datarange();
    });
</script>



