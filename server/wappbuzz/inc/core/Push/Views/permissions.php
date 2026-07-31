<?php 
    $permissions = get_blocks("block_push_permissions", false, true);
    $permissions = array_reverse($permissions);
?>

<div class="mb-3">
    <label class="form-label"><?php _e("Total websites")?></label>
    <div class="mb-3">
        <input type="number" class="form-control" id="push_total_websites" name="permissions[push_total_websites]" value="<?php _ec( (int)plan_permission('text', "push_total_websites") )?>">
    </div>
</div>

<div class="mb-3">
    <label class="form-label"><?php _e("Total subscribers")?></label>
    <div class="mb-3">
        <input type="number" class="form-control" id="push_total_subscribers" name="permissions[push_total_subscribers]" value="<?php _ec( (int)plan_permission('text', "push_total_subscribers") )?>">
    </div>
</div>

<div class="p-15 bg-light-primary b-r-10 border">
    <?php foreach ($permissions as $key => $permission): ?>
        <div class="accordion mb-3" id="accordion_push_<?php _ec($permission['id'])?>">
            <div class="accordion-header d-flex mb-3" id="headingOne">
                <div class="form-check form-check-sm form-check-custom">
                    <input class="form-check-input" type="checkbox" name="permissions[<?php _ec($permission['id'])?>]" id="push_<?php _ec($permission['id'])?>" value="1" data-bs-toggle="collapse" data-bs-target="#tab_push_<?php _ec($permission['id'])?>" aria-expanded="true" aria-controls="tab_push_<?php _ec($permission['id'])?>" <?php _e( plan_permission('checkbox', $permission['id']) == 1?"checked":"" )?>>
                </div>
                <label for="push_<?php _ec($permission['id'])?>" class="nav-link text-gray-700 px-2 b-r-6 text-active-white" data-bs-toggle="collapse" data-bs-target="#tab_push_<?php _ec($permission['id'])?>" aria-expanded="true" aria-controls="tab_push_<?php _ec($permission['id'])?>"><?php _e($permission['name'])?></label>
            </div>
            <?php if (isset($permission['data']['html']) && $permission['data']['html'] != "" && !$permission['data']['html']): ?>
                <div id="tab_push_<?php _ec($permission['id'])?>" class="accordion-collapse collapse <?php _e( 1==1?"show":"" )?>" aria-labelledby="headingOne" data-bs-parent="#accordion_push_<?php _ec($permission['id'])?>">
                    <div class="border b-r-6 p-20">
                        <?php _ec( $permission['data']['html'] )?>
                    </div>
                </div>
            <?php endif ?>
        </div>
    <?php endforeach ?>
</div>