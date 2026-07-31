<form action="<?php _eC(get_module_url("do_capturer_report/" . $account->token)) ?>" method="POST" data-loading="false">
    <div class="container my-5">

        <div class="bd-search position-relative me-auto">
            <h2 class="mb-0 py-4"> <i class="fad fa-file-user me-2 text-info"></i> <?php _e("Capturer Report") ?></h2>
        </div>

        <div class="card b-r-6 h-100 post-schedule wrap-caption">
            <div class="card-body position-relative">


                <div class="table-responsive">

                    <table class="table table align-middle table-row-dashed fs-13 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bolder text-uppercase gs-0">
                                <th scope="col" class="w-20 border-bottom py-4 ps-4">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input checkbox-all" type="checkbox">
                                    </div>
                                </th>
                                <th class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e("Input Name") ?></th>
                                <th class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e("Caption") ?></th>
                                <th class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e("Description") ?></th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (!empty($result)) : ?>
                                <?php foreach ($result as $key => $value) : ?>                                    
                                    <tr class="item">
                                        <th scope="row" class="py-3 ps-4 border-bottom">
                                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                <input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->ids) ?>">
                                            </div>
                                        </th>
                                        <td><?php _e($value->inputname) ?></td>
                                        <td><?php _e($value->caption) ?></td>
                                        <td><?php _e($value->description) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            <?php endif ?>

                        </tbody>
                    </table>

                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?php _ec(get_module_url("index/list/" . $account->ids)) ?>" class="btn btn-dark btn-hover-scale">
                        <?php _e("Back") ?>
                    </a>
                    <button type="submit" class="btn btn-success btn-hover-scale">
                        <?php _e("Report") ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>


<script type="text/javascript">
    $(function() {
        Core.tagsinput();
    });
</script>