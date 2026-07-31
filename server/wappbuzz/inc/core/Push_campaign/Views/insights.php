<div class="row widget-main">
    <div class="col-md-3 mb-4">
        <div class="bg-white border p-20 b-r-10">
            <div class="d-flex justify-content-between mb-2">
                <div class="me-2">
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
                <div class="me-2">
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
                <div class="me-2">
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
                <div class="me-2">
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
                <div id="notification_chart" class="h-450">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){

        Core.chart({
            id: 'notification_chart',
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



