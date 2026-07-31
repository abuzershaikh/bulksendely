<div class="modal fade" id="addAddSegmentation" tabindex="-1" role="dialog">
  	<div class="modal-dialog modal-dialog-centered">
	    <div class="modal-content">
	    	<form class="actionForm" action="<?php _ec( get_module_url("save/".uri("segment", 3)) )?>" method="POST" data-redirect="<?php _ec( get_module_url() )?>">
	      		<div class="modal-header">
			        <h5 class="modal-title"><i class="<?php _e( $config['icon'] )?>" style="color: <?php _e( $config['color'] )?>"></i> <?php _e( $config['name'] )?></h5>
			         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		      	</div>
		      	<div class="modal-body shadow-none">
	                <div class="mb-3">
			            <label for="name" class="form-label"><?php _e("Name")?></label>
			            <input type="text" class="form-control" id="name" name="name" value="<?php _ec( get_data($result, "name") )?>">
			        </div>
		      	</div>
		      	<div class="modal-footer d-flex justify-content-between py-3">
			    	<button type="submit" class="btn btn-primary" data-bs-dismiss="modal"><?php _e("Save")?></button>
		      	</div>
	      	</form>
	    </div>
  	</div>
</div>