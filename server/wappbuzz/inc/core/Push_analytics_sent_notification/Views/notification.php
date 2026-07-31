<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center mt-4">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 text-gray-800 mb-1"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e("Notification details analysis")?></h2>
        <p><?php _e("Provides detailed insights into each notification individually")?></p>
    </div>
</div>

<div class="container my-5 insights">

	<div class="mb-4">
		<?php
		$data = false;
		?>
		<div class="card border item mb-4 b-r-10">

			<?php if ($result['post']->status == 1){ ?>
				<div class="ribbon ribbon-triangle ribbon-top-start border-primary rounded btl-r-10">
			        <div class="ribbon-icon mn-t-22 mn-l-22">
			            <i class="fs-20 fas fa-circle-notch fa-spin fs-2 text-white"></i>
			        </div>
			    </div>
			<?php }else if($result['post']->status == 3){ ?>
				<div class="ribbon ribbon-triangle ribbon-top-start border-success rounded btl-r-10">
			        <div class="ribbon-icon mn-t-22 mn-l-22">
			            <i class="fs-20 fad fa-check-double fs-2 text-white"></i>
			        </div>
			    </div>
			<?php } ?>
			
			<div class="card-header px-4 border-0">
				
				<div class="card-title fw-normal fs-12">
					
					<div class="d-flex flex-stack">
						<div class="symbol symbol-45px me-3">
							<img src="<?php _ec( get_file_url($result['post']->icon) )?>" class="align-self-center rounded-circle border" alt="">
						</div>
						<div class="d-flex align-items-center flex-row-fluid flex-wrap">
							<div class="flex-grow-1 me-2 text-over-all">
								<a href="<?php _ec( $result['post']->url )?>"  target="_blank" class="text-gray-800 text-hover-primary fs-14 fw-bold">
									<i class="<?php _ec( $result['post']->icon )?>"></i> <?php _e($domain->domain)?>
								</a>
								<?php if ($result['post']->type == 3): ?>
								<span class="bg-success px-2 p-t-2 p-b-2 b-r-10 position-relative t-4 d-inline-block fs-10 text-white ms-1"><?php _e("A/B Testing")?></span>
								<?php endif ?>
								<span class="text-muted fw-semibold d-block fs-12"><i class="fal fa-calendar-alt"></i> <?php _ec( datetime_show($result['post']->time_post) )?></span>
							</div>
						</div>
					</div>

				</div>
			</div>

			<div class="card-body p-20">
				
				<div class="d-flex">
					<div class="symbol symbol-100px me-3 overflow-hidden w-99 border rounded b-r-10">

						<?php if($result['post']->large_image != ""){?>
							<div class="owl-carousel owl-theme">
					    		<div class="item w-100 h-99 background-cover" style="background-image: url('<?php _ec( get_file_url($result['post']->large_image) )?>');"></div>
							</div>
						<?php }else{?>
							<div class="d-flex align-items-center justify-content-center w-99 h-99 fs-30 text-primary bg-light-primary"><i class="fal fa-align-center"></i></div>
						<?php }?>

					</div>
					<div class="d-flex flex-row-fluid flex-wrap">
						<div class="flex-grow-1 me-2">

							<span class="text-gray-600 d-block h-99 overflow-auto">
								<div class="mb-1 fw-6 text-dark"><?php _ec( nl2br($result['post']->title) )?></div>
								<?php _ec( nl2br($result['post']->message) )?>
							</span>
						</div>
					</div>
				</div>

			</div>

			<?php if ( $result['post']->status == 3 ): ?>
				<?php  $data = json_decode($result['post']->result); ?>
				<div class="card-footer bg-light-success text-success py-3 px-4 d-flex justify-content-between">
					<span class="me-2"><?php _e("Post successed")?></span> <a href="<?php _e( $data->url )?>" class="text-dark text-hover-primary" target="_blank"><i class="fad fa-eye"></i> <?php _e("View post")?></a>
				</div>
			<?php endif ?>
		</div>
	</div>

	<div class="row widget-main">
	    <div class="col-md-3 mb-4">
	        <div class="bg-white border p-20 b-r-10">
	            <div class="d-flex justify-content-between mb-2">
	                <div class="me-2">
	                    <div class="fs-16 fw-6 text-success"><?php _e("Sent")?></div>
	                    <div class="fs-12 text-gray-600"><?php _e("Notifications have been sent successfully")?></div>
	                </div>
	                <div class="fs-25 fw-6 text-success"><?php _e( $result['post']->sent )?></div>
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
	                <div class="fs-25 fw-6 text-primary"><?php _e( $result['post']->delivered )?></div>
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
	                <div class="fs-25 fw-6 text-danger"><?php _e( $result['post']->clicked )?></div>
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
	                <div class="fs-25 fw-6 text-info"><?php _e( $result['post']->delivered!=0?round($result['post']->clicked/$result['post']->delivered*100,2):0 )?><?php _e("%")?></div>
	            </div>
	        </div>
	    </div>
	</div>

    <div class="row">
	    <div class="col-md-12">
	        <div class="card border b-r-10 mb-4">
            	<div class="card-header">
	                <div class="card-title">
	                    <span class="me-2"><?php _e("Geo Location")?></span>
	                </div>
	            </div>
	            <div class="card-body">
	                <div id="chart_geo" class="h-550">
	                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
	                </div>
	                <div class="w-300 m-auto text-center text-dark fw-6"><?php _e("Clicks Map")?></div>

	                <?php if (!empty($result["countries"])): ?>
	                <div class="card border mt-4">
	                    <div class="card-body">
	                        <table class="table table table-row-dashed">
	                            <thead>
	                                <tr class="fs-12 text-gray-600">
	                                    <th scope="col" class="fw-6"><?php _e("Country")?></td>
	                                    <th scope="col" class="text-end"><?php _e("Delivered")?></td>
	                                    <th scope="col" class="text-end"><?php _e("Clicked")?></td>
	                                    <th scope="col" class="text-end"><?php _e("CTR")?></td>
	                                </tr>
	                            </thead>
	                            <tbody>
	                                
	                                    <?php foreach ($result["countries"] as $key => $value): ?>
	                                    <tr class="text-gray-500 fs-12">
	                                        <td> <i class="flag-icon flag-icon-<?php _ec( strtolower( $value->country ) )?> me-2"></i> <?php _ec($value->country_name)?></td>
	                                        <td class="text-end fw-6"><?php _ec( $value->delivered )?></td>
	                                        <td class="text-end fw-6"><?php _ec( $value->clicked )?></td>
	                                        <td class="text-end fw-6"><?php _ec( $value->ctr )?>%</td>
	                                    </tr>
	                                    <?php endforeach ?>
	                                
	                            </tbody>
	                        </table>
	                    </div>
	                </div>
	                <?php else: ?>
	                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
	                <?php endif ?>
	            </div>
	        </div>
	    </div>
	</div>

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
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['desktop'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['desktop_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['percent_desktop'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Mobile")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['mobile'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['mobile_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['percent_mobile_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Tablet")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['tablet'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['tablet_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['device_data']['percent_tablet_clicked'] )?>%</td>
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
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['chrome'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['chrome_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['percent_chrome'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Firefox")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['firefox'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['firefox_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['percent_firefox_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Safari")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['safari'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['safari_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['percent_safari_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Opera")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['opera'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['opera_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['percent_opera_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Microsoft Edge")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['edge'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['edge_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['browser_data']['percent_edge_clicked'] )?>%</td>
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
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['windows'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['windows_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['percent_windows'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Android")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['android'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['android_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['percent_android_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Mac")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['mac'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['mac_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['percent_mac_clicked'] )?>%</td>
                                </tr>
                                <tr class="text-gray-500 fs-12">
                                    <td><?php _e("Linux")?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['linux'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['linux_clicked'] )?></td>
                                    <td class="text-end fw-6"><?php _ec(  $result['os_data']['percent_linux_clicked'] )?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
    </div>
</div>

</div>

<script type="text/javascript">
    $(function(){
        Core.map_chart({
            id: 'chart_geo',
            name: '<?php _e("Clicked")?>',
            minColor: 'rgba(214, 219, 254, 1)',
            maxColor: 'rgba(92, 92, 176, 1)',
            data: <?php _ec( $result["geo_stats"] )?>,
            legend: true
            
        });

        <?php if (!empty($result) && isset($result['device_columns'])): ?>
        Core.column_chart({
            id: 'chart_device',
            legend: true,
            xvisible: true,
            yvisible: true,
            ylineColor: 'rgba(239, 242, 245, 1)',
            data: [{
                name: '<?php _e("Desktop")?>',
                data: [<?php _ec( $result['device_data']['desktop_clicked'] )?>],
                color: 'rgba(0, 158, 247, 1)',
            }, {
                name: '<?php _e("Mobile")?>',
                data: [<?php _ec( $result['device_data']['mobile_clicked'] )?>],
                color: 'rgba(80, 205, 137, 1)'
            }, {
                name: '<?php _e("Tablet")?>',
                data: [<?php _ec( $result['device_data']['tablet_clicked'] )?>],
                color: 'rgba(241, 65, 108, 1)'
            }],
        });
        <?php endif ?>

        <?php if (!empty($result) && isset($result['browser_columns'])): ?>
        Core.chart({
            id: 'browser_chart',
            categories: '',
            legend: true,
            data: [{
                type: 'pie',
                name: '<?php _e("Total")?>',
                data: [{
                    name: '<?php _e("Chrome")?>',
                    y: <?php _ec( $result['browser_data']['chrome_clicked'] )?>,
                    color: 'rgba(0, 158, 247, 1)',
                }, {
                    name: '<?php _e("Firefox")?>',
                    y: <?php _ec( $result['browser_data']['firefox_clicked'] )?>,
                    color: 'rgba(80, 205, 137, 1)',
                }, {
                    name: '<?php _e("Safari")?>',
                    y: <?php _ec( $result['browser_data']['safari_clicked'] )?>,
                    color: 'rgba(241, 65, 108, 1)',
                }, {
                    name: '<?php _e("Opera")?>',
                    y: <?php _ec( $result['browser_data']['opera_clicked'] )?>,
                    color: 'rgba(114, 57, 234, 1)',
                }, {
                    name: '<?php _e("Microsoft Edge")?>',
                    y: <?php _ec( $result['browser_data']['edge_clicked'] )?>,
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
        <?php endif ?>

        Core.chart({
            id: 'os_chart',
            categories: '',
            legend: true,
            data: [{
                type: 'pie',
                name: '<?php _e("Total")?>',
                data: [{
                    name: '<?php _e("Windows")?>',
                    y: <?php _ec( $result['os_data']['windows_clicked'] )?>,
                    color: 'rgba(0, 158, 247, 1)',
                }, {
                    name: '<?php _e("Android")?>',
                    y: <?php _ec( $result['os_data']['android_clicked'] )?>,
                    color: 'rgba(80, 205, 137, 1)',
                }, {
                    name: '<?php _e("Mac")?>',
                    y: <?php _ec( $result['os_data']['mac_clicked'] )?>,
                    color: 'rgba(241, 65, 108, 1)',
                }, {
                    name: '<?php _e("Linux")?>',
                    y: <?php _ec( $result['os_data']['linux_clicked'] )?>,
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