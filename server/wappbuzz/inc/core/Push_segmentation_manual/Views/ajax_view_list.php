<?php if ( !empty($result) ): ?>
	
	<?php foreach ($result as $key => $value): ?>

		<?php 
			$token = json_decode($value->token); 
		?>
		
		<tr class="item">
		    <th scope="row" class="py-3 ps-4 border-bottom text-center">
		        <?php _e( (int)(post("current_page") * ($key + 1) ) )?>
		    </th>

		    <td class="border-bottom"><?php _ec( $token->authToken )?></td>
		    <td class="border-bottom text-nowrap"><?php _ec( datetime_show($value->first_visit) )?></td>
		    <td class="border-bottom"><?php _ec( $value->device )?></td>
		    <td class="border-bottom"><?php _ec( $value->os )?></td>
		    <td class="border-bottom"><?php _ec( $value->browser )?></td>
		    <td class="border-bottom"><?php _ec( $value->resolution )?></td>
		    <td class="border-bottom"><?php _ec( $value->location )?></td>
		    <td class="border-bottom"><?php _ec( $value->language )?></td>
		    <td class="border-bottom"><?php _ec( $value->timezone )?></td>
		    <td class="border-bottom"><?php _ec( $value->subscription_url )?></td>
		    <td class="border-bottom text-nowrap"><?php _ec( datetime_show($value->last_visit) )?></td>
		    <td class="border-bottom"><?php _ec( $value->last_visited_url )?></td>
				
		</tr>

	<?php endforeach ?>
	
<?php else: ?>

	<?php if ($total_items == 0): ?>
	<tr class="item">
		<td class="border-bottom p-20" colspan="13">
			<?php _ec( $this->include('Core\Push\Views\empty'), false);?>
		</td>
	</tr>
	<?php endif ?>
	
<?php endif ?>