
<div class="opt-preview position-relative overflow-hidden m-auto border">
    
    <div class="container py-5">
        <div class="row">
            <div class="col-md-12 mb-5">
                
                <div class="bg-gray-200 w-100 h-70 mb-5"></div>
                <div class="bg-gray-200 w-100 h-300"></div>

            </div>
            <div class="col-md-4">
                
                <div class="bg-gray-200 w-100 h-150 mb-5"></div>
                <div class="bg-gray-200 w-100 h-300 mb-5"></div>
                <div class="bg-gray-200 w-100 h-120 mb-5"></div>

            </div>
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-7">
                        <div class="bg-gray-200 w-100 h-37 mb-5"></div>
                        <div class="bg-gray-200 w-100 h-37 mb-5"></div>
                        <div class="bg-gray-200 w-100 h-37 mb-5"></div>
                        <div class="bg-gray-200 w-100 h-37 mb-5"></div>
                    </div>
                    <div class="col-md-5">
                        <div class="bg-gray-200 w-100 h-275 mb-5"></div>
                    </div>
                </div>
                <div class="bg-gray-200 w-100 h-50 mb-5"></div>
                <div class="bg-gray-200 w-100 h-50 mb-5"></div>
                <div class="bg-gray-200 w-100 h-150 mb-5"></div>
            </div>
        </div>
    </div>

    <div class="opt-preview-content"></div>

</div>

<style type="text/css">
    .main-wrapper {
        overflow: hidden!important;
    }

    .opt-preview .opt-preview-content{
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
    }
</style>

<div class="otp-type d-none" data-type="box">
    <style type="text/css">
        .vn-sp-opt-box{
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            margin: auto;
            background-color: rgba(0, 0, 0, {opt_opacity});
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }

        .vn-sp-opt-box .vn-sp-opt-content{
            max-width: 400px;
            background: {opt_bg};
            margin: auto;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
            padding: 20px;
            border-radius: 0 0 10px 10px;
        }

        .vn-sp-opt-box .vn-sp-opt-content.bottom{
            position: absolute;
            left: calc(50% - 200px);
            border-radius: 10px 10px 0 0;
            bottom: 0;
        }

        .vn-sp-opt-box .vn-sp-opt-content .sp-opt-box-detail{
            display: flex;
        }

        .vn-sp-opt-box .vn-sp-opt-content .sp-opt-box-detail .sp-opt-box-info{
            flex-grow: 1 !important;
            margin-left: 10px;
        }

        .vn-sp-opt-box .vn-sp-opt-content .vn-sp-opt-box-title{
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .vn-sp-opt-box .vn-sp-opt-content .vn-sp-opt-box-desc{
            font-size: 14px;
            margin-bottom: 20px;
        }

        .vn-sp-opt-box .vn-sp-opt-content .vn-sp-opt-box-action{
            text-align: right;
        }

        .vn-sp-opt-box .vn-sp-opt-content .vn-sp-opt-box-action .vn-sp-opt-btn-allow{
            text-align: right;
            appearance: none;
            background-color: {allow_btn_bg};
            border: 1px solid rgba(27, 31, 35, 0.15);
            border-radius: 6px;
            box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
            box-sizing: border-box;
            color: {allow_btn_text};
            cursor: pointer;
            display: inline-block;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            padding: 6px 16px;
            position: relative;
            transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
            white-space: nowrap;
            word-wrap: break-word;
        }

        .vn-sp-opt-box .vn-sp-opt-content .vn-sp-opt-box-action .vn-sp-opt-btn-deny{
            text-align: right;
            appearance: none;
            background-color: {deny_btn_bg};
            border: 1px solid rgba(27, 31, 35, 0.15);
            border-radius: 6px;
            box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
            box-sizing: border-box;
            color: {deny_btn_text};
            cursor: pointer;
            display: inline-block;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            padding: 6px 16px;
            position: relative;
            transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
            white-space: nowrap;
            word-wrap: break-word;
            margin-right: 10px;
        }
    </style>
    <div class="sp-opt-box">
        <div class="sp-opt-content animate__animated animate__slideIn{opt_slide} {opt_position}">
            <div class="sp-opt-box-detail">
                <div class="sp-opt-box-icon">
                    <img src="<?php _ec( get_file_url($domain->icon) )?>" width="45" height="45">
                </div>
                <div class="sp-opt-box-info">
                    <div class="sp-opt-box-title">{opt_title}</div>
                    <div class="sp-opt-box-desc">{opt_desc}</div>
                    <div class="sp-opt-box-action">
                        <a class="sp-opt-btn sp-opt-btn-deny" href="javascript:void(0);"><?php _e("Deny")?></a>
                        <a class="sp-opt-btn sp-opt-btn-allow" href="javascript:void(0);"><?php _e("Allow")?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="otp-type d-none" data-type="mini">
    <style type="text/css">
        .vn-sp-opt-mini{
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            margin: auto;
            background-color: rgba(0, 0, 0, {opt_opacity});
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }

        .vn-sp-opt-mini .vn-sp-opt-content{
            width: 400px;
            background: {opt_bg};
            position: absolute;
            top: 15px;
            left: 100px;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
            padding: 20px;
            border-radius: 10px;
        }

        .opt-preview.mini .sp-opt-mini .sp-opt-content{
            border-radius: 0 0 10px 10px;
            left: 0;
            top: 0;
            position: relative;
            width: auto;
            max-width: 400px;
        }

        .vn-sp-opt-mini .vn-sp-opt-content .vn-sp-opt-mini-title{
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .vn-sp-opt-mini .vn-sp-opt-content .vn-sp-opt-mini-desc{
            font-size: 14px;
            margin-bottom: 20px;
        }

        .vn-sp-opt-mini .vn-sp-opt-content .vn-sp-opt-mini-action{
            text-align: right;
        }

        .vn-sp-opt-mini .vn-sp-opt-content .vn-sp-opt-mini-action .vn-sp-opt-btn-allow{
            text-align: right;
            appearance: none;
            background-color: {allow_btn_bg};
            border: 1px solid rgba(27, 31, 35, 0.15);
            border-radius: 6px;
            box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
            box-sizing: border-box;
            color: {allow_btn_text};
            cursor: pointer;
            display: inline-block;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            padding: 6px 16px;
            position: relative;
            transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
            white-space: nowrap;
            word-wrap: break-word;
        }

        .vn-sp-opt-mini .vn-sp-opt-content .vn-sp-opt-mini-action .vn-sp-opt-btn-deny{
            text-align: right;
            appearance: none;
            background-color: {deny_btn_bg};
            border: 1px solid rgba(27, 31, 35, 0.15);
            border-radius: 6px;
            box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
            box-sizing: border-box;
            color: {deny_btn_text};
            cursor: pointer;
            display: inline-block;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            padding: 6px 16px;
            position: relative;
            transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
            white-space: nowrap;
            word-wrap: break-word;
            margin-right: 10px;
        }
    </style>
    <div class="sp-opt-mini">
        <div class="sp-opt-content animate__animated animate__slideInDown">

            <div class="sp-opt-mini-title">{opt_title}</div>
            <div class="sp-opt-mini-desc">{opt_desc}</div>
            <div class="sp-opt-mini-action">
                <a class="sp-opt-btn sp-opt-btn-deny" href="javascript:void(0);"><?php _e("Deny")?></a>
                <a class="sp-opt-btn sp-opt-btn-allow" href="javascript:void(0);"><?php _e("Allow")?></a>
            </div>

        </div>
    </div>
</div>

<div class="otp-type d-none" data-type="bar">
    <style type="text/css">
        .vn-sp-opt-bar{
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            margin: auto;
            text-align: center;
            background-color: rgba(0, 0, 0, {opt_opacity});
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }

        .vn-sp-opt-bar .vn-sp-opt-content{
            width: 100%;
            background: <?php _ec($opt_options["opt_bg"])?>;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
            padding-top: 12px;
            padding-bottom: 12px;
        }

        .vn-sp-opt-bar .vn-sp-opt-content.bottom{
            position: absolute;
            bottom: 0;
        }

        .vn-sp-opt-bar .vn-sp-opt-content .vn-sp-opt-bar-title{
            font-size: 16px;
            margin-bottom: 5px;
        }

        .vn-sp-opt-bar .vn-sp-opt-content .vn-sp-opt-btn-allow{
            text-align: right;
            appearance: none;
            background-color: {allow_btn_bg};
            border: 1px solid rgba(27, 31, 35, 0.15);
            border-radius: 6px;
            box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
            box-sizing: border-box;
            color: {allow_btn_text};
            cursor: pointer;
            display: inline-block;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            padding: 6px 16px;
            position: relative;
            transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
            white-space: nowrap;
            word-wrap: break-word;
            margin-left: 20px;
        }

        .opt-preview.mini .vn-sp-opt-bar .vn-sp-opt-content .vn-sp-opt-bar-title{
            font-size: 16px;
            margin-bottom: 5px;
            padding-left: 20px;
            padding-right: 20px;
            text-align: center;
            display: block;
        }

        .opt-preview.mini .vn-sp-opt-bar .vn-sp-opt-content .vn-sp-opt-btn-allow{
            margin-left: 0;
        }

        .vn-sp-opt-bar .vn-sp-opt-content .vn-sp-opt-btn-deny{
            position: absolute;
            top: 10px;
            right: 10px;
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
    <div class="sp-opt-bar">
        <div class="sp-opt-content animate__animated animate__slideIn{opt_slide} {opt_position}">
            <a class="sp-opt-btn sp-opt-btn-deny" href="javascript:void(0);">x</a>
            <span class="sp-opt-bar-title">{opt_title}</span>
            <a class="sp-opt-btn sp-opt-btn-allow" href="javascript:void(0);"><?php _e("Allow")?></a>
        </div>
    </div>
</div>

<div class="otp-type d-none" data-type="popup">
    <style type="text/css">
        .vn-sp-opt-popup{
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            margin: auto;
            background-color: rgba(0, 0, 0, {opt_opacity});
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }

        .vn-sp-opt-popup .vn-sp-opt-content{
            max-width: 400px;
            background: {opt_bg};
            margin:  150px auto;
            border-radius: 10px;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
        }

        .opt-preview.mini .sp-opt-popup .sp-opt-content{
            margin: 150px 20px;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-popup-banner{
            width: 100%;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-popup-banner img{
            width: 100%;
            border: none!important;
            border-radius: 10px 10px 0 0;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-popup-wrap{
            padding: 25px;
            text-align: center;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-popup-title{
            font-size: 18px;
            text-transform: uppercase;
            color: #0089cf;
            font-weight: bold;
            max-width: 200px;
            margin: 0 auto 15px auto;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-popup-desc{
            font-size: 14px;
            margin-bottom: 20px;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-btn-allow{
            text-align: right;
            appearance: none;
            background-color: {allow_btn_bg};
            border: 1px solid rgba(27, 31, 35, 0.15);
            border-radius: 6px;
            box-shadow: rgba(27, 31, 35, 0.04) 0 1px 0, rgba(255, 255, 255, 0.25) 0 1px 0 inset;
            box-sizing: border-box;
            color: {allow_btn_text};
            cursor: pointer;
            display: inline-block;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 14px;
            font-weight: 500;
            line-height: 20px;
            list-style: none;
            padding: 10px 20px;
            position: relative;
            transition: background-color 0.2s cubic-bezier(0.3, 0, 0.5, 1);
            user-select: none;
            -webkit-user-select: none;
            touch-action: manipulation;
            vertical-align: middle;
            white-space: nowrap;
            word-wrap: break-word;
            margin-left: 20px;
            min-width: 150px;
            text-align: center;
        }

        .vn-sp-opt-popup .vn-sp-opt-content .vn-sp-opt-btn-deny{
            position: absolute;
            top: 10px;
            right: 10px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 100px;
            color: #000;
            text-align: center;
            padding: 0;
        }
    </style>
    <div class="sp-opt-popup">
        <div class="sp-opt-content animate__animated animate__slideInDown">
            <div class="sp-opt-popup-banner"></div>
            <a class="sp-opt-btn sp-opt-btn-deny" href="javascript:void(0);"><i class="fal fa-times"></i></a>
            <div class="sp-opt-popup-wrap">
                <div class="sp-opt-popup-title">{opt_title}</div>
                <div class="sp-opt-popup-desc">{opt_desc}</div>
                <div class="sp-opt-popup-action">
                    <a class="sp-opt-btn sp-opt-btn-allow" href="javascript:void(0);"><?php _e("Allow")?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="otp-type d-none" data-type="none">
    
</div>

<div class="otp-type d-none" data-type="native">
    <style type="text/css">
        .vn-sp-opt-native{
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            margin: auto;
            font-family: -apple-system, system-ui, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }

        .vn-sp-opt-native .vn-sp-opt-content{
            width: 500px;
            background: #fff;
            margin:  150px auto;
            border-radius: 10px;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
        }

        .vn-sp-opt-native .vn-sp-opt-content .vn-sp-opt-native-banner{
            width: 100%;
        }

        .vn-sp-opt-native .vn-sp-opt-content .vn-sp-opt-native-banner img{
            width: 100%;
            border: none!important;
            border-radius: 10px 10px 0 0;
        }

        .vn-sp-opt-native .vn-sp-opt-content .vn-sp-opt-native-wrap{
            padding: 25px;
            text-align: center;
        }

        .vn-sp-opt-native .vn-sp-opt-content .vn-sp-opt-native-title{
            font-size: 16px;
            font-weight: bold;
            margin: 0 auto 15px auto;
        }

    </style>
    <div class="sp-opt-native">
        <div class="sp-opt-content">
            <div class="sp-opt-native-banner">
                <img src="<?php _ec( get_module_path( __DIR__, "Assets/img/img_native.svg") )?>">
            </div>
            <div class="sp-opt-native-wrap">
                <div class="sp-opt-native-title"><?php _e("This is a browser native theme, where user is directly asked to allow notification.")?></div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function(){

        var opt_bg = "<?php _ec( $opt_options['opt_bg'] )?>";
        var opt_opacity = <?php _ec( $opt_options['opt_opacity']/100 )?>;
        var opt_delay = <?php _ec( $opt_options['opt_delay'] )?>;
        var opt_position = "<?php _ec( $opt_options['opt_position'] )?>";
        var allow_btn_bg = "<?php _ec( $opt_options['opt_allow_btn_bg'] )?>";
        var deny_btn_bg = "<?php _ec( $opt_options['opt_deny_btn_bg'] )?>";
        var allow_btn_text = "<?php _ec( $opt_options['opt_allow_btn_text'] )?>";
        var deny_btn_text = "<?php _ec( $opt_options['opt_deny_btn_text'] )?>";
        var opt_title = "<?php _ec( $opt_options['opt_title'] )?>";
        var opt_desc = "<?php _ec( $opt_options['opt_desc'] )?>";

        $(document).on("change", ".opt-mode input", function(){
            var mode = parseInt($(this).val());


            if(mode == 1){
                $(".opt-preview").width(350);
                $(".opt-preview").addClass("mini");
            }else{
                $(".opt-preview").css({"width": "100%"});
                $(".opt-preview").removeClass("mini");
            }
        });

        $(document).on("change", ".opt-theme", function(){
            var theme = $(this).val();
            var item = $(".otp-type[data-type='"+theme+"']").html();
            var preview = $(".opt-preview-content");
            if(item != undefined){
                item = item.replaceAll("{opt_bg}", opt_bg);
                item = item.replaceAll("{opt_opacity}", opt_opacity);
                item = item.replaceAll("{opt_delay}", opt_delay);
                item = item.replaceAll("{opt_position}", opt_position);
                item = item.replaceAll("{opt_slide}", opt_position=="top"?"Down":"Up");
                item = item.replaceAll("{allow_btn_text}", allow_btn_text);
                item = item.replaceAll("{deny_btn_text}", deny_btn_text);
                item = item.replaceAll("{allow_btn_bg}", allow_btn_bg);
                item = item.replaceAll("{deny_btn_bg}", deny_btn_bg);
                item = item.replaceAll("{opt_title}", opt_title);
                item = item.replaceAll("{opt_desc}", opt_desc);
                item = item.replaceAll("vn-sp-", "sp-");
                preview.html("");
                preview.html(item);

                if (opt_banner != "") {
                    $(".opt-popup-banner").html('<img src="'+opt_banner+'">')
                }

                if(theme == "none"){
                    $(".sp-opt-none").removeClass("d-none");
                }else{
                    $(".sp-opt-none").addClass("d-none");
                }

                if(theme != "none" && theme != "native"){
                    $(".sp-otp-options").removeClass("d-none");
                }else{
                    $(".sp-otp-options").addClass("d-none");
                }

                if(theme == "box" || theme == "bar"){
                    $(".opt-position-wrap").removeClass("d-none");
                }else{
                    $(".opt-position-wrap").addClass("d-none");
                }

                if(theme == "popup"){
                    $(".opt-banner").removeClass("d-none");
                }else{
                    $(".opt-banner").addClass("d-none");
                }
            }
        });

        $(".opt-theme").trigger("change");
        $(".opt-trigger").trigger("change");

        const sp_opt_in_box_observer = new MutationObserver(function (mutations, sp_opt_in_box_observer) {
            mutations.forEach(function (mutation) {
                opt_banner = $(".opt-banner .fm-list-box input").val();
                if (opt_banner != "" && opt_banner != undefined) {
                    $(".sp-opt-popup-banner").html("<img src='<?php _ec(get_file_url(''))?>/"+opt_banner+"'>");
                }else{
                    $(".sp-opt-popup-banner").html("");
                }
                $(".opt-theme").trigger("change");
            });

            
        });

        if(document.querySelector(".fm-selected-media .items")){
            sp_opt_in_box_observer.observe( document.querySelector('.fm-selected-media .items') , {
                attributeFilter: ["title"],
                attributeOldValue: true,
                characterDataOldValue: true,
                childList: true,
                subtree: true,
            });
        }

        $(document).on("change", ".opt-title, .opt-desc, .opt-allow-btn-text, .opt-deny-btn-text, .opt-allow-btn-bg, .opt-deny-btn-bg, .opt-delay, .opt-position, .opt-bg, .opt-trigger, .opt-opacity, .opt-banner input", function(){
            opt_trigger = $(".opt-trigger").val();
            opt_opacity = $(".opt-opacity").val()/100;
            opt_bg = $(".opt-bg").val();
            opt_delay = $(".opt-delay").val();
            opt_position = $(".opt-position").val();
            allow_btn_bg = $(".opt-allow-btn-bg").val();
            deny_btn_bg = $(" .opt-deny-btn-bg").val();
            allow_btn_text = $(".opt-allow-btn-text").val();
            deny_btn_text = $(".opt-deny-btn-text").val();
            opt_title = $(".opt-title").val();
            opt_desc = $(".opt-desc").val();
            opt_banner = $(".opt-banner .fm-list-box input").val();

            var theme = $(".opt-theme").val();
            var item = $(".otp-type[data-type='"+theme+"']").html();
            var preview = $(".opt-preview-content");
            item = item.replaceAll("{opt_bg}", opt_bg);
            item = item.replaceAll("{opt_opacity}", opt_opacity);
            item = item.replaceAll("{opt_delay}", opt_delay);
            item = item.replaceAll("{opt_position}", opt_position);
            item = item.replaceAll("{allow_btn_text}", allow_btn_text);
            item = item.replaceAll("{deny_btn_text}", deny_btn_text);
            item = item.replaceAll("{allow_btn_bg}", allow_btn_bg);
            item = item.replaceAll("{deny_btn_bg}", deny_btn_bg);
            item = item.replaceAll("{opt_title}", opt_title);
            item = item.replaceAll("{opt_desc}", opt_desc);
            

            item = item.replaceAll("vn-sp-", "sp-");
            preview.html("");
            preview.html(item);

            if (opt_banner != "" && opt_banner != undefined) {
                $(".sp-opt-popup-banner").html("<img src='<?php _ec(get_file_url(''))?>/"+opt_banner+"'>");
            }else{
                $(".sp-opt-popup-banner").html("");
            }

            if(theme == "popup"){
                $(".opt-banner").removeClass("d-none");
            }else{
                $(".opt-banner").addClass("d-none");
            }

            if(theme != "none" && theme != "native"){
                $(".sp-otp-options").removeClass("d-none");
            }else{
                $(".sp-otp-options").addClass("d-none");
            }
        });

        $(document).on("change", ".opt-trigger", function(){
            var trigger = $(this).val();
            $(".opt-trigger-option").addClass("d-none");
            $(".opt-trigger-option[data-type='"+trigger+"']").removeClass("d-none");
        });


    });

</script>