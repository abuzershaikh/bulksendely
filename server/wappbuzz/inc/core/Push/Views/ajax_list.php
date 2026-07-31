<?php if ( !empty($result) ){ ?>
	
	<?php foreach ($result as $key => $value): ?>

		<div class="col-md-4 col-sm-12 col-xs-4 mb-4 aipost-item" data-id="<?php _e($value->ids)?>">
		    <div class="card d-flex flex-column flex-row-auto card-custom  rounded border">
		        <div class="card-header d-block position-relative mh-260 bg-light-primary b-r-10">
		        	<div class="d-flex justify-content-end mt-4">
		        		<a href="<?php _ec( get_module_url("delete") )?>" class="actionItem text-gray-500 fs-18 text-hover-danger" data-confirm="<?php _e("After deletion, all data related to this website will be permanently deleted. Are you sure to delete this website?")?>" data-remove="aipost-item" data-id="<?php _ec( $value->ids )?>"><i class="fad fa-trash-alt"></i></a>
		        	</div>
		        	<div class="my-3 mt-5 mb-5">
		        		<div class="mb-3">
		        			<img src="<?php _ec( get_file_url( $value->icon ) )?>" class="img-thumbnail icon mb-3  b-r-15 w-70 h-70">
		        		</div>
		        		<h3 class="text-gray-900 text-over"><?php _e($value->domain)?></h3>
		        		<div class="text-gray-700 text-over fs-12"><?php _e( sprintf("Created date: %s", datetime_show($value->created) ) )?></div>
		        	</div>
		        </div>

		        <div class="card-footer row py-0 text-center p-0">
	        		<div class="col-4 py-4 text-primary">
	        			<div class="fw-7 fs-20"><?php _ec( $value->subscriber_count )?></div>
	        			<div><?php _e("Subscribers")?></div>
	        		</div>
	        		<div class="col-4 py-4 border-end border-start text-success">
	        			<div class="fw-7 fs-20"><?php _ec( $value->schedule_count )?></div>
	        			<div><?php _e("Notifications")?></div>
	        		</div>
	        		<div class="col-4 py-4 text-danger">
	        			<div class="fw-7 fs-20"><?php _ec( ($value->delivered!=0?round($value->clicked/$value->delivered*100,2)."%":"N\A") )?></div>
	        			<div><?php _e("CTR")?></div>
	        		</div>
		        </div>
		        <div class="card-footer row">
	        		<a class="btn btn-dark actionItem b-r-50" href="<?php _ec( get_module_url("go/".$value->ids) );?>" data-redirect="<?php _ec( base_url("push_dashboard") )?>" ><?php _e("Go to dashboard")?></a>
		        </div>
		    </div> 
		</div>

	<?php endforeach ?>

<?php }else{ ?>
	<div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
	    <div>
	        <div class="text-center px-4">
	            <img class="mw-100 mh-300px" alt="" src="<?php _e( get_theme_url() ) ?>Assets/img/empty.png">
	            <a href="<?php _ec( get_module_url("index/update") )?>" class="btn btn-primary btn-sm mt-4 b-r-30"><i class="fad fa-plus"></i> <?php _e("Add new")?></a>
	        </div>
	    </div>
	</div> 
<?php }?>
