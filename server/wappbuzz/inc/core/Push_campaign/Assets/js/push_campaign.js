"use strict";
function Push_campaign(){
    var self = this;
    this.init = function(){
    };

    this.saveContent = function(result){
        Core.ajax_pages();
        $('#addPushCampaign').modal('hide');
    };
}

var Push_campaign = new Push_campaign();
$(function(){
    Push_campaign.init();
});