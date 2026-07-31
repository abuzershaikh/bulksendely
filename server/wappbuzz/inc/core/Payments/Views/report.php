<div class="container  py-4">
	<div class="row">
		<div class="col-md-12">
			<h2 class="mb-4 shadow-none b-r-10 py-4 fs-19 mt-3">
		        <span class="me-2"><i class="fas fa-history me-2" style="color: <?php _ec($config['color'])?>;"></i> <?php _e("Payment report")?></span>
		    </h2>

		    <div class="row">
				<div class="col mb-3">
			        <div class="border rounded b-r-10 bg-white position-relative">
			            <div class="p-20 position-relative zIndex-2 d-flex">
			            	<div class="bg-light-success w-60 h-60 text-success m-auto d-flex align-items-center justify-content-center fs-30 b-r-10">
			                	<i class="fad fa-money-bill-alt"></i>
			            	</div>
			            	<div class="flex-grow-1 ms-3">
			            		<div class=""><span class="fs-28 fw-9 text-gray-700 me-1"><?php _ec( get_option("payment_symbol", "$") )?><?php _ec( short_number($result->total_by_day->today) )?></span></div>
			            		<div class="fs-12 fw-5 text-gray-400"><?php _e( "Total earning today" )?></div>
			            	</div>
			            </div>
			        </div>
			    </div>
			    <div class="col mb-3">
			        <div class="border rounded b-r-10 bg-white position-relative">
			        	<div class="p-20 position-relative zIndex-2 d-flex">
			            	<div class="bg-light-primary w-60 h-60 text-primary m-auto d-flex align-items-center justify-content-center fs-30 b-r-10">
			                	<i class="fad fa-money-bill-alt"></i>
			            	</div>
			            	<div class="flex-grow-1 ms-3">
			            		<div class=""><span class="fs-28 fw-9 text-gray-700 me-1"><?php _ec( get_option("payment_symbol", "$") )?><?php _ec( short_number($result->total_by_day->week) )?></span></div>
			            		<div class="fs-12 fw-5 text-gray-400"><?php _e( "Total earning of this week" )?></div>
			            	</div>
			            </div>
			        </div>
			    </div>
			    <div class="col mb-3">
			        <div class="border rounded b-r-10 bg-white position-relative">
			        	<div class="p-20 position-relative zIndex-2 d-flex">
			            	<div class="bg-light-danger w-60 h-60 text-danger m-auto d-flex align-items-center justify-content-center fs-30 b-r-10">
			                	<i class="fad fa-money-bill-alt"></i>
			            	</div>
			            	<div class="flex-grow-1 ms-3">
			            		<div class=""><span class="fs-28 fw-9 text-gray-700 me-1"><?php _ec( get_option("payment_symbol", "$") )?><?php _ec( short_number($result->total_by_day->month) )?></span></div>
			            		<div class="fs-12 fw-5 text-gray-400"><?php _e( "Total earning of this month" )?></div>
			            	</div>
			            </div>
			        </div>
			    </div>
			    <div class="col mb-3">
			        <div class="border rounded b-r-10 bg-white position-relative">
			        	<div class="p-20 position-relative zIndex-2 d-flex">
			            	<div class="bg-light-warning w-60 h-60 text-warning m-auto d-flex align-items-center justify-content-center fs-30 b-r-10">
			                	<i class="fad fa-money-bill-alt"></i>
			            	</div>
			            	<div class="flex-grow-1 ms-3">
			            		<div class=""><span class="fs-28 fw-9 text-gray-700 me-1"><?php _ec( get_option("payment_symbol", "$") )?><?php _ec( short_number($result->total_by_day->year) )?></span></div>
			            		<div class="fs-12 fw-5 text-gray-400"><?php _e( "Total earning of this year" )?></div>
			            	</div>
			            </div>
			        </div>
			    </div>
			</div>
		</div>

		<div class="col-md-6 mb-4">
			<div class="card border b-r-10 d-flex flex-column flex-row-auto card-custom card-custom-primary rounded">
		        <div class="card-header d-block position-relative mh-230 bg-dark pe-0 overflow-hidden">
		        	<div class="my-3 m-b-25 d-flex justify-content-between align-items-center">
	        			<div class="flex-grow-1 m-t-30">
	        				<h3 class="text-gray-100"><i class="fad fa-credit-card-front text-warning"></i> <?php _e("Payment history")?></h3>
		        			<div class="text-gray-100"><?php _e("Reviewing your payment history")?></div>
	        			</div>
						<img src="<?php _ec( get_module_path( __DIR__, "Assets/img/payment.png" ) )?>" class="h-100  mh-170 m-t-10">
		        	</div>
		        	
		        </div>
		        <div class="card-body">
		        	<div class="row mb-4">
		        		<div class="col-6">
		        			<div class="b-r-15 p-20 bg-white rounded border d-flex justify-content-between">
			        			<div class="bg-light-primary w-60 h-60 text-primary d-flex align-items-center justify-content-center fs-30 b-r-10 me-3">
				                	<i class="fad fa-credit-card-front text-success"></i>
				            	</div>
				            	<div class="flex-grow-1">
				            		<div class="fs-14 fw-6 text-gray-700"><?php _e("Today")?></div>
				        			<div class="fs-25 fw-6 text-gray-700"><span class="text-success"><?php _ec( short_number($result->count_by_day->today) )?></span></div>
				            	</div>
			        		</div>
		        		</div>
		        		<div class="col-6">
			        		<div class="b-r-15 p-20 bg-white rounded border d-flex justify-content-between">
			        			<div class="bg-light-primary w-60 h-60 text-primary d-flex align-items-center justify-content-center fs-30 b-r-10 me-3">
				                	<i class="fad fa-credit-card-front text-primary"></i>
				            	</div>
				            	<div class="flex-grow-1">
				            		<div class="fs-14 fw-6 text-gray-700"><?php _e("Week")?></div>
				        			<div class="fs-25 fw-6 text-gray-700"><span class="text-primary"><?php _ec( short_number($result->count_by_day->week) )?></span></div>
				        			
				            	</div>
			        		</div>
			        	</div>
		        	</div>
		        	<div class="row">
		        		<div class="col-6">
			        		<div class="b-r-15 p-20 bg-white rounded border d-flex justify-content-between">
			        			<div class="bg-light-primary w-60 h-60 text-primary d-flex align-items-center justify-content-center fs-30 b-r-10 me-3">
				                	<i class="fad fa-credit-card-front text-danger"></i>
				            	</div>
				            	<div class="flex-grow-1">
				            		<div class="fs-14 fw-6 text-gray-700"><?php _e("Month")?></div>
				        			<div class="fs-25 fw-6 text-gray-700"><span class="text-danger"><?php _ec( short_number($result->count_by_day->month) )?></span></div>
				        			
				            	</div>
			        		</div>
			        	</div>
		        		<div class="col-6">
			        		<div class="b-r-15 p-20 bg-white rounded border d-flex justify-content-between">
			        			<div class="bg-light-primary w-60 h-60 text-primary d-flex align-items-center justify-content-center fs-30 b-r-10 me-3">
				                	<i class="fad fa-credit-card-front text-warning"></i>
				            	</div>
				            	<div class="flex-grow-1">
				            		<div class="fs-14 fw-6 text-gray-700"><?php _e("Year")?></div>
				        			<div class="fs-25 fw-6 text-gray-700"><span class="text-warning"><?php _ec( short_number($result->count_by_day->year) )?></span></div>
				        			
				            	</div>
			        		</div>
			        	</div>
		        	</div>
		        </div>
		    </div>
		</div>

		<div class="col-md-6 mb-4">
			<div class="card border b-r-10">
				<div class="card-header">
					<div class="card-title"><?php _e("Recently payments")?></div>
				</div>
				<div class="card-body overflow-auto mh-430">

					<?php if (!empty( $result->recently_payments )): ?>

						<?php foreach ($result->recently_payments as $key => $value): ?>
							<div class="border b-r-10 d-flex justify-content-between align-items-center p-10 mb-3">
								<div class="flex-grow-1 d-flex align-items-center">
									<img src="<?php _ec( get_file_url($value->avatar) )?>" class="b-r-10 me-2">
									<div>
										<div class="fw-6"><?php _ec( $value->fullname )?></div>
										<div class="fs-12 text-gray-400"><?php _ec( $value->email )?></div>
										<div class="fs-12 text-primary"><?php _ec( $value->transaction_id )?></div>
									</div>
								</div>
								<div>
									<div class="bg-light-primary text-gray-800 p-10 b-r-10 fw-6"><?php _ec( get_option("payment_symbol", "$") )?><?php _ec( short_number($value->amount) )?></div>
								</div>
							</div>
						<?php endforeach ?>
						
					<?php endif ?>

					
				</div>
			</div>
		</div>

		<div class="col-md-12">
			<h4 class="mb-4 pt-4">
		        <span class="me-2"><i class="fad fa-clock fs-20 me-2" style="color: <?php _ec($config['color'])?>;"></i> <?php _e("Latest 30 days")?></span>
		    </h4>
			<div class="card border b-r-10">
				<div class="card-body">
					<div id="payment_chart"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(function(){
		Core.chart({
            id: 'payment_chart',
            categories: <?php _ec( $result->chart->date )?>,
            data: [{
                name: '<?php _e("New payments")?>',
                lineColor: 'rgba(60, 88, 208, 1)',
                fillColor: {
                    linearGradient: {x1: 0, y1: 0, x2: 0, y2: 1},
                    stops: [
                        [0, 'rgba(60, 88, 208, 1)'],
                        [1, 'rgba(255,255,255,.5)']
                    ]
                },
                color: 'rgba(60, 88, 208, 1)',
                data: <?php _ec( $result->chart->value )?>,
            }]
        });
	});
</script>