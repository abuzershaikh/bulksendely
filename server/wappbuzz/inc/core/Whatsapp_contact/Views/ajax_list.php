<?php if (!empty($result)) { ?>


    <?php foreach ($result as $key => $value) : ?>
        <tr class="item">
            <th scope="row" class="py-3 ps-4 border-bottom">
                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                    <input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->ids) ?>">
                </div>
            </th>
            <td scope="row" class="border-bottom">
                <div class="d-flex align-items-center">

                    <div class="d-flex flex-column">
                        <a href="<?php _e(get_module_url("index/update/" . $value->ids)) ?>" class="text-gray-800 text-hover-primary fw-6 "><?php _ec($value->name) ?></a>

                        <!-- <span class="text-gray-400 text-truncate" title=""></span> -->
                    </div>
                </div>
            </td>
            <td class="border-bottom contact-group-count-<?php _e($value->id) ?>"><?php _ec(number_format($value->count ?? 0)) ?></td>
            <td class="border-bottom contact-group-valid-<?php _e($value->id) ?>"><?php _ec(number_format($value->count_valid ?? 0)) ?></td>
            
            <!--DELETE-->
            <td class="border-bottom contact-group-invalid-<?php _e($value->id) ?>"><?php _ec(number_format($value->count_invalid ?? 0)) ?><a href="<?php _e(get_module_url("delete_invalid/" . $value->ids)) ?>" title="<?php _e('Delete') ?>" class="btn px-2 actionItem" data-confirm="<?php _e('Are you sure to delete this item?') ?>" data-call-after="Core.ajax_pages();">
                            <i class="fad fa-trash text-danger"></i>
                        </a></td><!--end delete-->
            <td class="border-bottom contact-group-process-<?php _e($value->id) ?>"><?php _ec(number_format($value->count_process ?? 0)) ?></td>
            <td class="border-bottom contact-group-duplicate-<?php _e($value->id) ?>"><?php _ec(number_format($value->count - $value->count_non_repeated ?? 0)) ?><a href="<?php _e(get_module_url("delete_duplicate/" . $value->ids)) ?>" title="<?php _e('Delete') ?>" class="btn px-2 actionItem" data-confirm="<?php _e('Are you sure to delete this item?') ?>" data-call-after="Core.ajax_pages();">
                            <i class="fad fa-trash text-danger"></i>
                        </a></td>  
            <td class="border-bottom py-3 text-center" style="width: 50px;"><i class="fs-18 <?php _ec($value->status ? "fad fa-check-circle text-primary" : "fad fa-times-circle text-dark") ?>"></i></td>

            <td class="text-end border-bottom text-nowrap py-4 pe-4" style="width: 80px;">
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group" role="group">
                        <a href="<?php _e( get_module_url("exportar_csv/" . $value->ids) )?>" class="btn btn-success btn-block w-100">
	                                <i class="fas fa-download"></i> <?php _e("Export")?>
	                            </a>
                        <a href="<?php _e(get_module_url("index/update/" . $value->ids)) ?>" title="<?php _e('Edit') ?>" class="btn btn-info">
                            <i class="fad fa-edit"></i>
                        </a>
                        <a href="<?php _e(get_module_url("index/phone_numbers/" . $value->ids)) ?>" title="<?php _e('Lista de números') ?>" class="btn btn-warning">
                            <i class="fad fa-list-alt"></i>
                        </a>
                        <a href="<?php _e(get_module_url("delete/" . $value->ids)) ?>" title="<?php _e('Delete') ?>" class="btn btn-dark actionItem" data-confirm="<?php _e('Are you sure to delete this item?') ?>" data-call-after="Core.ajax_pages();">
                            <i class="fad fa-trash text-danger"></i>
                        </a>
                    </div>
                </div>

            </td>

        </tr>
    <?php endforeach ?>


<?php } else { ?>
    <div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
        <div>
            <div class="text-center px-4">
                <img class="mw-100 mh-300px" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty.png">
            </div>
        </div>
    </div>
<?php } ?>