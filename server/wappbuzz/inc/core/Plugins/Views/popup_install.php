<div class="modal fade" id="InstallModal" tabindex="-1" role="dialog">
  	<div class="modal-dialog modal-dialog-centered">
  		<form class="actionForm" action="<?php _ec( get_module_url("do_install_mod") ) ?>" method="POST" data-redirect="<?php _ec( get_module_url() ) ?>">
		    <div class="modal-content">
	      		<div class="modal-header">
			        <h5 class="modal-title"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _ec("Install Mod")?></h5>
			         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		      	</div>
		      	<div class="modal-body shadow-none">
	        		<div class="mb-3">
	        			<label class="form-label"><?php _e("Mod Name")?></label>
	                    <input type="text" class="form-control form-control-solid" id="purchase_code" name="purchase_code" placeholder="<?php _e("Enter mod name")?>" value="">
	                </div>

	                <ul class="list-group">
					  	<li class="list-group-item px-3 py-3 active" aria-current="true"><?php _e("Note")?></li>
					  	<li class="list-group-item px-3 py-3"><?php _e("Just can install mods to latest versions, can't be rollback")?></li>
					  	<li class="list-group-item px-3 py-3"><?php _e("Make sure your server does not block the permissions to install")?></li>
					</ul>
		      	</div>
		      	<div class="modal-footer d-flex justify-content-end align-items-center">
		      		<button type="submit" class="btn btn-primary b-r-10"><?php _e("Submit")?></button>
		      	</div>
		    </div>
    	</form>
  	</div>
</div>