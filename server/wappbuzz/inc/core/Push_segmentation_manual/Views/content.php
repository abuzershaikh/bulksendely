<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto">
        <h2 class="mb-0 py-4 text-gray-800"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e( $config['name'] )?>
            <div class="fs-14 fw-4 mt-2"><?php _e( $config['desc'] )?></div>
        </h2>
    </div>

    <div class="">
        <div class="dropdown me-2">
            <div class="input-group input-group-sm sp-input-group border b-r-4">
                <span class="input-group-text border-0 fs-20 bg-gray-100 text-gray-800" id="sub-menu-search"><i class="fad fa-search"></i></span>
                <input type="text" class="ajax-pages-search ajax-filter form-control form-control-solid ps-15 border-0" name="keyword" value="" placeholder="<?php _e("Search")?>" autocomplete="off">
                <a href="<?php _ec( get_module_url("popup_add_segmentation") )?>" class="btn btn-light btn-active-light-primary m-r-1 border-end actionItem" title="<?php _e("Add new")?>" data-toggle="tooltip" data-placement="top" data-popup="addAddSegmentation"><i class="fad fa-plus text-primary pe-0"></i></a>
                <a href="<?php _e( get_module_url('delete') )?>" class="btn btn-light btn-active-light-danger actionMultiItem" data-confirm="<?php _e('Are you sure to delete this items?')?>" data-remove-other-active="true" data-active="bg-light-danger" data-result="html" data-content="main-wrapper" data-redirect="<?php _ec( current_url() )?>" title="<?php _e("Delete")?>" data-toggle="tooltip" data-placement="top"><i class="fad fa-trash-alt text-danger pe-0"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 insights">
    <div class="card card-flush b-r-10 border">
        <div class="card-body py-0 px-0 pb-5">

            <?php if ( isset($datatable) ): ?>

                <div class="<?php _e( get_data($datatable, "responsive")? "table-responsive":"" )?>">

                    <?php if ( is_array( get_data($datatable, "columns") ) ): ?>

                        <table 
                            class="ajax-pages table table-border align-middle table-row-dashed fs-13 gy-5" 
                            data-url="<?php _ec( get_module_url("ajax_list"))?>" 
                            data-response=".ajax-result" 
                            data-per-page="<?php _ec( get_data($datatable, "per_page") )?>"
                            data-current-page="<?php _ec( get_data($datatable, "current_page") )?>"
                            data-total-items="<?php _ec( get_data($datatable, "total_items") )?>"
                        >
                            <thead>
                                <tr class="text-start text-muted fw-bolder text-uppercase gs-0">

                                    <?php foreach ( get_data($datatable, "columns") as $key => $value ): ?>

                                        <?php if ( $key == "id" ): ?>
                                        <th scope="col" class="w-20 border-bottom py-4 ps-4 fw-6 fs-12 text-nowrap text-dark"><?php _e("#")?></th>
                                        <?php else: ?>
                                        <th scope="col" class="border-bottom py-4 fw-6 fs-12 text-nowrap text-dark"><?php _e( $value )?></th>
                                        <?php endif ?>
                                    <?php endforeach ?>
                                    <th scope="col" class="border-bottom py-4 fw-6 fs-12 text-nowrap text-dark"> </th>

                                </tr>
                            </thead>
                            <tbody class="ajax-result"></tbody>
                        </table>

                    <?php endif ?>

                </div>
                
            <?php endif ?>

           <?php if (get_data($datatable, "total_items") != 0): ?>
            <nav class="m-t-50 ajax-pagination m-auto text-center mb-4"> </nav>
            <?php endif ?>

        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){
        Core.ajax_pages();
    });
</script>