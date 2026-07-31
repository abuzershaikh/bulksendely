<form>
    <div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
        <div class="bd-search position-relative me-auto">
            <h2 class="mb-0 py-4"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _ec(get_data($account, "name")) ?></h2>
        </div>
        <div class="">
            <div class="dropdown me-2">
                <div class="input-group input-group-sm sp-input-group border b-r-4">
                    <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                    <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search") ?>" autocomplete="off">

                    <style>
                        .nocaret.dropdown-toggle::after {
                            display: none;
                        }
                    </style>
                    <div class="dropdown m-r-1 border-start " style="height: 44px;" title="<?php _e("More Options") ?>" data-toggle="tooltip" data-placement="top">
                        <button class="btn btn-light btn-active-light-primary dropdown-toggle nocaret" type="button" id="dropdownMenuButton1" aria-haspopup="true" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fad fa-ellipsis-h text-primary"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li>
                                <a href="<?php _e(get_module_url('delete')) ?>" class="dropdown-item actionMultiItem" title="<?php _e("Delete Selected") ?>" data-confirm="<?php _e('Are you sure to delete this items?') ?>" data-redirect="">
                                    <i class=" fad fa-trash fa-fw text-danger"></i>
                                    <?php _e("Delete Selected") ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php _ec(get_module_url("index/ai_settings/" . $account->ids)) ?>" class="dropdown-item" title="<?php _e("Edit Chatbot Settings") ?>" data-toggle="tooltip" data-placement="top" data-redirect="">
                                    <i class="fad fa-robot fa-fw  text-primary"></i>
                                    <?php _e("Edit Chatbot Settings") ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item actionMultiItem" href="<?php _e(get_module_url('export_chatbot/' . $account->token)) ?>" data-confirm="<?php _e('Are you sure to export this items?') ?>" href="#">
                                    <i class="fad fa-file-download fa-fw  text-primary"></i>
                                    <?php _e("Export Chatbots") ?>
                                </a>
                            </li>

                            <li>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#ImportChatbotModal">
                                    <i class="fad fa-file-upload fa-fw text-primary"></i>
                                    <?php _e("Import Chatbots") ?>
                                </button>
                            </li>

                            <li>
                                <a href="<?php _ec(get_module_url("index/capturer_report/" .  $account->ids)) ?>" class="dropdown-item" title="<?php _e("Capturer Report") ?>" data-toggle="tooltip" data-placement="top" data-redirect="">
                                    <i class="fad fa-file-user fa-fw text-info"></i>
                                    <?php _e("Capturer Report") ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <a href="<?php _ec(get_module_url("index/update/" . $account->ids)) ?>" class="btn btn-light btn-active-light-primary m-r-1 border-start" title="<?php _e("Add new") ?>" data-toggle="tooltip" data-placement="top" data-redirect=""><i class="fad fa-plus text-primary"></i></a>

                    <?php if ($run) : ?>

                        <a href="<?php _ec(get_module_url("status/" . $account->token)) ?>" class="btn btn-light btn-success m-r-1 border-start actionItem" title="<?php _e("Click to stop") ?>" data-toggle="tooltip" data-placement="top" data-redirect=""><i class="fas fa-circle-notch fa-spin pe-0 me-1"></i></a>

                    <?php else : ?>
                        <a href="<?php _ec(get_module_url("status/" . $account->token)) ?>" class="btn btn-light btn-danger m-r-1 border-start actionItem" title="<?php _e("Click to run") ?>" data-toggle="tooltip" data-placement="top" data-redirect=""><i class="fad fa-stop-circle"></i></a>
                    <?php endif ?>
                </div>


            </div>


        </div>
    </div>

    <?php if (get_data($datatable, "total_items") != 0) : ?>
        <div class="container">
            <div class="ajax-pages" data-url="<?php _ec(get_module_url("ajax_list_items/" . get_data($account, "ids"))) ?>" data-response=".ajax-result" data-per-page="<?php _ec(get_data($datatable, "per_page")) ?>" data-current-page="<?php _ec(get_data($datatable, "current_page")) ?>" data-total-items="<?php _ec(get_data($datatable, "total_items")) ?>">

                <div class="ajax-result row mt-4"></div>


                <nav class="m-t-50 m-b-50 ajax-pagination m-auto text-center"></nav>


            </div>
        </div>

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

</form>



<!-- Import Chatbot Modal -->

<div class="modal fade" id="ImportChatbotModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _ec("Import Chatbots") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="actionForm" action="<?php _eC(get_module_url("import_chatbot/" .  $account->token)) ?>" method="POST" data-redirect="">
                    <div class="tab-pane fade show active p-50" id="import_csv">
                        <div class="col mb-3">
                            <p><?php _e("To import chatbots, click on bellow button and select the json file.") ?></p>
                        </div>
                        <button type="button" class="btn btn-primary btn-block fileinput-button w-100">
                            <i class="fas fa-upload"></i> <?php _e("Upload JSON chatbots") ?>
                            <input id="import_whatsapp_chatbot" type="file" accept=".json" name="files[]" data-action="<?php _ec(get_module_url("import_chatbot/" .  $account->token)) ?>">
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!--End Import Chatbot Modal -->