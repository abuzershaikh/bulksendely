<?php
$chat_completions = [
    'gpt-4-turbo',
    'gpt-4-1106-preview',
    'gpt-4-vision-preview',
    'gpt-4',
    'gpt-4-32k',
    'gpt-4-0613',
    'gpt-4-32k-0613',
    'gpt-3.5-turbo',
    'gpt-3.5-turbo-0301'
];
?>

<form class="actionForm" action="<?php _eC(get_module_url("save_ai/" . $account->token)) ?>" method="POST" data-redirect="<?php _ec(get_module_url("index/list/" . $account->ids)) ?>">
    <div class="container my-5 mw-800">
        <div class="bd-search position-relative me-auto">
            <h2 class="mb-0 py-4"> <i class="<?php _ec($config['icon']) ?> me-2" style="color: <?php _ec($config['color']) ?>;"></i> <?php _e("Chatbot Settings") ?></h2>
        </div>

        <div class="card b-r-6 h-100 post-schedule wrap-caption">

            <div class="card-body position-relative">
                <input type="text" class="form-control form-control-solid d-none" name="instance_id" value="<?php _ec($account->token) ?>" required>

                <div class="mb-4">
                    <label class="form-label"><?php _e("AI Status") ?></label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" <?php _ec((get_data($ai, "status") == 1 || get_data($ai, "status") == "") ? "checked='true'" : "") ?> id="status_enable" value="1">
                            <label class="form-check-label" for="status_enable"><?php _e('Enable') ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" <?php _ec(get_data($ai, "status", "radio", 0)) ?> id="status_disable" value="0">
                            <label class="form-check-label" for="status_disable"><?php _e('Disable') ?></label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("AI ApiKey") ?></label>
                    <input type="password" class="form-control form-control-solid" name="apikey" value="<?php _ec(get_data($ai, "apikey")) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("AI Temperature") ?></label>
                    <input type="number" min="0" max="2" step="0.1" class="form-control form-control-solid" name="temperature" value="<?php _ec(get_data($ai, "temperature")) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("AI Model") ?></label>
                    <select class="form-control form-control-solid" name="model">
                    <?php foreach ($chat_completions as  $value): ?>
                    <option value="<?php echo $value ?>" <?php if(isset($ai->model) && $value == $ai->model) echo 'selected'; ?> ><?php echo $value?></option>
                    <?php endforeach ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><?php _e("Max Tokens") ?></label>
                    <input type="text" class="form-control form-control-solid" name="max_tokens" value="<?php _ec(get_data($ai, "max_tokens")) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("Chat keyword to Enable bot") ?></label>
                    <input type="text" class="form-control form-control-solid" name="key_enable" value="<?php _ec(get_data($ai, "key_enable")) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php _e("Chat keyword to Disable bot") ?></label>
                    <input type="text" class="form-control form-control-solid" name="key_disable" value="<?php _ec(get_data($ai, "key_disable")) ?>">
                </div>


            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="<?php _ec(get_module_url("index/list/" . $account->ids)) ?>" class="btn btn-dark btn-hover-scale">
                        <?php _e("Back") ?>
                    </a>
                    <button type="submit" class="btn btn-primary btn-hover-scale">
                        <?php _e("Submit") ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</form>

<script type="text/javascript">
    $(function() {
        Core.tagsinput();
    });
</script>