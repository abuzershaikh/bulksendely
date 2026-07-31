<?php if (!empty($result)) { ?>

	<div class="card card-flush b-r-10">
		<div class="card-body py-0 px-0 pb-5">
			<div class="table-responsive">
				<table class="table table align-middle table-row-dashed fs-13 gy-5">
					<thead>
						<tr class="text-start text-muted fw-bolder text-uppercase gs-0">
							<th scope="col" class="w-20 border-bottom py-4 ps-4">
								<div class="form-check form-check-sm form-check-custom form-check-solid me-3">
									<input class="form-check-input checkbox-all" type="checkbox">
								</div>
							</th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Status') ?></th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e("Instance ID") ?></th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e("Phone Number") ?></th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e("Type") ?></th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap">
								<?php _e("Message") ?>
							</th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Time') ?></th>

							<th scope="col" class="border-bottom py-4 fw-4 fs-12"></th>

							
						</tr>
					</thead>
					<tbody>


						<?php foreach ($result as $key => $value) : ?>

							<tr class="item">
								<th scope="row" class="py-3 ps-4 border-bottom">
									<div class="form-check form-check-sm form-check-custom form-check-solid me-3">
										<input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->id) ?>">
									</div>
								</th>
								<td class="py-3 ps-4 border-bottom" style="max-width: 100px;">
									<?php

										switch ($value->status) {
											case 0:
												$type = '<i class="fs-18 fad fa-circle-xmark text-warning"  title="Failed"></i>';
												break;

											case 1:
												$type = '<i class="fs-18 fad fa-check-circle text-success" title="Success"></i>';
												break;

											default:
												$type = '<i class="fs-18 fad fa-circle-xmark text-primary" title="Unknown"></i>';
												break;
										}
										_ec($type);

										?>
								</td>
								<td class="border-bottom" style="width: 100px; max-width: 120px;"><?php _ec($value->instance_id) ?></td>
								<td class="border-bottom" style="width: 100px; max-width: 120px;"><?php _ec($value->phone) ?></td>
								<td class="border-bottom" style="width: 100px; max-width: 120px;"><?php _ec($value->type) ?></td>
								<td scope="row" class="border-bottom" style="min-width: 220px;max-width: 220px;"><?php _ec($value->message) ?></td>
								
								<td class="border-bottom text-center" style="width: 50px;"><?php _ec(datetime_show($value->time_post))?></td>
	
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

<?php } else { ?>
	<div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
		<div>
			<div class="text-center px-4">
				<img class="mw-100 mh-300px" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
			</div>
		</div>
	</div>
<?php } ?>