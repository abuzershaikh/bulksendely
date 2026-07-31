<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 py-4"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e($config['name']) ?></h2>
    </div>
    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search") ?>" autocomplete="off">
                <a href="<?php _ec(get_module_url("index/update")) ?>" class="btn btn-light btn-active-light-primary border-start" title="<?php _e("Add new") ?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-plus text-primary"></i></a>
            </div>
        </div>
    </div>
</div>
<?php if (get_data($datatable, "total_items") != 0) : ?>
    <div class="container">
        <div class="card card-flush b-r-10">
            <div class="card-body py-0 px-0 pb-5">
                <div class="table-responsive">
                    <table class="table table align-middle table-row-dashed fs-13 gy-5 ajax-pages" data-url="<?php _ec(get_module_url("ajax_list")) ?>" data-response=".ajax-result" data-per-page="<?php _ec(get_data($datatable, "per_page")) ?>" data-current-page="<?php _ec(get_data($datatable, "current_page")) ?>" data-total-items="<?php _ec(get_data($datatable, "total_items")) ?>">
                        <thead>
                            <tr class="text-start text-muted fw-bolder text-uppercase gs-0">
                                <th scope="col" class="w-20 border-bottom py-4 ps-4">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                        <input class="form-check-input checkbox-all" type="checkbox">
                                    </div>
                                </th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap" style="width: 300px;max-width: 300px;"><?php _e('Name') ?></th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap" style="width: 100px; max-width: 100px;">
                                    <i class="text-primary fad fa-mobile"></i> <?php _e("Total") ?>
                                </th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap" style="width: 100px; max-width: 100px;">
                                    <i class="text-success fad fa-check-circle"></i> <?php _e("Valid") ?>
                                </th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap" style="width: 100px; max-width: 100px;">
                                    <i class="text-danger fad fa-exclamation-triangle"></i> <?php _e('Invalid') ?>
                                </th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap" style="width: 100px; max-width: 100px;">
                                    <i class="text-primary fad fa-circle-notch fa-spin"></i> <?php _e('validating') ?>
                                </th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap" style="width: 100px; max-width: 100px;">
                                    <i class="text-warning fad fa-clone"></i> <?php _e("Duplicate") ?>
                                </th>
                                <th scope="col" class="border-bottom py-4 fw-4 fs-12 text-nowrap"><?php _e('Status') ?></th>
                                <th scope="col" class="border-bottom py-4 pe-4"></th>
                            </tr>
                        </thead>
                        <tbody class="ajax-result">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <nav class="m-t-50 m-b-50 ajax-pagination m-auto text-center"></nav>

    </div>
    <script>
        setTimeout(function() {
            var wa_server = '<?php echo get_option('whatsapp_server_url', '') ?>';

            if (!contact_socket && wa_server != '') {
                var contact_socket = io(wa_server, {
                    transports: ['polling']
                });

                contact_socket.on('check_phone_update_<?php echo get_team("id"); ?>', (args) => {
                    Core.ajax_pages();
                })
            }
        }, 2000)
    </script>

    <script type="text/javascript">
        $(function() {
            Core.ajax_pages();
        });
    </script>
<?php else : ?>
    <div class="container">
        <div class="d-flex align-items-center align-self-center h-100 mih-500">
            <div class="w-100">
                <div class="text-center px-4">
                    <img class="mh-190 mb-4" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
                </div>
            </div>
        </div>
    </div>
<?php endif ?>