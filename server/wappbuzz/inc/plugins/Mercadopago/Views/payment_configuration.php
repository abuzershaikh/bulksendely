<div class="card mb-4">
    <div class="card-header">
        <div class="card-title"><?php _e( $config['name'] )?></div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-4">
                    <label for="mercadopago_one_time_status" class="form-label"><?php _e('Pagamento Único')?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mercadopago_one_time_status" <?php _e( get_option("mercadopago_one_time_status", 0)==1?"checked='true'":"" )?> id="mercadopago_one_time_status_enable" value="1">
                            <label class="form-check-label" for="mercadopago_one_time_status_enable"><?php _e('Habilitar')?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mercadopago_one_time_status" <?php _e( get_option("mercadopago_one_time_status", 0)==0?"checked='true'":"" )?> id="mercadopago_one_time_status_disable" value="0">
                            <label class="form-check-label" for="mercadopago_one_time_status_disable"><?php _e('Desabilitar')?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <label for="mercadopago_access_token" class="form-label"><?php _e('Public Token')?></label>
            <input type="text" class="form-control form-control-solid" id="mercadopago_public_token" name="mercadopago_public_token" value="<?php _ec( get_option("mercadopago_public_token", "") )?>" >
        </div>        
        <div class="mb-4">
            <label for="mercadopago_access_token" class="form-label"><?php _e('Access Token Produção')?></label>
            <input type="text" class="form-control form-control-solid" id="mercadopago_access_token" name="mercadopago_access_token" value="<?php _ec( get_option("mercadopago_access_token", "") )?>">
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-4">
                    <label for="creditCard_status" class="form-label"><?php _e('Habilitar Cartão de Crédito')?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="creditCard_status" <?php _e( get_option("creditCard_status", 0)==1?"checked='true'":"" )?> id="creditCard_status_enable" value="1">
                            <label class="form-check-label" for="creditCard_status_enable"><?php _e('Habilitar')?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="creditCard_status" <?php _e( get_option("creditCard_status", 0)==0?"checked='true'":"" )?> id="creditCard_status_disable" value="0">
                            <label class="form-check-label" for="creditCard_status_disable"><?php _e('Desabilitar')?></label>
                        </div>
                        <div style="margin-top: 10px;">
                            <input style="width: 100px;" type="number" min="1" max="12" class="form-control form-control-solid" id="creditCard_maxInstallments" name="creditCard_maxInstallments" value="<?php echo _ec( empty(get_option("creditCard_maxInstallments")) ? 1 : get_option("creditCard_maxInstallments") )?>" oninput="limitValue(this)">
                            <label style="margin-top: 7px;" class="form-check-label" for="creditCard_maxInstallments"><?php _e('Limite Parcelas')?></label>
                        </div>                        
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <label for="debitCard_status" class="form-label"><?php _e('Habilitar Cartão de Débito')?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="debitCard_status" <?php _e( get_option("debitCard_status", 0)==1?"checked='true'":"" )?> id="debitCard_status_enable" value="1">
                            <label class="form-check-label" for="debitCard_status_enable"><?php _e('Habilitar')?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="debitCard_status" <?php _e( get_option("debitCard_status", 0)==0?"checked='true'":"" )?> id="debitCard_status_disable" value="0">
                            <label class="form-check-label" for="debitCard_status_disable"><?php _e('Desabilitar')?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-4">
                    <label for="ticket_status" class="form-label"><?php _e('Habilitar Pagamento Boleto')?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="ticket_status" <?php _e( get_option("ticket_status", 0)==1?"checked='true'":"" )?> id="ticket_status_enable" value="1">
                            <label class="form-check-label" for="ticket_status_enable"><?php _e('Habilitar')?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="ticket_status" <?php _e( get_option("ticket_status", 0)==0?"checked='true'":"" )?> id="ticket_status_disable" value="0">
                            <label class="form-check-label" for="ticket_status_disable"><?php _e('Desabilitar')?></label>
                        </div>
                    </div>
                </div>
            </div> 
            <div class="col-md-6">
                <div class="mb-4">
                    <label for="bankTransfer_status" class="form-label"><?php _e('Habilitar Pagamento PIX')?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="bankTransfer_status" <?php _e( get_option("bankTransfer_status", 0)==1?"checked='true'":"" )?> id="bankTransfer_status_enable" value="1">
                            <label class="form-check-label" for="bankTransfer_status_enable"><?php _e('Habilitar')?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="bankTransfer_status" <?php _e( get_option("bankTransfer_status", 0)==0?"checked='true'":"" )?> id="bankTransfer_status_disable" value="0">
                            <label class="form-check-label" for="bankTransfer_status_disable"><?php _e('Desabilitar')?></label>
                        </div>
                    </div>
                </div>
            </div>            
        </div>
    </div>
</div>
<script>
function limitValue(input) {
    if (input.value < 1) {
        input.value = 1;
    }
    if (input.value > 12) {
        input.value = 12;
    }
}
</script>
