<div class="row widget-main">
    <div class="col-md-4 mb-4">
        <div class="bg-white border b-r-10 p-20">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-success"><?php _e("Total subscribers")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Current total subscribers")?></div>
                </div>
                <div class="fs-25 fw-6 text-success"><?php _e( $subscriber['total'] )?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="bg-white border b-r-10 p-20">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-primary"><?php _e("Subscribers (In Period)")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Total subscribers during this period")?></div>
                </div>
                <div class="fs-25 fw-6 text-primary"><?php _e( $subscriber['new'] )?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="bg-white border b-r-10 p-20">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <div class="fs-16 fw-6 text-danger"><?php _e("Unsubscribers (In Period)")?></div>
                    <div class="fs-12 text-gray-600"><?php _e("Total unsubscribers during this period")?></div>
                </div>
                <div class="fs-25 fw-6 text-danger"><?php _e( $subscriber['lost'] )?></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        
        <div class="card border b-r-10 mb-4">
            <div class="card-header">
                <div class="card-title">
                    <span class="me-2"><?php _e("Subscribers by selected date")?></span>
                </div>
            </div>
            <div class="card-body">
                <div id="subscriber_chart" class="h-450">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>
            </div>
        </div>

        <div class="card card-flush b-r-10 border">
            <div class="card-body py-0 px-0 pb-5">

                <table class="table table-border align-middle table-row-dashed fs-13 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bolder text-uppercase gs-0">
                            <th scope="col" class="border-bottom py-4 ps-4 fw-6 fs-12 text-nowrap text-dark"><?php _e("Date")?></th>
                            <th class="border-bottom py-4 pe-4 fw-6 fs-12 text-nowrap text-dark text-center"><?php _e("Subscribed")?></th>
                            <th class="border-bottom py-4 pe-4 fw-6 fs-12 text-nowrap text-dark text-center"><?php _e("UnSubscribed")?></th>
                            <th class="border-bottom py-4 pe-4 fw-6 fs-12 text-nowrap text-dark text-center"><?php _e("Net")?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subscriber['date_list'])): ?>
                            <?php foreach ($subscriber['date_list'] as $key => $value): ?>
                            <tr class="text-start text-muted fw-bolder text-uppercase gs-0">
                                <th scope="col" class="border-bottom py-2 ps-4 fw-5 fs-12 text-nowrap text-dark"><?php _ec($key)?></th>
                                <th class="border-bottom py-2 pe-4 fw-5 text-nowrap text-dark text-center"><?php _ec($value[0])?></th>
                                <th class="border-bottom py-2 pe-4 fw-5 text-nowrap text-dark text-center"><?php _ec($value[1])?></th>
                                <th class="border-bottom py-2 pe-4 fw-5 text-nowrap text-center <?php _ec( $value[0]-$value[1]>0?"text-success":( $value[0]-$value[1]<0?"text-danger":"text-dank" ) )?> "><?php _ec( (($value[0]-$value[1]>0)?"+":"").$value[0]-$value[1])?></th>
                            </tr>
                            <?php endforeach ?>
                        <?php else: ?>
                            
                        <?php endif ?>                            
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){

        Core.chart({
            id: 'subscriber_chart',
            categories: <?php _ec( $subscriber['date'] )?>,
            legend: true,
            yvisible: true,
            xvisible: true,
            crosshairs: true,
            shared: true,
            data: [{
                name: '<?php _e("Subscribed")?>',
                lineColor: 'rgba(0, 158, 247, 1)',
                fillColor: {
                    linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
                    stops: [
                        [0, 'rgba(0, 158, 247, 1)'],
                        [1, 'rgba(255,255,255,.5)']
                    ]
                },
                color: 'rgba(0, 158, 247, 1)',
                data: <?php _ec( $subscriber['subscriber_str'] )?>,
            },{
                name: '<?php _e("Unsubscribed")?>',
                lineColor: 'rgba(241, 65, 108, 1)',
                fillColor: {
                    linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
                    stops: [
                        [0, 'rgba(241, 65, 108, 1)'],
                        [1, 'rgba(255,255,255,.5)']
                    ]
                },
                color: 'rgba(241, 65, 108, 1)',
                data: <?php _ec( $subscriber['unsubscriber_str'] )?>,
            }]
        });
    });
</script>



