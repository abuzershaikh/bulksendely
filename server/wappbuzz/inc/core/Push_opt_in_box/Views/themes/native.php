<style type="text/css">
    .sp-opt-native{
        width: 100%;
        height: auto;
        position: fixed;
        top: 0;
        margin: auto;
        text-align: center;
        background-color: rgba(0, 0, 0, <?php _ec($opt_options["opt_opacity"]/100)?>);
        font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        display: none;
        z-index: 999999999999999;
    }
</style>
<div class="sp-opt-native" id="sp-opt-main">
    <a class="sp-opt-btn sp-opt-btn-allow" id="sp-allow-btn" href="#allow"><?php _e("Allow")?></a>
</div>