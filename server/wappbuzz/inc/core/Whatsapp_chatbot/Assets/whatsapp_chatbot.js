"use strict";
function WhatsappChatbot() {
    var self = this;

    this.init = function () {
        self.action();
        self.import_chatbot();
        self.get_from_api();
    };

    this.action = function () {

    }

    this.closeImportModal = function () {
        $('#ImportChatbotModal').modal('hide');
    };

    this.import_chatbot = function () {
        if ($("#import_whatsapp_chatbot").length > 0) {
            var url = $("#import_whatsapp_chatbot").data("action");

            $(document).on('change', '#import_whatsapp_chatbot', function () {
                var form_data = new FormData();
                var totalfiles = document.getElementById('import_whatsapp_chatbot').files.length;
                for (var index = 0; index < totalfiles; index++) {
                    form_data.append("files[]", document.getElementById('import_whatsapp_chatbot').files[index]);
                }

                Core.overplay();

                $(this).val('');
                $.ajax({
                    url: url,
                    type: 'post',
                    data: form_data,
                    dataType: 'json',
                    contentType: false,
                    processData: false,
                    xhr: function () {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function (evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total;
                            }
                        }, false);
                        xhr.addEventListener("progress", function (evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total;
                            }
                        }, false);
                        return xhr;
                    },
                    success: function (result) {
                        Core.overplay(true);
                        if (result.status == "success") {
                            window.location.reload();
                        } else {
                            Core.notify(result.message, result.status);
                        }
                    }
                });

                return false;
            });
        }
    };

    this.get_from_api = function () {

        $(document).on("click", ".btn-wa-add-header-api", function () {

            var option = $(".wa-add-header-api-template").html();
            var count_msg_item = $(".wa-add-header-api-items .wa-add-header-api-item").length;

            option = option.replace(/{count}/g, count_msg_item);
            $(".wa-add-header-api-items").append(option);
            $(".wa-add-header-api-items .wa-lm-empty").remove();
            $(".hs-wrap").getNiceScroll().resize();

        });

        $(document).on("click", ".btn-wa-add-body-api", function () {

            var option = $(".wa-add-body-api-template").html();
            var count_msg_item = $(".wa-add-body-api-items .wa-add-body-api-item").length;

            option = option.replace(/{count}/g, count_msg_item);
            $(".wa-add-body-api-items").append(option);
            $(".wa-add-body-api-items .wa-lm-empty").remove();
            $(".hs-wrap").getNiceScroll().resize();

        });


        $(document).on("click", ".btn-wa-rem-header-api", function () {

            var count_msg_item = $(".wa-add-header-api-items .wa-add-header-api-item").length;
            $('.wa-add-header-api-item-' + (count_msg_item - 1)).remove();

            if ((count_msg_item - 1) === 0) {
                var empty_template = $(".wa-add-body-empty-template").html();               
                $(".wa-add-header-api-items").append(empty_template);
            }

        });

        $(document).on("click", ".btn-wa-rem-body-api", function () {

            var count_msg_item = $(".wa-add-body-api-items .wa-add-body-api-item").length;
            $('.wa-add-body-api-item-' + (count_msg_item - 1)).remove();

            if ((count_msg_item - 1) === 0) {
                var empty_template = $(".wa-add-body-empty-template").html();
                $(".wa-add-body-api-items").append(empty_template);
            }

        });

    }
}

var WhatsappChatbot = new WhatsappChatbot();
$(function () {
    WhatsappChatbot.init();
});