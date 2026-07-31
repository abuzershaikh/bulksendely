<?php if ( !empty($result) ): ?>
    
    <?php foreach ($result as $key => $value): ?>
        
        <tr class="item">
            <th scope="row" class="py-3 ps-4 border-bottom">
                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                    <input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e( $value->ids )?>">
                </div>
            </th>
            <td class="border-bottom">
                <div class="pb-1 fw-6"><?php _ec( $value->name )?></div>
                <div class="fs-12 text-gray-500"><?php _ec( $value->desc )?></div>

            </td>
            <td class="border-bottom">
                <?php
                    switch ($value->status) {
                        case 0:
                            $status = '<span class="badge badge-light-warning fw-4 fs-12 p-6">'.__("Inactive").'</span>';
                            break;

                        default:
                            $status = '<span class="badge badge-light-success fw-4 fs-12 p-6">'.__("Active").'</span>';
                            break;
                    }

                ?>

                <?php _ec( $status )?>
            </td>
            <td class=" text-end border-bottom text-nowrap py-4 pe-4">
                <div class="d-flex justify-content-end">
                    <a href="<?php _ec( get_module_url("analytic/".$value->ids) )?>" class="btn btn-sm btn-white border me-2 px-3 b-r-10">
                        <i class="fad fa-analytics p-0"></i>
                    </a>
                    <div class="dropdown dropdown-fixed dropdown-hide-arrow">
                        <button class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle px-3 b-r-10 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fad fa-th-list pe-0"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="<?php _ec( get_module_url("popup_add_campaign/".$value->ids) )?>" class="actionItem dropdown-item" data-popup="addPushCampaign" >
                                    <i class="fad fa-pen-square pe-2"></i> <?php _e('Edit')?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php _e( get_module_url('delete/'.$value->ids) )?>" class="actionItem dropdown-item" data-confirm="<?php _e('Are you sure to delete this items?')?>" data-remove="item" data-active="bg-light-primary">
                                    <i class="fad fa-trash-alt pe-2"></i> <?php _e("Delete")?>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </td>
        </tr>

    <?php endforeach ?>

<?php else: ?>
    <tr class="item">
        <td colspan="4" class="border-bottom-0">
            <div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
                <div class="text-center">
                    <div class="text-center px-4">
                        <img class="mw-100 mh-300px" alt="" src="<?php _e( get_theme_url() ) ?>Assets/img/empty.png">
                        <a href="<?php _ec( get_module_url("popup_add_campaign") )?>" class="btn btn-primary btn-sm mt-4 b-r-30 actionItem" title="<?php _e("Add new")?>" data-toggle="tooltip" data-placement="top" data-popup="addPushCampaign"><i class="fad fa-plus"></i> <?php _e("Add new")?></a>
                    </div>
                </div>
            </div>
        </td>
    </tr>
<?php endif ?>
