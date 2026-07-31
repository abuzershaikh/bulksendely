<form class="actionForm" action="<?php _e( get_module_url("save") )?>" method="POST" data-redirect="<?php _ec( base_url("push_integrate") )?>">
	<div class="p-25 container  mw-800">
		<div class="d-flex align-items-md-center justify-content-between pt-5 mb-5">
	        <div class="bd-search position-relative me-auto">
	            <h1><i class="fad fa-browser" style="color: <?php _ec( $config['color'] )?>;" ></i> <?php _e( "Add Website" )?></h1>
	            <span><?php _e("Configure a new domain to enable Web Push Notifications")?></span>
	        </div>
	    </div>

	    <div class="card border post-schedule wrap-caption mb-3 b-r-10">
			<div class="card-body p-30 position-relative">
				<div class="mb-4">
					<label class="form-label"><?php _e("Website")?></label>
					<div class="input-group input-group-sm sp-input-group border b-r-10">
		                <span class="input-group-text border-0 bg-gray-100 text-gray-800 ps-3 pe-2"><i class="fad fa-globe-asia fs-18 pe-2"></i> <span class="fw-4">https://</span></span>
		                <input type="text" name="website" class="form-control-solid ps-15 border-0 px-3 flex-fill b-r-10" value="" placeholder="<?php _e("yourdomain.com")?>" autocomplete="off">
		            </div>
				
				</div>
	            <div class="mb-0">
                    <div><label for="website_icon" class="form-label"><?php _e('Default Icon')?></label></div>
                    <div class="mb-4 border p-20 d-inline-block rounded b-r-10">
                        <img src="<?php _ec( get_option("push_website_icon", get_module_path( __DIR__, "../Push/Assets/img/bell_icon.png") ) )?>" class="img-thumbnail icon mw-100 mb-3  w-150 h-150 b-r-10">
                        <input type="text" name="icon" id="icon" class="form-control form-control-solid d-none" placeholder="<?php _e("Select file")?>" value="<?php _ec( get_option("push_website_icon", get_module_path( __DIR__, "../../Push/Assets/img/bell_icon.png") ) )?>">
                        <div class="input-group w-100 ">
                            <button type="button" class="btn btn-light-primary btn-sm btnOpenFileManager w-100 b-r-10" data-select-multi="0" data-type="image" data-id="icon">
                                <i class="fad fa-folder-open p-r-0 n"></i> <?php _e( "Select" )?>
                            </button>
                        </div>
                    </div>
                </div>

			</div>

			<div class="card-footer p-30 d-flex justify-content-center">
				<button type="submit" href="#" class="btn btn-primary b-r-10">
					<?php _e("Submit")?>
				</button>
			</div>
		</div>
	</div>
</form>