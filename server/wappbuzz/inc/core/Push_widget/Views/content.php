
<div class="opt-preview">
    
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

    <div class="widget-preview-content"></div>

</div>

<style type="text/css">
    .main-wrapper {
        overflow: hidden!important;
    }

    .opt-preview .widget-preview-content{
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
    }
</style>

<div class="otp-type d-none" data-type="none">
    <style type="text/css">
        .vn-sp-opt-none{
            max-width: 55px;
            height: 55px;
            position: absolute;
            bottom: {widget_bottom}px;
            border-radius: 100px;
            width: 55px;
            box-shadow: rgba(149, 157, 165, 0.2) 0px 8px 24px;
            background-color: {widget_bg};
        }

        .vn-sp-opt-none.left{
            left: {widget_left}px;
        }

        .vn-sp-opt-none.right{
            right: {widget_right}px;
        }

        .vn-sp-opt-none img{
            width: 100%;
            height: 100%;
            border: none!important;
            border-radius: 100px;
        }

    </style>
    <div class="sp-opt-none animate__animated animate__swing {widget_position}">
        <img src="{widget_icon}">
    </div>
</div>

<script type="text/javascript">
    $(function(){

        var widget_status = "<?php _ec( $widget_options['widget_status'] )?>";
        var widget_position = "<?php _ec( $widget_options['widget_position'] )?>";
        var widget_left = "<?php _ec( $widget_options['widget_left'] )?>";
        var widget_right = "<?php _ec( $widget_options['widget_right'] )?>";
        var widget_bottom = "<?php _ec( $widget_options['widget_bottom'] )?>";
        var widget_bg = "<?php _ec( $widget_options['widget_bg'] )?>";
        var widget_icon = "<?php _ec( $widget_options['widget_icon'] )?>";

        $(document).on("change", ".widget-status", function(){
            var status = $(this).val();
            var item = $(".otp-type[data-type='none']").html();
            var preview = $(".widget-preview-content");
            item = item.replaceAll("{widget_bg}", widget_bg);
            item = item.replaceAll("{widget_position}", widget_position);
            item = item.replaceAll("{widget_left}", widget_left);
            item = item.replaceAll("{widget_right}", widget_right);
            item = item.replaceAll("{widget_bottom}", widget_bottom);
            item = item.replaceAll("{widget_icon}", widget_icon);
            item = item.replaceAll("{widget_bg}", widget_bg);
            item = item.replaceAll("vn-sp-", "sp-");
            preview.html("");
            preview.html(item);

            if(status == 1){
                $(".sp-widget-disable").addClass("d-none");
                $(".sp-widget-options").removeClass("d-none");
            }else{
                $(".sp-widget-disable").removeClass("d-none");
                $(".sp-widget-options").addClass("d-none");
            }

            $(".widget-position-option").addClass("d-none");
            $(".widget-position-option[data-type='"+widget_position+"']").removeClass("d-none");
        });

        $(".widget-status").trigger("change");

        $(document).on("change", ".widget-status, .widget-icon, .widget-position, .widget-bg, .widget-left, .widget-right, .widget-bottom", function(){

            console.log(3433);
            widget_status = $(".widget-status").val();
            widget_icon = $(".widget-icon").val();
            widget_bg = $(".widget-bg").val();
            widget_position = $(".widget-position").val();
            widget_left = $(".widget-left").val();
            widget_right = $(".widget-right").val();
            widget_bottom = $(".widget-bottom").val();

            var item = $(".otp-type[data-type='none']").html();
            var preview = $(".widget-preview-content");
            item = item.replaceAll("{widget_bg}", widget_bg);
            item = item.replaceAll("{widget_position}", widget_position);
            item = item.replaceAll("{widget_left}", widget_left);
            item = item.replaceAll("{widget_right}", widget_right);
            item = item.replaceAll("{widget_bottom}", widget_bottom);
            item = item.replaceAll("{widget_icon}", widget_icon);
            item = item.replaceAll("{widget_bg}", widget_bg);
            item = item.replaceAll("vn-sp-", "sp-");
            preview.html("");
            
            if(widget_status == 1){
                preview.html(item);
                $(".sp-widget-disable").addClass("d-none");
                $(".sp-widget-options").removeClass("d-none");
            }else{
                $(".sp-widget-disable").removeClass("d-none");
                $(".sp-widget-options").addClass("d-none");
            }

            $(".widget-position-option").addClass("d-none");
            $(".widget-position-option[data-type='"+widget_position+"']").removeClass("d-none");
        });



    });

</script>