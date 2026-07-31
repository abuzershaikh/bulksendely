<?php if ($domain->ios_status && $domain->ios_popup_text != "" && $domain->ios_name != "" && $domain->ios_start_url != "" && $domain->ios_icon != ""): ?>
<style type="text/css">
    .sp-opt-ios{
        margin: auto;
        font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        z-index: 999999999999999;
        position: fixed;
        bottom: 0;
    }

    .sp-opt-ios .sp-opt-content{
        max-width: 400px;
        background: <?php _ec($domain->ios_popup_bg_color)?>;
        margin: auto;
        box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
        padding: 20px;
        color: <?php _ec($domain->ios_popup_text_color)?>;
        position: relative;
    }

    .sp-opt-ios .sp-opt-content .sp-opt-ios-detail{
        display: flex;
    }

    .sp-opt-ios .sp-opt-content .sp-opt-ios-detail .sp-opt-ios-info{
        flex-grow: 1 !important;
        margin-left: 10px;
        font-size: 14px;
        margin-top: -3px;
    }

    .sp-opt-ios .sp-opt-content .sp-opt-ios-close{
        position: absolute;
        top: 3px;
        right: 3px;
        width: 22px;
        height: 22px;
        font-size: 16px;
        background: #fff;
        border-radius: 100px;
        border: 1px solid #eee;
        color: #000;
        text-align: center;
        padding: 0;
        text-decoration: none;
        line-height: 16px;
        font-weight: 600;
    }
</style>

<?php 
$ios_popup_text = $domain->ios_popup_text;
$ios_popup_text = str_replace("{share_icon}", "<img src='".get_module_path( __DIR__, "../Push/Assets/img/share_icon.png" )."' width='16' height='16'>" , $ios_popup_text);
$ios_popup_text = str_replace("{add_icon}", "<img src='".get_module_path( __DIR__, "../Push/Assets/img/add_icon.png" )."' width='16' height='16'>" , $ios_popup_text);
$ios_popup_text = str_replace("{app_name}", $domain->ios_name , $ios_popup_text);
?>

<div class="sp-opt-ios" id="sp-opt-ios">
    <div class="sp-opt-content border">
        <div class="sp-opt-ios-detail">
            <div class="sp-opt-ios-icon">
                <?php if ($domain->ios_icon != ""): ?>
                    <img src="<?php _ec( get_file_url( $domain->ios_icon ) )?>" width="45" height="45">
                <?php endif ?>
            </div>
            <div class="sp-opt-ios-info"><?php _ec($ios_popup_text)?></div>
            <div class="sp-opt-ios-close">x</div>
        </div>
    </div>
</div>
<?php endif ?>
