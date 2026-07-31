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
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Name') ?></th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap">
								<i class="text-success fad fa-paper-plane"></i> <?php _e("Sent") ?>
							</th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap">
								<i class="text-primary fad fa-circle-notch fa-spin"></i> <?php _e("Pending") ?>
							</th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap">
								<i class="text-danger fad fa-exclamation-triangle"></i> <?php _e('Failed') ?>
							</th>

							<th scope="col" class="border-bottom py-4 fw-4 fs-12"></th>

							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Status') ?></th>
							<th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Next Action') ?></th>

							<th scope="col" class="border-bottom py-4 pe-4"></th>
						</tr>
					</thead>
					<tbody>


						<?php foreach ($result as $key => $value) : ?>

							<tr class="item">
								<th scope="row" class="py-3 ps-4 border-bottom">
									<div class="form-check form-check-sm form-check-custom form-check-solid me-3">
										<input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->ids) ?>">
									</div>
								</th>
								<td scope="row" class="border-bottom text-nowrap" style="max-width: 300px;">
									<div class="d-flex align-items-center w-100">

										<div class="d-flex flex-column">
											<a href="<?php _e(get_module_url("index/update/" . $value->ids)) ?>" class="text-gray-800 text-hover-primary fw-6 "><?php _ec($value->name) ?></a>

											<span class="text-gray-400 text-truncate" style="max-width: 390px;" title="<?php _e(sprintf(__("%s contacts"), number_format($value->total_phone_number))) ?>"><?php _e(sprintf(__("%s contacts"), number_format($value->total_phone_number))) ?></span>
										</div>
									</div>
								</td>
								<td class="border-bottom text-success bulk-sent-<?php _e($value->id) ?>" style="width: 100px; max-width: 120px;"><?php _ec(number_format($value->sent)) ?></td>
								<td class="border-bottom text-primary bulk-pending-<?php _e($value->id) ?>" style="width: 100px; max-width: 120px;"><?php _ec(number_format(($value->total_phone_number - $value->sent - $value->failed >= 0 && $value->status != 2) ? $value->total_phone_number - $value->sent - $value->failed : 0)) ?></td>
								<td class="border-bottom text-danger bulk-failed-<?php _e($value->id) ?>" style="width: 100px; max-width: 120px;"><?php _ec(number_format($value->failed)) ?></td>

								<td style="max-width: 80px; width: 80px;">

									<?php
									$percent = $value->total_phone_number > 0 ? ($value->sent + $value->failed) / $value->total_phone_number : 0;
									$percent = round($percent, 2) * 100;
									?>
									<div class="text-center fs-10 bulk-progress-label-<?php _e($value->id) ?>">
										<?php _e($percent) ?>%
									</div>
									<div class="progress" style="height: 5px;">
										<div class="progress-bar bulk-progress-bar-<?php _e($value->id) ?>" role="progressbar" style="width: <?php _e($percent) ?>%;" aria-valuenow="<?php _e($percent) ?>" aria-valuemin="0" aria-valuemax="100"></div>
									</div>
								</td>

								<td class="border-bottom text-center bulk-status-<?php _e($value->id) ?>" style="width: 50px;">

									<a href="<?php _ec(get_module_url("status/" . $value->ids)) ?>" title="<?php _e('Change Status') ?>" class="actionItem" data-confirm="<?php _e('Are you sure to change status of this item?') ?>" data-call-after="Core.ajax_pages();">
										<?php

										switch ($value->status) {
											case 0:
												$type = '<i class="fs-18 fad fa-pause-circle text-warning"  title="Paused"></i>';
												break;

											case 1:
												$type = '<i class="fs-18 fad fa-circle-notch fa-spin text-primary"  title="Running"></i>';
												break;

											default:
												$type = '<i class="fs-18 fad fa-check-circle text-success" title="Ended"></i>';
												break;
										}

										?>

										<?php _ec($type) ?>
									</a>

								</td>
								<td class="border-bottom text-nowrap bulk-next-<?php _e($value->id) ?>" style="max-width: 200px; width: 120px;">
									<?php _e(($value->total_phone_number - $value->sent - $value->failed >= 0 && $value->status != 2) ? datetime_show($value->time_post) : '-') ?>
								</td>

								<td class="text-end border-bottom text-nowrap py-4 pe-4" style="width: 80px;">
									<div class="btn-toolbar" role="toolbar">
										<div class="btn-group" role="group">
											<a href="<?php _e(get_module_url("restart/" . $value->ids)) ?>" title="<?php _e('Restart') ?>" class="btn btn-success px-2 actionItem" data-confirm="<?php _e('Are you sure to restart this item?') ?>">
												<i class="fad fa-fast-backward"></i>
											</a>
											<a href="<?php _e(get_module_url("index/update/" . $value->ids)) ?>" title="<?php _e('Edit') ?>" class="btn btn-warning px-2">
												<i class="fad fa-edit"></i>
											</a>
											<a href="<?php _e(get_module_url("report/" . $value->ids)) ?>" title="<?php _e('Report') ?>" class="btn btn-primary px-2">
												<i class="fad fa-chart-bar"></i>
											</a>
											<a href="<?php _e(get_module_url("delete/" . $value->ids)) ?>" title="<?php _e('Delete') ?>" class="btn btn-danger px-2 actionItem" data-confirm="<?php _e('Are you sure to delete this item?') ?>" data-call-after="Core.ajax_pages();">
												<i class="fad fa-trash"></i>
											</a>
										</div>
									</div>

								</td>
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