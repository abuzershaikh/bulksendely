<div class="container">
    <form class="actionForm" action="<?php _e( get_module_url( ( uri('segment', 3)=="unofficial"?"save_unofficial":"save" ) ) )?>" method="POST" data-redirect="<?php _e( base_url("account_manager") )?>">
    <div class="row justify-content-center mt-5">
        <div class="col-md-7">
            <div class="card mb-4 mb-xl-10">
                <div class="card-header cursor-pointer">
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0"><i class="<?php _e( $config['icon'] )?>" style="color: <?php _e($config['color'])?>"></i> <?php _e("Add Whatsapp profiles")?></h3>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (check_number_account("whatsapp", "profile", false, false) || uri("segment", 3) == $instance_id): ?>
                    <div class="py-2 check-wrap-all">
                        <div class="border b-r-10 p-20 mb-4">
                            <div class="fs-16 fw-6"><i class="fad fa-key"></i> <?php _e("Instance ID:")?> <span class="text-success"><?php _ec($instance_id)?></span></div>
                            <?php if(get_option('wa_paircode') == 0) {?>
                            <div class="text-gray-600"><?php _e("Scan the QR Code on your Whatsapp app")?></div>
                            <?php } else { ?>
                            <div class="text-gray-600"><?php _e("Scan the QR Code on your Whatsapp app")?> <?php _e('Or:');?></div>
                            
                            <p></p>
                            <button type="button" class="btn btn-outline btn-outline-dashed bg-white" data-bs-toggle="modal" data-bs-target="#PairingCodeModal"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>"></i> <?php _e("Login with Pairing Code")?></button>
                            <?php } ?>
                        </div>

                        <div class="text-center wa-qr-code" data-instance-id="<?php _ec($instance_id)?>">
                            <?php if($has_pair == false){ ?>
                            <div class="wa-code text-center">
                                
                                <div class="w-300 h-300 d-flex justify-content-center align-items-center fs-60 m-auto border b-r-10 text-dark">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <?php }else { ?>
                            <div class="border b-r-10 p-20 text-center">
                                <?php if($pair_code != "" && $has_error == false){ ?>
                                <h5><?php _ec($pair_code);?></h5>
                                <?php } else { ?>
                                <div class="alert alert-danger">
                                    <?php _e($error_msg);?>
                                </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php $number_accounts = (int)permission("number_accounts"); ?>
                        <div class="alert alert-danger d-flex align-items-center">
                            <div class="fs-40 me-3"><i class="fad fa-exclamation-circle"></i></div>
                            <div>
                                <div class="fw-bold"><?php _e("Limit number of accounts")?></div>
                                <?php _e( sprintf(__("You can only add up to %s Whatsapp profiles"), $number_accounts ) )?>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>

            <?php if (!empty($accounts)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="card-title"><i class="fad fa-sync me-2" style="color: <?php _e($config['color'])?>"></i> <?php _ec("Relogin to keep old instance id")?></div>
                </div>
                <div class="card-body">
                    <?php foreach ($accounts as $key => $value): ?>
                        <div class="d-flex flex-stack">
                            <div class="symbol symbol-45px me-3">
                                <img src="<?php _ec( get_file_url($value->avatar) )?>" class="align-self-center" alt="">
                            </div>
                            <div class="d-flex align-items-center flex-row-fluid flex-wrap">
                                <div class="flex-grow-1 me-2 text-over-all">
                                    <a href="<?php _ec( $value->url)?>" class="text-gray-800 text-hover-primary fs-14 fw-bold"><?php _e( $value->name )?></a>
                                    <span class="text-muted fw-semibold d-block fs-12"><?php _e( $value->pid )?></span>
                                    <?php if ($value->status == 0): ?>
                                    <a href="<?php _ec( base_url("whatsapp_profiles/oauth/".$value->token) )?>" class="text-danger fw-semibold d-block fs-12"><?php _e( "Re-login required" )?></a>
                                    <?php endif ?>
                                </div>
                            </div>
                            <a href="<?php _ec( base_url("whatsapp_profiles/oauth/".$value->token) )?>" class="btn btn-sm btn-outline-dashed btn-light-danger "><?php _e("Relogin")?></a>
                        </div>
                        <?php if($key + 1 != count($accounts)){?>
                        <div class="separator separator-dashed my-4"></div>
                        <?php }?>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>

            <div class="card">
                <div class="card-body">
                    <div class="note">
                        <div class="desc m-b-15"><?php _e("If you don't see your profiles above, you might try to reconnect, re-accept all permissions, and ensure that you're logged in to the correct profile.")?></div>
                        <a href="<?php _ec( get_module_url("oauth") )?>" class="btn btn-outline btn-outline-dashed bg-white"><i class="<?php _ec( $config['icon'] )?>" style="color: <?php _ec( $config['color'] )?>"></i> <?php _e("Re-connect with Whatsapp")?></a>
                        <br>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
</div>

<!-- Import Chatbot Modal -->

<div class="modal fade" id="PairingCodeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php _ec("Login With Pairing code") ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form class="PairingCodeModal" action="<?php _ec(get_module_url("oauth")) ?>" method="POST" data-redirect="">
                    <div class="tab-pane fade show active p-50" id="PairingCodeModal_form">
                        <div class="col mb-3">
                            <input type="hidden" id="instance_id" name="instance_id" value="<?php _ec($instance_id)?>">
                            <label for="phone" class="form-label"><?php _e("Phone Number") ?></label>
                            <input id="phone" type="text" class="form-control" name="phone"  required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block w-100">
                        <?php _e("Submit") ?>
                    </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
.intl-tel-input{
    display: block;
}
</style>

<!--End Import Chatbot Modal -->