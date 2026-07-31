<?php if ( !empty($result) ): ?>
	<?php $count = 1; $pass = 0;?>
	<?php foreach ($result as $key => $value): ?>
		
		<tr class="item">
		    <td class="py-3 ps-4 border-bottom text-center text-nowrap fw-6 fs-12">
		       	<?php _e( (int)(post("current_page") * ($count + 1) ) )?>
		        <?php if ($value->type == 3): ?>
		        	<?php _e($value->action_type==1?"A":"B")?>
		        <?php endif ?>
		    </td>
		    <td class="border-bottom p-20">
		    	<div class="d-flex align-items-top flex-grow-1 w-100">
		    		<div class="symbol symbol-50px overflow-hidden me-3">
						<a href="<?php _e( get_module_url('index/notification/'.$value->ids) )?>" class="actionItem" data-remove-other-active="true" data-active="bg-light-primary" data-result="html" data-content="main-wrapper" data-history="<?php _e( get_module_url('index/notification/'.$value->ids) )?>" data-call-after="Layout.carousel();">
							<div class="symbol-label b-r-10">
								<img src="<?php _ec( get_file_url($value->icon) )?>" class="w-100 border b-r-10">
							</div>
						</a>
					</div>
					<div class="d-flex flex-column w-100">
						<a href="<?php _e( get_module_url('index/notification/'.$value->ids) )?>" class="text-gray-800 text-hover-primary fw-6 actionItem mb-1" data-remove-other-active="true" data-active="bg-light-primary" data-result="html" data-content="main-wrapper" data-history="<?php _e( get_module_url('index/notification/'.$value->ids) )?>" data-call-after="Layout.carousel();"><?php _ec( $value->title )?></a>
			        	<span class="fs-12 mb-1"><?php _ec( $value->message )?></span>
			        	<span class="text-gray-600 mb-3 fs-11"><a href="<?php _ec( $value->url )?>" target="_blank" class="text-gray-600"><?php _ec( $value->url )?></a></span>
			        	<span class="text-gray-600 mb-2 fs-11"><?php _e( datetime_show( $value->created ) )?></span>
			        	<?php if ($value->campaign_name != ""): ?>
		        		<span class="text-gray-600 mb-1 fs-12"><a href="<?php _ec( base_url("push_campaign/analytic/".$value->campaign_ids) )?>"><strong class="text-gray-700"> <?php _ec("Campaign:")?></strong>  <?php _ec($value->campaign_name)?></a></span>
			        	<?php endif ?>
		        		
					</div>
		    	</div>
		    </td>
		    <td class="border-bottom fw-8 text-center"><?php _e( (int)$value->number_sent )?></td>
		    <td class="border-bottom fw-8 text-center"><?php _e( (int)$value->number_delivered )?></td>
		    <td class="border-bottom fw-8 text-center"><?php _e( (int)$value->number_action )?></td>
		    <td class="border-bottom fw-8 text-center"><?php _e( (int)$value->number_delivered!=0?round( $value->number_action/$value->number_delivered*100 ):0 )?><?php _e("%")?></td>
		</tr>

		<?php 
		if ( $value->type != 3 || $pass == 1 ) {
			$count++;
			$pass = 0;
		}else{
			$pass++;
		}
		
		?>

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