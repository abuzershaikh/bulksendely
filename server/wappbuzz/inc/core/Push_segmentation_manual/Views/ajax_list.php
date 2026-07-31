<?php if ( !empty($result) ): ?>
	
	<?php foreach ($result as $key => $value): ?>

		<tr class="item">
		    <th scope="row" class="py-3 ps-4 border-bottom text-center">
		        <?php _e( (int)(post("current_page") * ($key + 1) ) )?>
		    </th>
		    <td class="border-bottom"><?php _ec( $value->name )?></td>
		    <td class="border-bottom text-nowrap"><?php _ec( $value->id )?></td>
		    <td class="border-bottom text-nowrap w-90">
		    	<a href="<?php _e( get_module_url('popup_code/'.$value->ids) )?>" class="actionItem btn btn-sm px-2 py-1 btn-success" data-popup="addSegmentationCode" title="<?php _e("Code")?>" data-toggle="tooltip" data-placement="top" data-trigger="hover">
		    		<i class="fad fa-code p-0 fs-12"></i>
		    	</a>
		    	<a href="<?php _e( get_module_url('index/view/'.$value->ids) )?>" class="actionItem btn btn-success btn-sm px-2 py-1" data-result="html" data-content="main-wrapper" data-history="<?php _e( get_module_url('index/view/'.$value->ids) )?>" title="<?php _e("View Audience")?>" data-toggle="tooltip" data-placement="top" data-trigger="hover">
		    		<i class="fad fa-eye p-0 fs-12"></i>
		    	</a>
		    	<a href="<?php _e( get_module_url('popup_add_segmentation/'.$value->ids) )?>" class="actionItem btn btn-sm px-2 py-1 btn-dark" data-popup="addAddSegmentation" title="<?php _e("Edit")?>" data-toggle="tooltip" data-placement="top" data-trigger="hover">
		    		<i class="fad fa-pencil-alt p-0 fs-12"></i>  
		    	</a>
		    	<a href="<?php _e( get_module_url('delete/'.$value->ids) )?>" class="actionItem btn btn-sm px-2 py-1 btn-danger" data-confirm="<?php _e('Deleting this segmentation will immediately stop all active campaigns?')?>" data-remove="item">
		    		<i class="fad fa-trash-alt p-0 fs-12"></i>
		    	</a>
		    </td>
		</tr>

	<?php endforeach ?>
	
<?php else: ?>

	<?php if ($total_items == 0): ?>
	<tr class="item">
		<td class="border-bottom p-20" colspan="6">
			<?php _ec( $this->include('Core\Push\Views\empty'), false);?>
		</td>
	</tr>
	<?php endif ?>
	
<?php endif ?>