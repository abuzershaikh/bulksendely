<div class="row">
    <div class="col-md-12">
        
        <div class="card border b-r-10 mb-4">
            <div class="card-header">
                <div class="card-title">
                    <span class="me-2"><?php _e("Device")?></span>
                </div>
            </div>
            <div class="card-body">
                <div id="chart_device" class="h-450">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>

                <div class="card border mt-4">
                    <div class="card-body">
                        <table class="table table table-row-dashed">
                            <thead>
                                <tr class="fs-12 text-gray-600">
                                    <th scope="col" class="fw-6"><?php _e("Device")?></td>
                                    <th scope="col" class="text-end"><?php _e("Total Device")?></td>
                                    <th scope="col" class="text-end"><?php _e("Clicked")?></td>
                                    <th scope="col" class="text-end"><?php _e("Percent Clicked")?></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Desktop")?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['desktop'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['desktop_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['percent_desktop'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Mobile")?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['mobile'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['mobile_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['percent_mobile_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Tablet")?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['tablet'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['tablet_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $device['data']['percent_tablet_clicked'] )?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
    </div>

    <div class="col-md-6">
        
        <div class="card border b-r-10 mb-4">
            <div class="card-header">
                <div class="card-title">
                    <span class="me-2"><?php _e("Browsers")?></span>
                </div>
            </div>
            <div class="card-body">
                <div id="browser_chart" class="h-450">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>

                <div class="card border mt-4">
                    <div class="card-body">
                        <table class="table table table-row-dashed">
                            <thead>
                                <tr class="fs-12 text-gray-600">
                                    <th scope="col" class="fw-6"><?php _e("Browser")?></td>
                                    <th scope="col" class="text-end"><?php _e("Total")?></td>
                                    <th scope="col" class="text-end"><?php _e("Clicked")?></td>
                                    <th scope="col" class="text-end"><?php _e("Percent Clicked")?></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Chrome")?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['chrome'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['chrome_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['percent_chrome'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Firefox")?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['firefox'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['firefox_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['percent_firefox_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Safari")?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['safari'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['safari_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['percent_safari_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Opera")?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['opera'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['opera_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['percent_opera_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Microsoft Edge")?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['edge'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['edge_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $browser['data']['percent_opera_clicked'] )?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
    </div>

    <div class="col-md-6">
        
        <div class="card border b-r-10 mb-4">
            <div class="card-header">
                <div class="card-title">
                    <span class="me-2"><?php _e("OS")?></span>
                </div>
            </div>
            <div class="card-body">
                <div id="os_chart" class="h-450">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>

                <div class="card border mt-4">
                    <div class="card-body">
                        <table class="table table table-row-dashed">
                            <thead>
                                <tr class="fs-12 text-gray-600">
                                    <th scope="col" class="fw-6"><?php _e("OS")?></td>
                                    <th scope="col" class="text-end"><?php _e("Total")?></td>
                                    <th scope="col" class="text-end"><?php _e("Clicked")?></td>
                                    <th scope="col" class="text-end"><?php _e("Percent Clicked")?></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Windows")?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['windows'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['windows_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['percent_windows'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Android")?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['android'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['android_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['percent_android_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Mac")?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['mac'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['mac_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['percent_mac_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Linux")?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['linux'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['linux_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec( $os['data']['percent_linux_clicked'] )?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
    </div>
</div>

<script type="text/javascript">


    $(function(){

        <?php if (!empty($device) && isset($device['columns'])): ?>
        Core.column_chart({
            id: 'chart_device',
            legend: true,
            xvisible: true,
            yvisible: true,
            ylineColor: 'rgba(239, 242, 245, 1)',
            data: [{
                name: '<?php _e("Desktop")?>',
                data: [<?php _ec( $device['data']['desktop_clicked'] )?>],
                color: 'rgba(0, 158, 247, 1)',
            }, {
                name: '<?php _e("Mobile")?>',
                data: [<?php _ec( $device['data']['mobile_clicked'] )?>],
                color: 'rgba(80, 205, 137, 1)'
            }, {
                name: '<?php _e("Tablet")?>',
                data: [<?php _ec( $device['data']['tablet_clicked'] )?>],
                color: 'rgba(241, 65, 108, 1)'
            }],
        });
        <?php endif ?>

        Core.chart({
            id: 'browser_chart',
            categories: '',
            legend: true,
            data: [{
                type: 'pie',
                name: '<?php _e("Total")?>',
                data: [{
                    name: '<?php _e("Chrome")?>',
                    y: <?php _ec( $browser['data']['chrome_clicked'] )?>,
                    color: 'rgba(0, 158, 247, 1)',
                }, {
                    name: '<?php _e("Firefox")?>',
                    y: <?php _ec( $browser['data']['firefox_clicked'] )?>,
                    color: 'rgba(80, 205, 137, 1)',
                }, {
                    name: '<?php _e("Safari")?>',
                    y: <?php _ec( $browser['data']['safari_clicked'] )?>,
                    color: 'rgba(241, 65, 108, 1)',
                }, {
                    name: '<?php _e("Opera")?>',
                    y: <?php _ec( $browser['data']['opera_clicked'] )?>,
                    color: 'rgba(114, 57, 234, 1)',
                }, {
                    name: '<?php _e("Microsoft Edge")?>',
                    y: <?php _ec( $browser['data']['edge_clicked'] )?>,
                    color: 'rgba(255, 122, 0, 1)',
                }],
                size: 150,
                innerSize: '60%',
                showInLegend: true,
                dataLabels: {
                    enabled: false
                }
            }]
        });

        Core.chart({
            id: 'os_chart',
            categories: '',
            legend: true,
            data: [{
                type: 'pie',
                name: '<?php _e("Total")?>',
                data: [{
                    name: '<?php _e("Windows")?>',
                    y: <?php _ec( $os['data']['windows_clicked'] )?>,
                    color: 'rgba(0, 158, 247, 1)',
                }, {
                    name: '<?php _e("Android")?>',
                    y: <?php _ec( $os['data']['android_clicked'] )?>,
                    color: 'rgba(80, 205, 137, 1)',
                }, {
                    name: '<?php _e("Mac")?>',
                    y: <?php _ec( $os['data']['mac_clicked'] )?>,
                    color: 'rgba(241, 65, 108, 1)',
                }, {
                    name: '<?php _e("Linux")?>',
                    y: <?php _ec( $os['data']['linux_clicked'] )?>,
                    color: 'rgba(114, 57, 234, 1)',
                }],
                size: 150,
                innerSize: '60%',
                showInLegend: true,
                dataLabels: {
                    enabled: false
                }
            }]
        });
    });
</script>
