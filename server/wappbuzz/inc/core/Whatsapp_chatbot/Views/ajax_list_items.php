<?php if (!empty($result)) { ?>

    <div class="card card-flush b-r-10">
        <div class="card-body py-0 px-0 pb-5">
            <!-- <i class="fad fa-user-robot fs-90 position-absolute text-success opacity-10 r-30"></i> -->
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
                            <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Keywords') ?></th>
                            <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Next Bot') ?></th>
                            <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Sent') ?></th>
                            <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap text-center"><?php _e('Status') ?></th>
                            <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap text-center"><?php _e('Type') ?></th>
                            <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap text-center"><?php _e('Send to') ?></th>
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
                                <td scope="row" class="border-bottom" style="max-width: 400px;">
                                    <div class="d-flex align-items-center w-100">

                                        <div class="d-flex flex-column">
                                            <a href="<?php _e(get_module_url("index/update/" . get_data($account, "ids") . "/" . $value->ids)) ?>" class="text-gray-800 text-hover-primary fw-6 "><?php _ec($value->name) ?></a>

                                            <span class="text-gray-400 text-truncate" style="max-width: 390px;" title="<?php _ec($value->description) ?>"><?php _ec($value->description) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="border-bottom text-truncate" style="width: 300px; max-width: 300px;" title="<?php _ec($value->keywords) ?>"><?php _ec($value->keywords) ?></td>
                                <td class="border-bottom text-truncate" style="width: 200px; max-width: 200px;" title="<?php _ec($value->nextBot) ?>"><?php _ec($value->nextBot) ?></td>
                                <td class="border-bottom" style="width: 100px;"><?php _ec($value->sent) ?></td>
                                <!-- <td class="border-bottom text-truncate" style="width: 250px; max-width: 250px;"><?php echo json_encode($value) ?> </td> -->
                                <td class="border-bottom py-3 text-center" style="width: 50px;">
                                    <i class="fs-18 <?php _ec($value->status || $value->is_default ? "fad fa-check-circle " . ($value->is_default ? 'text-warning' : 'text-success') : "fad fa-times-circle text-dark") ?>"></i>
                                </td>

                                <td class="border-bottom text-center" style="width: 80px;">
                                    <?php
                                    switch ($value->type) {
                                        case 2:
                                            $type = '<i class="fs-18 fad fa-square text-primary"  title="Button"></i>';
                                            break;

                                        case 3:
                                            $type = '<i class="fs-18 fad fa-list-ul text-primary"  title="List"></i>';
                                            break;

                                        default:
                                            if ($value->use_ai) {
                                                $type = '<i class="fs-18 fad fa-font text-dark" title="Text AI"></i><i class="fs-18 fad fa-brain text-dark" title="Text AI"></i>';
                                            } else {
                                                $type = '<i class="fs-18 fad fa-font text-dark" title="Text"></i>';
                                            }

                                            break;
                                    }

                                    if (isset($value->save_data) && $value->save_data == '2') {
                                        $type .= '<i class="fs-18 fad fa-save text-warning" title="' . $value->inputname . '"></i>';
                                    }

                                    ?>

                                    <?php _ec($type) ?>
                                </td>

                                <td class="border-bottom text-center" style="width: 100px;">
                                    <?php
                                    switch ($value->send_to) {
                                        case 1:
                                            $send_to = '<i class="fs-18 fad fa-globe text-success"  title="' . __("All") . '"></i>';
                                            break;

                                        case 2:
                                            $send_to = '<i class="fs-18 fad fa-user text-primary"  title="' . __("Individual") . '"></i>';
                                            break;

                                        default:
                                            $send_to = '<i class="fs-18 fad fa-users text-warning"  title="' . __("Group") . '"></i>';
                                            break;
                                    }

                                    ?>

                                    <?php _ec($send_to) ?>
                                </td>



                                <td class="text-end border-bottom text-nowrap py-4 pe-4" style="width: 80px;">
                                    <div class="dropdown dropdown-fixed dropdown-hide-arrow">
                                        <button class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fad fa-th-list pe-0"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a href="<?php _e(get_module_url("index/update/" . get_data($account, "ids") . "/" . $value->ids)) ?>" class="dropdown-item">
                                                    <i class="fad fa-pen-square pe-2"></i> <?php _e('Edit') ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a href="<?php _e(get_module_url("delete/" . $value->ids)) ?>" data-id="<?php _ec($value->ids) ?>" data-call-success="Core.ajax_pages();" class="actionItem dropdown-item" data-confirm="<?php _e('Are you sure to delete this items?') ?>" data-remove="item" data-active="bg-light-primary">
                                                    <i class="fad fa-trash-alt pe-2"></i> <?php _e("Delete") ?>
                                                </a>
                                            </li>
                                        </ul>
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