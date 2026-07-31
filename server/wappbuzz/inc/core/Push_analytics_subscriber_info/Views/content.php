<div class="container d-sm-flex align-items-md-center pt-4 align-items-center justify-content-center">
    <div class="bd-search position-relative me-auto mt-4 mb-2">
        <h2 class="mb-0 text-gray-800 mb-2"> <i class="<?php _ec( $config['icon'] )?> me-2" style="color: <?php _ec( $config['color'] )?>;"></i> <?php _e($config['name'])?></h2>
        <p><?php _e( $config['desc'] )?></p>
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