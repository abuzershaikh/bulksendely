"use strict";
function Push(){
    var self = this;
    this.init = function(){
        self.actions();
    };

    this.actions = function(){
        $(document).on("click", ".push-main input[data-bs-toggle='collapse']", function(){
            var id = $(this).parents('.accordion-header').find("label").attr("for");
            var name = $(this).attr("name");
            var value = $(this).val();
            var label = $(this).parents('.accordion-header').find("label");
            if ( label.hasClass("collapsed") ) {
                $("#"+id).prop("checked", false);
            }else{
                $("#"+id).prop("checked", true);
            }

            if(name == "action_button"){
                if( $(this).is(":checked") ){
                    $(".pv-btn-actions").removeClass("d-none")
                }else{
                    $(".pv-btn-actions").addClass("d-none")
                }
            }

            if(name == "large_image_status"){
                if( $(this).is(":checked") ){
                    $(".pv-large-image").removeClass("d-none")
                }else{
                    $(".pv-large-image").addClass("d-none")
                }
            }
        });

        $(document).on("change", ".push-main input[name='action_button_count']", function(){
            var value = $(this).val();
            if(value == 1){
                $(".pv-btn-action-2").removeClass("d-none");
            }else{
                $(".pv-btn-action-2").addClass("d-none");
            }
        });

        $(document).on("keyup", ".push-main input[name='title']", function(){
            var text = $(this).val();
            var c = $(this).attr("data-default-text");
            if(text == "" && c != ""){
                text = c;
            }
            $(".piv-title").html(text);
        });


        $(document).on("change", ".push-main input[name='large_image']", function(){
            var img = $(this).val();
            var c = $(this).attr("data-default-image");
            if(img == "" && c != ""){
                img = c;
            }
            $(".piv-image").attr("src", img);
        });

        $(document).on("keyup", ".push-main input[name='action_button1_name']", function(){
            var text = $(this).val();
            var c = $(this).attr("data-default-text");
            if(text == "" && c != ""){
                text = c;
            }
            $(".piv-btn1").html(text);
        });

        $(document).on("keyup", ".push-main input[name='action_button2_name']", function(){
            var text = $(this).val();
            var c = $(this).attr("data-default-text");
            if(text == "" && c != ""){
                text = c;
            }
            $(".piv-btn2").html(text);
        });

        const push_observer = new MutationObserver(function (mutations, push_observer) {
            mutations.forEach(function (mutation) {
                $(".fm-selected-media .items .fm-list-item").each(function(name, value){
                    var that = $(this);
                    var is_image = that.data("is-image");
                    var file = that.data("file");
                    var c = $(".push-main .push-icon").attr("data-default-icon");

                    if(event.type == "DOMNodeRemoved"){
                        file = c;
                    }

                    if(is_image){
                        $(".push-main").find(".piv-icon").attr('src', file);
                    }else{}
                });
            });

            
        });

        if(document.querySelector(".fm-selected-media .items")){
            push_observer.observe( document.querySelector(".fm-selected-media .items") , {
                attributeFilter: ["title"],
                attributeOldValue: true,
                characterDataOldValue: true,
                childList: true,
                subtree: true,
            });
        }
    };
}

var Push = new Push();
$(function(){
    Push.init();
});