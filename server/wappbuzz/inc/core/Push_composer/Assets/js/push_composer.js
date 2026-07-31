"use strict";
function Push_composer(){
    var self = this;
    var timeout;
    var preview_tmp = {};

    this.init = function(){
        $(document).on("change", ".push-main select[name='post_by']", function(){
            var that = $(this);
            var type = $(this).val();
            $(".push-main .post-by").addClass("d-none");
            $(".push-main .post-by[data-by='"+type+"']").removeClass("d-none").show();

            if(type != 3){
                $(".btnSchedulePost").removeClass("d-none");
                $(".btnSaveDraft").addClass("d-none");
            }else{
                $(".btnSchedulePost").addClass("d-none");
                $(".btnSaveDraft").removeClass("d-none");
            }
        });

        $(document).on("click", ".push-main .addSpecificDays", function(){
            var that = $(this);
            var item = $(".tempPostByDays").find(".item"); 
            var c = item.clone();
            c.find("input").attr("name", "time_posts[]").addClass("datetime").val("");
            $(".listPostByDays").append(c);
            Core.calendar();

            if( $(".push-main .listPostByDays .remove").length > 1 ){
                $(".push-main .listPostByDays .remove").removeClass("disabled");
            }

            return false;
        });

        $(document).on("click", ".push-main .listPostByDays .remove:not(.disabled)", function(){
            var that = $(this);
            that.parents(".item").remove();

            if( $(".push-main .listPostByDays .remove").length < 2 ){
                $(".push-main .listPostByDays .remove").addClass("disabled");
            }
        });
    };
}

var Push_composer = new Push_composer();
$(function(){
    Push_composer.init();
});