"use strict";
function Core(){
    var self = this;
    this.init = function(){
        self.actionItem();
        self.actionMultiItem();
        self.actionForm();
        self.setTimezone();
        self.Payment();
        self.ajax_pages();
        self.Fix_header();
        self.Counter();
        self.sp_slider();
        self.rating_slider();
        AOS.init();

        $("#marquee").marquee({
          direction: 'left',
          speed: 20,
          loop: true,
          pausehover: false,
          spaceBetween: 10
        });

        $('.nonloop').owlCarousel({
            center: false,
            items:1,
            loop:false,
            margin:10,
            autoplay:true,
            autoplayTimeout:3000,
            responsive:{
                600:{
                    items: 1
                }
            }
        });

        $(".click-menu").click(function() {
            var id = $(this).attr("data-id");
            if ($("#"+id).length > 0) {
                if($(this).hasClass("nav-link")){
                    $(".header-menu .nav-link").removeClass("active");
                    $(this).addClass("active");
                }
                $('html, body').animate({
                    scrollTop: $("#"+id).offset().top - 100
                }, 500);
            }
        });

        var url = window.location.href;
        const url_arr = url.split("#");
        if(url_arr.length > 1){
            if ($("#"+url_arr[1]).length > 0) {
                $(".header-menu .nav-link").removeClass("active");
                $("[data-id='"+url_arr[1]+"']").addClass("active");
                $('html, body').animate({
                    scrollTop: $("#"+url_arr[1]).offset().top - 100
                }, 500);
            }   
        }
    };

    this.rating_slider = function(){
        var rating_slider = $(".rating-slider").owlCarousel({
            center: false,
            items:1,
            loop:true,
            margin:10,
            autoplay:true,
            autoplayTimeout:5000,
            dots: true,
            responsive:{
                600:{
                    items: 1
                }
            }
        });

        rating_slider.on('changed.owl.carousel', function(event) {
            var index = event.page.index;
            $(".rating-slider-nav li").removeClass("active");
            $(".rating-slider-nav li").each(function(i, v){
                if(i == index){
                    $(this).addClass("active");
                }
            });
        });

        $(document).on("click", ".rating-slider-nav li a", function(){
            var index = $(this).parents("li").data("index");
            index = parseInt(index);
            rating_slider.trigger("to.owl.carousel", index);
        });
    };

    this.sp_slider = function(){
        var sp_slider = $('.sp-slider').owlCarousel({
            center: false,
            items:1,
            loop:true,
            margin:10,
            autoplay:true,
            autoplayTimeout:5000,
            dots: true,
            responsive:{
                600:{
                    items: 1
                }
            }
        });

        $(".sp-slider-nav div:nth-child(1) a").addClass("active");
        sp_slider.on('changed.owl.carousel', function(event) {
            var index = event.page.index;
            $(".sp-slider-nav a").removeClass("active");
            $(".sp-slider-content .item").addClass("d-none");
            $(".sp-slider-nav a").each(function(i, v){
                if(i == index){
                    $(this).addClass("active");
                }
            });

            $(".sp-slider-content .item:nth-child("+(index+1)+")").removeClass("d-none");
        });

        $(document).on("click", ".sp-slider-nav a", function(){
            var index = $(this).data("index");
            index = parseInt(index);
            sp_slider.trigger("to.owl.carousel", index);
        });
    };

    this.Counter = function(){
        if($('.odometer').length > 0){
            window.odometerOptions = {
              format: '(,ddd)'
            };

            setTimeout(function(){
                $('.odometer').each(function(){
                    var number = $(this).attr("data-count-to");
                    number = parseInt(number);
                    $(this).html(number);

                    
                });
            }, 500);

            
            setInterval(function(){
                $('.odometer').each(function(){
                    var number = $(this).attr("data-count-to");
                    number = parseInt(number) + 1;
                    $(this).attr("data-count-to", number);
                    $(this).html(number);
                });
            }, 2000);

        }
    };

    this.Fix_header = function(){
        var header = $(".header");
        var scroll = $(window).scrollTop();
        if(scroll > 0){
            header.addClass("active");
        }

        $(window).scroll(function () {
            var scroll = $(window).scrollTop();
            if( scroll > 20 ){
                header.addClass("active");
            }else{
                header.removeClass("active");
            }
        });
    };

    this.Payment = function(){
        $(document).on("change", ".plan_by", function(){
            if($(this).is(":checked")){
                $(".by_monthly").addClass("d-none");
                $(".by_annually").removeClass("d-none");
            }else{
                $(".by_monthly").removeClass("d-none");
                $(".by_annually").addClass("d-none");
            }
        });
    };

    this.setTimezone = function(){
        var settings = {
            "async": true,
            "crossDomain": true,
            "url": "https://api.ip.sb/geoip",
            "dataType": "jsonp",
            "method": "GET",
            "headers": {
                "Access-Control-Allow-Origin": "*"
            }
        }
        
        $.ajax(settings).done(function (response) {
            var timezone = response.timezone;
            $.post(PATH+"timezone", {csrf:csrf, timezone:timezone}, function(){}, 'json');
            $(".auto-select-timezone").val(timezone);
        });
    };

    this.actionItem= function(){
        $(document).on('click', ".actionItem", function(event) {
            event.preventDefault();    
            var that           = $(this);
            var action         = that.attr("href");
            var id             = that.data("id");
            var data           = $.param({csrf:csrf, id: id});

            self.ajax_post(that, action, data, null);
            return false;
        });
    };

    this.actionMultiItem= function(){
        $(document).on('click', ".actionMultiItem", function(event) {
            event.preventDefault();    
            var that           = $(this);
            var form           = that.closest("form");
            var action         = that.attr("href");
            var params         = that.data("params");
            var data           = form.serialize();
            var data           = data + '&' + $.param({csrf:csrf}) + "&" + params;
            self.ajax_post(that, action, data, null);
            return false;
        });
    };

    this.actionForm= function(){
        $(document).on('submit', ".actionForm", function(event) {
            event.preventDefault();    
            var that           = $(this);
            var action         = that.attr("action");
            var data           = that.serialize();
            var data           = data + '&' + $.param({csrf:csrf});
            
            self.ajax_post(that, action, data, null);
        });
    };

    this.ajax_post = function(that, action, data, _function){
        var confirm        = that.data("confirm");
        var transfer       = that.data("transfer");
        var type_message   = that.data("type-message");
        var rediect        = that.data("redirect");
        var content        = that.data("content");
        var append_content = that.data("append-content");
        var callback       = that.data("callback");
        var history_url    = that.data("history");
        var hide_overplay  = that.data("hide-overplay");
        var call_after     = that.data("call-after");
        var remove         = that.data("remove");
        var type           = that.data("result");
        var object         = false;

        if(type == undefined){
            type = 'json';
        }

        if(confirm != undefined){
            if(!window.confirm(confirm)) return false;
        }

        if(history_url != undefined){
            history.pushState(null, '', history_url);
        }

        if(!that.hasClass("disabled")){
            if(hide_overplay == undefined || hide_overplay == 1){
                self.overplay();
            }
            that.addClass("disabled");
            $.post(action, data, function(result){
                
                //Check is object
                if(typeof result != 'object'){
                    try {
                        result = $.parseJSON(result);
                        object = true;
                    } catch (e) {
                        object = false;
                    }
                }else{
                    object = true;
                }

                //Run function
                if(_function != null){
                    _function.apply(this, [result]);
                }

                //Callback function
                if(result.callback != undefined){
                    $("body").append(result.callback);
                }

                //Callback
                if(callback != undefined){
                    var fn = window[callback];
                    if (typeof fn === "function") fn(result);
                }

                //Using for update
                if(transfer != undefined){
                    that.removeClass("tag-success tag-danger").addClass(result.tag).text(result.text);
                }

                //Add content
                if(content != undefined && object == false){
                    if(append_content != undefined){
                        $("."+content).append(result);
                    }else{
                        $("."+content).html(result);
                    }
                }

                //Call After
                if(call_after != undefined){
                    eval(call_after);
                }

                //Remove Element
                if(remove != undefined){
                    that.parents('.'+remove).remove();
                }

                //Hide Loading
                self.overplay(true);
                that.removeClass("disabled");

                //Redirect
                self.redirect(rediect, result.status);

                //Message
                if(result.status != undefined){
                    if(result.status == "error"){
                        $(".show-message").removeClass("text-danger text-success").addClass("text-danger").html(result.message);
                    }else{
                        $(".show-message").removeClass("text-danger text-success").addClass("text-success").html(result.message);
                    }
                }

            }, type).fail(function() {
                that.removeClass("disabled");
            });
        }

        return false;
    };

    this.ajax_pages = function(){
        if( $(".ajax-pages").length > 0 ){
            var that = $(".ajax-pages");
            var url = that.attr('data-url');
            var filter = $(".ajax-filter");
            var loading = that.attr('data-loading');
            var class_result = that.attr('data-response');
            var call_after = that.attr("data-call-after");
            var call_success = that.attr("data-call-success");
            var per_page = that.attr("data-per-page");
            var current_page = that.attr("data-current-page");
            var total_items = that.attr("data-total-items");

            if(current_page == undefined || Number.isNaN(current_page)){
                current_page = 1;
                loading = 0;
                that.attr('data-page', 0);
                that.attr('data-loading', 0);
            }

            var data = { 
                csrf: csrf, 
                current_page: current_page, 
                per_page: per_page, 
                total_items: total_items 
            };

            if( filter.length > 0 ){
                filter.each( function( index, value ) {
                    var name = $(this).attr("name");
                    var value = $(this).val();
                    data[name] = value;
                } );
            }

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'JSON',
                data: data
            }).done(function(result) {
                $('.ajax-loading').hide();

                $(class_result).html( result.data );

                

                //Call After
                if(call_after != undefined){
                    eval(call_after);
                }

                //Call Success
                if(call_success != undefined && result.status == 'success'){
                    eval(call_success);
                }

                if( $(".paginationjs").length == 0 || total_items != result.total_items ){
                    that.attr("data-total-items", result.total_items );
                    total_items = result.total_items;

                    self.ajax_pages_actions();
                    self.pagination(total_items, per_page, current_page, ".ajax-pages");
                }
            });
        }
    };

    this.ajax_pages_actions = function(){
        $(".ajax-pages-search").keyup(function(e) {
            clearTimeout(timeout);
            timeout = setTimeout(function(){
                e.preventDefault();
                self.ajax_pages();
            }, 500);
            return false;   
        });

        $(".ajax-pages-search").keydown(function(e) {
            if(e.which == 13) {
                return false;
            }   
        });
    };

    this.pagination = function(total_items, per_page, current_page, el_return){
        if( $(".ajax-pagination").length > 0 ){
            $('.ajax-pagination').pagination({
                dataSource: function(done){
                    var result = [];
                    for (var i = 1; i <= total_items; i++) {
                        result.push(i);
                    }
                    done(result);
                },
                pageNumber: current_page,
                pageSize: per_page,
                callback: function(data, pagination) {
                    $(el_return).attr("data-current-page", pagination.pageNumber);
                    self.ajax_pages();
                }
            });
        }
    };

    this.callbacks = function(_function){
        $("body").append(_function);
    };

    this.redirect = function(_rediect, _status){
        if(_rediect != undefined && _status == "success"){
            setTimeout(function(){
                window.location.assign(_rediect);
            }, 1500);
        }
    };

    this.overplay = function(status){
        if(status == undefined){
            $(".loading").show();
            if($(".modal").hasClass("in")){
                $(".loading").addClass("top");
            }else{
                $(".loading").removeClass("top");
            }
        }else{
            $(".loading").hide();
        }
    };

    this.notify = function(_message, _type){
        if(_message != undefined && _message != ""){
            switch(_type){
                case "success":
                    var backgroundColor = "#04c8c8";
                    break;

                case "error":
                    var backgroundColor = "#f1416c";
                    break;

                default:
                    var backgroundColor = "#ffc700";
                    break;
            }

            iziToast.show({
                theme: 'dark',
                icon: 'fad fa-bells',
                title: '',
                position: 'bottomCenter',
                message: _message,
                backgroundColor: backgroundColor,
                progressBarColor: 'rgb(255, 255, 255, 0.5)',
            });
        }
    };
}

var Core = new Core();
$(function(){
    Core.init();
});

class Blob {
    constructor(el, options) {
        this.DOM = {};
        this.DOM.el = el;
        this.options = {};
        Object.assign(this.options, options);
        this.init();
    }
    init() {
        this.rect = this.DOM.el.getBoundingClientRect();
        this.descriptions = [];
        this.layers = Array.from(this.DOM.el.querySelectorAll('path'), t => {
            t.style.transformOrigin = `${this.rect.left + this.rect.width/3}px 0`;
            t.style.opacity = 0;
            this.descriptions.push(t.getAttribute('d'));
            return t;
        });
    }
    intro() {
        anime.remove(this.layers);
        anime({
            targets: this.layers,
            duration: 1800,
            delay: (t,i) => i*120,
            easing: [0.2,1,0.1,1],
            scale: [0.2, 1.9],
            opacity: {
                value: [0,1],
                duration: 300,
                delay: (t,i) => i*120,
                easing: 'linear'
            }
        });
    }
};

window.Blob = Blob;

const DOM = {};
let blobs = [];
DOM.svg = document.querySelector('svg.scene');
if(DOM.svg != null){
    Array.from(DOM.svg.querySelectorAll('g')).forEach((el) => {
        const blob = new Blob(el);
        blobs.push(blob);
        blob.intro();
    });
}
