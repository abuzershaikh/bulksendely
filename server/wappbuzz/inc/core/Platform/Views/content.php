<div class="mw-500 my-5 mx-auto px-4">
	<div>
		<div class="mb-5">
			<div class="fs-20 fw-9 mb-2">Platform Manage</div>
			<div>Active, inactive and set default all your platforms</div>
		</div>

		<div>
			<?php if (!empty($result)): ?>
				
				<?php foreach ($result as $key => $value): ?>
				<div class="d-flex align-items-center bg-white shadow p-15 b-r-10 justify-content-between mb-4">
					<div class="d-flex align-items-center">
						<div class="w-45 h-45 d-flex justify-content-center align-items-center bg-gray-100 border b-r-10 me-3 fs-20"><i class="<?php _e($value->icon)?>" style="color: <?php _e($value->color)?>"></i></div>
						<div class=""><?php _e($value->name)?></div>
					</div>
					<div class="d-flex align-items-center">
						<?php if ($value->is_default == 0): ?>
							<a class="btn btn-sm btn-light-success px-0 w-45 h-45 d-flex align-items-center justify-content-center fs-10 b-r-10 actionItem" title="<?php _e("Set platform is default")?>" data-toggle="tooltip" data-redirect="" href="<?php _ec( get_module_url("set_default") )?>" data-id="<?php _e($value->id)?>"><i class="fal fa-check pe-0 fs-14"></i></a>
						<?php else: ?>
							<div class="text-success"><?php _e("Is default")?></div>
						<?php endif ?>

						<?php if ($value->is_default == 0): ?>
						<a class="btn btn-sm px-0 w-45 h-45 b-r-10 actionItem d-flex align-items-center justify-content-center ms-2 <?php _e( $value->status==0?"btn-light-danger":"btn-light-primary" )?>" title="<?php _e("Enable/Disable")?>" data-toggle="tooltip" data-redirect="" href="<?php _ec( get_module_url("status/".($value->status==0?1:0)) )?>" data-id="<?php _e($value->id)?>">
							<?php if ($value->status == 1): ?>
								<i class="fal fa-eye pe-0 fs-14"></i>
							<?php else: ?>
								<i class="fal fa-eye-slash pe-0 fs-14"></i>
							<?php endif ?>
						</a>
						<?php endif ?>
					</div>
				</div>
				<?php endforeach ?>

			<?php endif ?>
		</div>
	</div>
</div>