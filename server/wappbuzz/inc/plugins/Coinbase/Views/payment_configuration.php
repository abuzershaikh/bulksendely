<div class="card mb-4">
    <div class="card-header">
        <div class="card-title"><?php _e( $config['name'] )?></div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-4">
                    <label for="coinbase_status" class="form-label"><?php _e('One-time payment status')?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="coinbase_status" <?php _e( get_option("coinbase_status", 0)==1?"checked='true'":"" )?> id="coinbase_status_enable" value="1">
                            <label class="form-check-label" for="coinbase_status_enable"><?php _e('Enable')?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="coinbase_status" <?php _e( get_option("coinbase_status", 0)==0?"checked='true'":"" )?> id="coinbase_status_disable" value="0">
                            <label class="form-check-label" for="coinbase_status_disable"><?php _e('Disable')?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label for="coinbase_api_key" class="form-label"><?php _e('Coinbase api key')?></label>
            <input type="text" class="form-control form-control-solid" id="coinbase_api_key" name="coinbase_api_key" value="<?php _ec( get_option("coinbase_api_key", "") )?>">
        </div>
        
        <div class="alert alert-primary">
            <span class="fw-6"><?php _e("Webhook 'URL:")?></span> 
            <a href="<?php _ec( base_url("coinbase/webhook") )?>" target="_blank"><?php _ec( base_url("coinbase/webhook") )?></a> 
        </div>

        <div class="mb-4">
            <label for="coinbase_webhook_id" class="form-label"><?php _e('Webhook ID')?></label>
            <input type="text" class="form-control form-control-solid" id="coinbase_webhook_id" name="coinbase_webhook_id" value="<?php _ec( get_option("coinbase_webhook_id", "") )?>">
        </div>
    </div>
</div>