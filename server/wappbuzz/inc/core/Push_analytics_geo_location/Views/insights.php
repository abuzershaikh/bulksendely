<div class="row">
    <div class="col-md-12">


        <div class="card border b-r-10 mb-4 pt-4">
            <div class="card-body">
                <div id="chart_geo" class="h-550">
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                </div>
                <div class="w-300 m-auto text-center text-dark fw-6"><?php _e("Clicks Map")?></div>

                <?php if (!empty($geo["list"])): ?>
                <div class="card border mt-4">
                    <div class="card-body">
                        <table class="table table table-row-dashed">
                            <thead>
                                <tr class="fs-12 text-gray-600">
                                    <th scope="col" class="fw-6"><?php _e("Country")?></td>
                                    <th scope="col" class="text-end"><?php _e("Delivered")?></td>
                                    <th scope="col" class="text-end"><?php _e("Clicked")?></td>
                                    <th scope="col" class="text-end"><?php _e("CTR")?></td>
                                </tr>
                            </thead>
                            <tbody>
                                
                                    <?php foreach ($geo["list"] as $key => $value): ?>
                                    <tr class="text-gray-500 fs-12">
                                        <td> <i class="flag-icon flag-icon-<?php _ec( strtolower( $value->country ) )?> me-2"></i> <?php _ec($value->country_name)?></td>
                                        <td class="text-end fw-6"><?php _ec( $value->delivered )?></td>
                                        <td class="text-end fw-6"><?php _ec( $value->clicked )?></td>
                                        <td class="text-end fw-6"><?php _ec( $value->ctr )?>%</td>
                                    </tr>
                                    <?php endforeach ?>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                    <?php _ec( $this->include('Core\Push\Views\empty'), false);?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){
        Core.map_chart({
            id: 'chart_geo',
            name: '<?php _e("Clicked")?>',
            minColor: 'rgba(214, 219, 254, 1)',
            maxColor: 'rgba(92, 92, 176, 1)',
            data: <?php _ec( $geo["chart"] )?>,
            legend: true
            
        });
    });
</script>