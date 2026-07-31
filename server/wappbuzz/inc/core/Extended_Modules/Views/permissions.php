<style>
    #plan_extended_modules>div>div:first-of-type {
        display: none;
    }
</style>
<div class="mb-5">
    <label class="form-label text-primary text-uppercase"><?php _e("Whatsapp Extensions") ?></label>

    <?php if (find_modules("whatsapp_link_generator")) : ?>
        <div class="mb-3">
            <label for="whatsapp_link_generator" class="form-label">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="permissions[whatsapp_link_generator]" id="whatsapp_link_generator" value="1" <?php _e(plan_permission('checkbox', "whatsapp_link_generator") == 1 ? "checked" : "") ?>>
                    <label class="form-check-label" for="whatsapp_link_generator"><?php _e("Link Generator") ?></label>
                </div>
            </label>
        </div>
    <?php endif ?>

    <div class="mb-3">
        <label for="whatsapp_data_capturer" class="form-label">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_data_capturer]" id="whatsapp_data_capturer" value="1" <?php _e(plan_permission('checkbox', "whatsapp_data_capturer") == 1 ? "checked" : "") ?>>
                <label class="form-check-label" for="whatsapp_data_capturer"><?php _e("Whatsapp Data Capturer") ?></label>
            </div>
        </label>
    </div>

    <div class="mb-3">
        <label for="whatsapp_api_data" class="form-label">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_api_data]" id="whatsapp_api_data" value="1" <?php _e(plan_permission('checkbox', "whatsapp_api_data") == 1 ? "checked" : "") ?>>
                <label class="form-check-label" for="whatsapp_api_data"><?php _e("Whatsapp Get API Data") ?></label>
            </div>
        </label>
    </div>

    <div class="mb-3">
        <label for="whatsapp_livechat" class="form-label">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="permissions[whatsapp_livechat]" id="whatsapp_livechat" value="1" <?php _e(plan_permission('checkbox', "whatsapp_livechat") == 1 ? "checked" : "") ?>>
                <label class="form-check-label" for="whatsapp_livechat"><?php _e("Whatsapp LiveChat") ?></label>
            </div>
        </label>
    </div>


</div>