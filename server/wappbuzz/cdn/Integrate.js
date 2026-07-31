var script_el = document.querySelector("script[webpush-client-id]");
var client_id = script_el.getAttribute("webpush-client-id");
var domain_id = script_el.getAttribute("webpush-domain-id");
var main_url = script_el.getAttribute("src");
var main_path = main_url.replace("cdn/integrate.js", "").split("?")[0];
var main_api = main_path+"push_request/";
var hostname = location.hostname.replace(/^www\./, "");

var WebPush = new (function () {
	this.init = function( options ) {
		var self = this;
		this.opts = options;
		this.serverKey = this.opts.serverKey;
		this.uid =  this.opts.uid;
		this.cookie_prefix =  "webpush_"+this.opts.cookie_prefix+"_";
		this.hostname = hostname;
		this.enableSubdomainIntegration = this.opts.enableSubdomainIntegration;

		//Web Push
		this.client_id = client_id;
		this.domain_id = domain_id;
		this.main_path = main_path;
		this.main_api = main_api;
		this.sp_subscriber = '';
		this.sp_subscriber_info = false;
		this.show_ios_guide = 1;
		this.web_push_popup(this.opts);
		this.web_push(this.opts);
		this.ios_popup_guide(this.opts);
	},

	this.web_push = function ( sub_options ) {
		var that = this;

		var pushButton = document.querySelector('.js-push-button');
	    if (pushButton) {
	        pushButton.addEventListener('click', function() {
	            if (isPushEnabled) {
	                that.push_unsubscribe();
	            } else {
	                that.push_subscribe();
	            }
	        });
	    }

		if ('serviceWorker' in navigator) {
	        navigator.serviceWorker.register('/sw.js')
	        .then(function(sw) {

	        	if (Notification.permission === "granted") {
	        		that.push_subscribe();
	        		for (var i = 0; i < (webpush = window.webpush || []).length; i++) {
	        			that.sendAction( (webpush = window.webpush || [])[i] );
	        		}
	            } else if (Notification.permission === "denied") {
	            	that.push_unsubscribe();
	            }else{
	            	var trigger = that.opts.optin_opts.opt_trigger;
	            	switch(trigger){
	            		case "on_scroll":
	            			addEventListener("scroll", (event) => {
	            				that.web_push_popup_status("show");
	            				let show_percent = parseInt(that.opts.optin_opts.opt_on_scroll);
	            				let height = document.documentElement.scrollHeight;
	            				var scroll_pos = window.scrollY;
	            				var current_percent = scroll_pos/height*100;
	            				if(show_percent <= current_percent){
	            					that.web_push_popup_status("show");
	            				}
	            			});
	            			break;

	            		case "on_inactivity":
	            			let time_show = parseInt(that.opts.optin_opts.opt_on_inactivity) * 1000;
	            			let current_time = 0;
	            			let check_interval = false;
	            			var optin_interval = setInterval(function () { current_time = time_show; }, time_show);


	            			addEventListener("mousemove", (event) => {
	            				current_time = 0;
								clearInterval(optin_interval);
								optin_interval = setInterval(function () { current_time = time_show; }, time_show);
	            			});

							check_interval =  setInterval(function () {
								if(current_time == time_show){
									clearInterval(check_interval);
									clearInterval(optin_interval);
									that.web_push_popup_status("show");
								}
							}, 1000);

							break;

						case "on_pageviews":
							let pageviews_show = parseInt(that.opts.optin_opts.opt_on_pageviews);
							var pageviews_cookie_name = that.cookie_prefix+"pageviews";
							var pageviews_cookie_counter = that.getCookie(pageviews_cookie_name);
							if(!pageviews_cookie_counter) pageviews_cookie_counter = 0;
							var pageviews_cookie_counter = parseInt(pageviews_cookie_counter) + 1;
							that.setCookie(pageviews_cookie_name, pageviews_cookie_counter, 30);

							if(pageviews_show <= pageviews_cookie_counter){
								that.web_push_popup_status("show");
							}
	            			break;

	            		case "on_landing":
							that.web_push_popup_status("show");
	            			break;

	            		default: 

	            			
	            			break
	            	}

	            	if(that.opts.optin_opts.opt_theme == "native"){
						setTimeout(function(){
	            			document.querySelector("#sp-allow-btn").click();
	            		}, 2000);
	            	}

	            	if(that.opts.optin_opts.opt_theme == "none"){
						that.web_push_popup_status("show");
	            	}

	            	
	            }

	            
	        }, function (e) {
	            console.error('[SW] Oups...', e);
	            that.changePushButtonState('incompatible');

	        });
	    } else {
	        console.warn('[SW] Les service workers ne sont pas encore supportÃ©s par ce navigateur.');
	        that.changePushButtonState('incompatible');
	    }
	},

	this.push_initialiseState = function() {
		var that = this;

	    if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
	        console.warn('[SW] Les notifications ne sont pas supportÃ©es par ce navigateur.');
	        that.changePushButtonState('incompatible');
	        return;
	    }

	    if (Notification.permission === 'denied') {
	        console.warn('[SW] Les notifications ne sont pas autorisÃ©es par l\'utilisateur.');
	        that.changePushButtonState('disabled');
	        return;
	    }

	    if (!('PushManager' in window)) {
	        console.warn('[SW] Les messages Push ne sont pas supportÃ©s par ce navigateur.');
	        that.changePushButtonState('incompatible');
	        return;
	    }

	    navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
	        serviceWorkerRegistration.pushManager.getSubscription()
	        .then(function(subscription) {
	            that.changePushButtonState('disabled');

	            if (!subscription) {
	                return;
	            }

	            push_sendSubscriptionToServer(subscription, 'update');
	            that.changePushButtonState('enabled');
	        })
	        ['catch'](function(err) {
	            console.warn('[SW] Erreur pendant getSubscription()', err);
	        });
	    });
	},

	this.initPostMessageListener = function() {
	    var onRefreshNotifications = function () {
	        var notificationCounters = document.getElementsByClassName('notificationCounter');

	        if (!notificationCounters.length) {
	            var notificationCounterWrappers = document.getElementsByClassName('notificationCounterWrapper');

	            for (var i = 0; i < notificationCounterWrappers.length; i++) {
	                var notificationCounter = document.createElement('span');
	                notificationCounter.className = 'notificationCounter badge badge-notification';
	                notificationCounter.textContent = '0';
	                notificationCounterWrappers[i].appendChild(notificationCounter);
	            }
	        }

	        for (var i = 0; i < notificationCounters.length; i++) {
	            notificationCounters[i].textContent++;
	        }
	    };

	    var onRemoveNotifications = function() {
	        document.querySelector('.notificationCounter').remove();
	    };

	    navigator.serviceWorker.addEventListener('message', function(e) {
	        var message = e.data;

	        switch (message) {
	            case 'reload':
	                window.location.reload(true);
	                break;
	            case 'refreshNotifications':
	                onRefreshNotifications();
	                break;
	            case 'removeNotifications':
	                onRemoveNotifications();
	                break;
	            default:
	                console.warn("Message '" + message + "' not handled.");
	                break;
	        }
	    });
	},

	this.changePushButtonState = function(state) {
	    var $pushButtons = document.querySelectorAll('.js-push-button');

	    for (var i = 0; i < $pushButtons.length; i++) {
	        var pushButton = $pushButtons[i];
	        var $pushButton = document.querySelector(pushButton);

	        switch (state) {
	            case 'enabled':
	                pushButton.disabled = false;
	                pushButton.title = "Notifications Push activÃ©es";
	                $pushButton.addClass("active");
	                isPushEnabled = true;
	                break;
	            case 'disabled':
	                pushButton.disabled = false;
	                pushButton.title = "Notifications Push dÃ©sactivÃ©es";
	                $pushButton.removeClass("active");
	                isPushEnabled = false;
	                break;
	            case 'computing':
	                pushButton.disabled = true;
	                pushButton.title = "Chargement...";
	                break;
	            case 'incompatible':
	                pushButton.disabled = true;
	                pushButton.title = "Notifications Push non disponibles (navigateur non compatible)";
	                break;
	            default:
	                console.error('Unhandled push button state', state);
	                break;
	        }
	    }
	},

	this.push_subscribe = function() {
		var that = this;
	    that.changePushButtonState('computing');

	    navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
	        serviceWorkerRegistration.pushManager.subscribe({
	            userVisibleOnly: true,
	            applicationServerKey: that.urlBase64ToUint8Array(that.serverKey)
	        })
	        .then(function(subscription) {
	            that.changePushButtonState('enabled');
	            that.web_push_listener(that.cookie_prefix + "subs_status|subscribed");
	            return that.push_sendSubscriptionToServer(subscription, 'subscriber');
	        })
	        ['catch'](function(e) {
	            if (Notification.permission === 'denied') {
	                console.warn('[SW] Les notifications ne sont pas autorisÃ©es par l\'utilisateur.');
	                that.changePushButtonState('incompatible');
	            } else {
	                console.error('[SW] Impossible de souscrire aux notifications.', e);
	                that.changePushButtonState('disabled');
	            }
	        });
	    });
	},

	this.push_unsubscribe = function() {
		var that = this;
	  	that.changePushButtonState('computing');

	  	navigator.serviceWorker.ready.then(function(serviceWorkerRegistration) {
		    serviceWorkerRegistration.pushManager.getSubscription().then(
		      function(pushSubscription) {
		        if (!pushSubscription) {
		            that.changePushButtonState('disabled');
		          	return;
		        }

		        that.push_sendSubscriptionToServer(pushSubscription, 'delete');
		        pushSubscription.unsubscribe().then(function(successful) {
		            that.changePushButtonState('disabled');
		        })['catch'](function(e) {
		            console.log('[SW] Erreur pendant le dÃ©sabonnement aux notifications: ', e);
		            that.changePushButtonState('disabled');
		        });
		      })['catch'](function(e) {
		        console.error('[SW] Erreur pendant le dÃ©sabonnement aux notifications.', e);
		      });
	  	});
	},

	this.push_sendSubscriptionToServer = function(subscription, action) {
		var that = this;
	    var req = new XMLHttpRequest();
	    var url = main_api + action + "/" + that.client_id + "/" + that.domain_id;
	    req.open('POST', url, true);
	    req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	    req.setRequestHeader("Content-type", "application/json");
	    req.onreadystatechange = function (e) {
	        if (req.readyState == 4) {
	            if(req.status != 200) {
	                console.error("[SW] Erreur :" + e.target.status);
	            }
	        }
	    };
	    req.onerror = function (e) {
	        console.error("[SW] Erreur :" + e.target.status);
	    };

	    var key = subscription.getKey('p256dh');
	    var token = subscription.getKey('auth');
	    var data = {
	        'endpoint': that.getEndpoint(subscription),
	        'key': key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
	        'token': token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null,
	        'domain': hostname,
	    };

		var browser_info = that.getBrowserInfo();
		var browser_name = browser_info.browser;
		var key;
		for (key in browser_info) {
            data[key] = encodeURIComponent(browser_info[key]);
        }

        data['referrer'] = encodeURIComponent(window.location.href);

        that.web_push_listener(that.cookie_prefix + "subs_id|"+ (token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null));

	    req.send(JSON.stringify(data));

	    return true;
	},

	this.ios_popup_guide = function( sub_options ){
		var browser_info = this.getBrowserInfo();
		var iOS =
        /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase()) &&
        ((browser_info.browserVer >= 16.4 && browser_info.browser == "safari") ||
            (browser_info.browserMajor >= 113 && browser_info.browser == "chrome" && browser_info.osVer >= 16.4) ||
            (browser_info.browserMajor >= 112 && browser_info.browser == "edge" && browser_info.osVer >= 16.4));
            
        var standalone = "standalone" in window.navigator && window.navigator.standalone;
      
        if(this.show_ios_guide){
			if (iOS && !standalone && !window.localStorage.webpush_add_homescreen_message) {
				document.body.innerHTML += sub_options.optin_ios;
				
				if (document.getElementById("sp-opt-ios") != null) {
					document.getElementById("sp-opt-ios").addEventListener("click", (event) => {
						window.localStorage.setItem("webpush_add_homescreen_message", "hide");
						document.getElementById("sp-opt-ios").remove();
					});
				}
			}
		}

	},

	this.web_push_popup = function ( sub_options ){
		var that = this;
		var manifest = document.createElement("link");
        manifest.rel = "manifest";
        manifest.href = "/manifest.json";
        var platform = navigator?.userAgent || navigator?.platform || "unknown";
        if (/iphone|ipad/.test(platform.toLocaleLowerCase()) || (platform.toLocaleLowerCase().indexOf("safari/") >= 0 && platform.toLocaleLowerCase().indexOf("version/") >= 0)) {
            document.head.appendChild(manifest);
        }else{
            this.show_ios_guide = 0;
        }

        var webpush_el = document.createElement('div');
		webpush_el.id = 'webpush';
		if(sub_options.optin_opts.opt_theme == "none"){
			var widget_status = parseInt(sub_options.optin_opts.widget_status);
			if(widget_status === 1){
				webpush_el.innerHTML = sub_options.optin;
			}
		}else{
			webpush_el.innerHTML = sub_options.optin;
		}

		document.body.appendChild(webpush_el);

		if(document.querySelector("#sp-allow-btn")){
			document.querySelector("#sp-allow-btn").addEventListener("click", (event) => {
				that.web_push_popup_status("hide");
				
				Notification.requestPermission().then(function(result) {

					if( Notification.permission == 'granted' ){
						console.log('[SW] Registered service worker');
						that.push_subscribe();
        				that.push_initialiseState();
        				that.initPostMessageListener();
        				that.web_push_listener(that.cookie_prefix + "subs_status|subscribed");
					}

				});
			});
		}

		if(document.querySelector("#sp-deny-btn")){
        	document.querySelector("#sp-deny-btn").addEventListener("click", (event) => {
				that.web_push_listener(that.cookie_prefix + "subs_status|canceled");
				that.web_push_popup_status("hide");
			});
		}
	},

	this.web_push_popup_status = function( status ){
		var that = this;
		switch(status) {
		  case "hide":
		    document.querySelector("#sp-opt-main").classList.remove("sp-opt-show");
		    break;
		  case "show":
		  	if( this.getCookie( this.cookie_prefix + "subs_status" ) != "canceled" ){
		  		setTimeout(function(){ 
		    		document.querySelector("#sp-opt-main").classList.add("sp-opt-show");
		    	}, parseInt(that.opts.optin_opts.opt_delay)*1000);
		  	}
		    break;
		}
	},

	this.web_push_listener = function( data ){
		var data_sent = data.split("|");

		if (data_sent[0] == this.cookie_prefix + "subs_id") {
            this.setCookie(data_sent[0], encodeURIComponent(data_sent[1]), 9999);
            this.subs_id = data_sent[1];
        } else {
            if ((data_sent[0] == "webpush_subs_status" || data_sent[0] == this.cookie_prefix + "subs_status") && data_sent[1] == "canceled") {
                this.setCookie(data_sent[0], data_sent[1], 7);
            } else {
                this.setCookie(data_sent[0], data_sent[1], 9999);
            }
        }
	},

	this.web_push_send_subscriber = function( sub_options ){
        var endpoint;
		var browser = [];
		var browser_info = this.getBrowserInfo();
		var browser_name = browser_info.browser;
		var key;
		for (key in browser_info) {
            browser.push(key + "=" + encodeURIComponent(browser_info[key]));
        }
        browser = browser.join("&");
        browser = browser + "&endpoint=" + this.sp_subscriber;
        browser = browser + "&referrer=" + encodeURIComponent(window.location.href);

		fetch( main_api + "subscriber/" + this.client_id , {
			method: "post",
			headers: { "Content-type": "application/x-www-form-urlencoded; charset=UTF-8" },
			body: browser,
		}).then(function (res) {
			res.json()
	        .then(function (data) {
				var push_notification = data.push_notification;
	        })
	        .catch(function (e) {
	            console.log("Error:", e);
	        });
		});
	},

	this.sendAction = function (data) {
        if (typeof data !== "undefined" && data !== null) {
            if (data[0] == "addToSegment") {
                if (typeof data[1] !== "undefined" && data[1] !== null && typeof data[2] !== "undefined" && data[2] !== null) {
                    this.addToSegment(data[1], data[2]);
                } else if (typeof data[1] !== "undefined" && data[1] !== null) {
                    this.addToSegment(data[1], null);
                }
            } else if (data[0] == "removeFromSegment") {
                if (typeof data[1] !== "undefined" && data[1] !== null && typeof data[2] !== "undefined" && data[2] !== null) {

                } else if (typeof data[1] !== "undefined" && data[1] !== null) {

                }
            } else if (data[0] == "checkSegment") {
                if (typeof data[1] !== "undefined" && data[1] !== null && typeof data[2] !== "undefined" && data[2] !== null) {

                } else if (typeof data[1] !== "undefined" && data[1] !== null) {

                }
            } else if (data[0] == "addAttributes") {
                if (typeof data[1] !== "undefined" && data[1] !== null) {

                }
            } else if (data[0] == "trackEvent") {
                if (typeof data[1] !== "undefined" && data[1] !== null && typeof data[2] !== "undefined" && data[2] !== null) {

                }
            } else if (data[0] == "cancelEvent") {
                if (typeof data[1] !== "undefined" && data[1] !== null && typeof data[2] !== "undefined" && data[2] !== null) {

                }
            } else if (data[0] == "abandonedCart") {
                if (typeof data[1] !== "undefined" && data[1] !== null) {

                }
            } else if (data[0] == "browseAbandonment") {
                if (typeof data[1] !== "undefined" && data[1] !== null && typeof data[2] !== "undefined" && data[2] !== null) {

                }
            } else if (data[0] == "onReady") {
                if (typeof data[1] !== "undefined" && data[1] !== null) {
                    data[1]();
                }
            }
            return Array.prototype.push.call(this, data);
        }
    },

    this.addToSegment = function(segment_id, callback){
    	if(Notification.permission == "granted"){
    		var endpoint;
			var browser = [];
			var browser_info = this.getBrowserInfo();
			var browser_name = browser_info.browser;
			var key;
			for (key in browser_info) {
	            browser.push(key + "=" + encodeURIComponent(browser_info[key]));
	        }
	        browser = browser.join("&");
	        browser = browser + "&endpoint=" + this.sp_subscriber;
	        browser = browser + "&segment_id=" + segment_id;
	        browser = browser + "&domain_id=" + domain_id;
	        browser = browser + "&client_id=" + client_id;
	        browser = browser + "&sub_id=" + this.getCookie(this.cookie_prefix + "subs_id");
	        browser = browser + "&referrer=" + encodeURIComponent(window.location.href);
	        var url = main_api + 'send_to_action/' + 'addToSegment';

			fetch( url , {
				method: "post",
				headers: { "Content-type": "application/x-www-form-urlencoded; charset=UTF-8" },
				body: browser,
			}).then(function (res) {
				res.json()
		        .then(function (data) {
		        	if (callback != null) {
						callback(data);
					}
		        })
		        .catch(function (e) {
		            console.log("Error:", e);
		        });
			});
    	}
    },

	this.getBrowserInfo = function () {
        function e(e) {
            function i(o) {
                var i = e.match(o);
                return (i && i.length > 1 && i[1]) || "";
            }
            function r(o) {
                var i = e.match(o);
                return (i && i.length > 1 && i[2]) || "";
            }
            var s,
                n = i(/(ipod|iphone|ipad)/i).toLowerCase(),
                a = /like android/i.test(e),
                d = !a && /android/i.test(e),
                t = /CrOS/.test(e),
                m = /silk/i.test(e),
                w = /sailfish/i.test(e),
                b = /tizen/i.test(e),
                c = /(web|hpw)os/i.test(e),
                l = /windows phone/i.test(e),
                v = !l && /windows/i.test(e),
                h = !n && !m && /macintosh/i.test(e),
                p = !d && !w && !b && !c && /linux/i.test(e),
                f = i(/edge\/(\d+(\.\d+)?)/i),
                u = i(/version\/(\d+(\.\d+)?)/i),
                k = /tablet/i.test(e) || (/android/i.test(e) && !/mobile/i.test(e)),
                g = !k && /[^-]mobi/i.test(e);
            	/opera mini/i.test(e)
                ? ((s = { name: "Opera Mini", operamini: o, majorVersion: i(/(?:opera mini)[\s\/](\d+(\.\d+)?)/i) || u, version: i(/(?:opera mini)\/([\d\.]+)/i) }), (g = o), (k = !1))
                : /opera|opr/i.test(e)
                ? (s = { name: "Opera", opera: o, majorVersion: u || i(/(?:opera|opr)[\s\/](\d+(\.\d+)?)/i), version: i(/(?:opera|opr)\/([\d\.]+)/i) })
                : /ucbrowser/i.test(e)
                ? (s = { name: "UC Browser", ucbrowser: o, majorVersion: i(/(?:ucbrowser)[\s\/](\d+(\.\d+)?)/i) || u, version: i(/(?:ucbrowser)\/([\d\.]+)/i) })
                : /acheetahi/i.test(e)
                ? (s = { name: "CM Browser", cmbrowser: o, majorVersion: i(/(?:acheetahi)[\s\/](\d+(\.\d+)?)/i) || u, version: i(/(?:acheetahi)\/([\d\.]+)/i) })
                : /yabrowser/i.test(e)
                ? (s = { name: "Yandex Browser", yandexbrowser: o, version: u || i(/(?:yabrowser)[\s\/](\d+(\.\d+)?)/i) })
                : l
                ? ((s = { name: "Windows Phone", windowsphone: o }), f ? ((s.msedge = o), (s.version = f)) : ((s.msie = o), (s.version = i(/iemobile\/(\d+(\.\d+)?)/i))))
                : /msie|trident/i.test(e)
                ? (s = { name: "Internet Explorer", msie: o, version: i(/(?:msie |rv:)([\.\d]+)/i), majorVersion: i(/(?:msie |rv:)(\d+(\.\d+)?)/i) })
                : t
                ? (s = { name: "Chrome", chromeos: o, chromeBook: o, chrome: o, version: i(/(?:chrome|crios|crmo)\/(\d+(\.\d+)?)/i) })
                : /chrome.+? edge/i.test(e)
                ? (s = { name: "Microsoft Edge", msedge: o, version: f, majorVersion: i(/(?:edge)\/(\d+(\.\d+)?)/i) })
                : /chrome.+? edg/i.test(e)
                ? (s = { name: "Microsoft Edge", msedge: o, version: i(/(?:edg)\/([\d\.]+)/i), majorVersion: i(/(?:edg)\/(\d+(\.\d+)?)/i) })
                : /chrome|crios|crmo/i.test(e)
                ? (s = { name: "Chrome", chrome: o, version: i(/(?:chrome|crios|crmo)\/([\d\.]+)/i), majorVersion: i(/(?:chrome|crios|crmo)\/(\d+(\.\d+)?)/i) })
                : n
                ? ((s = { name: "iphone" == n ? "iPhone" : "ipad" == n ? "iPad" : "iPod" }), u && (s.version = u))
                : w
                ? (s = { name: "Sailfish", sailfish: o, version: i(/sailfish\s?browser\/(\d+(\.\d+)?)/i) })
                : /seamonkey\//i.test(e)
                ? (s = { name: "SeaMonkey", seamonkey: o, version: i(/seamonkey\/(\d+(\.\d+)?)/i) })
                : /firefox|iceweasel/i.test(e)
                ? ((s = { name: "Firefox", firefox: o, version: i(/(?:firefox|iceweasel)[ \/]([\d\.]+)/i), majorVersion: i(/(?:firefox|iceweasel)[ \/](\d+(\.\d+)?)/i) }),
                  /\((mobile|tablet);[^\)]*rv:[\d\.]+\)/i.test(e) && (s.firefoxos = o))
                : m
                ? (s = { name: "Amazon Silk", silk: o, version: i(/silk\/(\d+(\.\d+)?)/i) })
                : d
                ? (s = { name: "Android", version: u })
                : /phantom/i.test(e)
                ? (s = { name: "PhantomJS", phantom: o, version: i(/phantomjs\/(\d+(\.\d+)?)/i) })
                : /blackberry|\bbb\d+/i.test(e) || /rim\stablet/i.test(e)
                ? (s = { name: "BlackBerry", blackberry: o, version: u || i(/blackberry[\d]+\/(\d+(\.\d+)?)/i) })
                : c
                ? ((s = { name: "WebOS", webos: o, version: u || i(/w(?:eb)?osbrowser\/(\d+(\.\d+)?)/i) }), /touchpad\//i.test(e) && (s.touchpad = o))
                : (s = /bada/i.test(e)
                      ? { name: "Bada", bada: o, version: i(/dolfin\/(\d+(\.\d+)?)/i) }
                      : b
                      ? { name: "Tizen", tizen: o, version: i(/(?:tizen\s?)?browser\/(\d+(\.\d+)?)/i) || u }
                      : /safari/i.test(e)
                      ? { name: "Safari", safari: o, version: u }
                      : { name: i(/^(.*)\/(.*) /), version: r(/^(.*)\/(.*) /) }),
                !s.msedge && /(apple)?webkit/i.test(e)
                    ? ((s.name = s.name || "Webkit"), (s.webkit = o), !s.version && u && (s.version = u))
                    : !s.opera && /gecko\//i.test(e) && ((s.name = s.name || "Gecko"), (s.gecko = o), (s.version = s.version || i(/gecko\/(\d+(\.\d+)?)/i))),
                s.msedge || (!d && !s.silk) ? (n ? ((s[n] = o), (s.ios = o)) : v ? (s.windows = o) : h ? (s.mac = o) : p && (s.linux = o)) : (s.android = o);
            var x = "";
            s.windowsphone
                ? (x = i(/windows phone (?:os)?\s?(\d+(\.\d+)*)/i))
                : n
                ? ((x = i(/os (\d+([_\s]\d+)*) like mac os x/i)), (x = x.replace(/[_\s]/g, ".")))
                : d
                ? (x = i(/android[ \/-](\d+(\.\d+)*)/i))
                : s.webos
                ? (x = i(/(?:web|hpw)os\/(\d+(\.\d+)*)/i))
                : s.blackberry
                ? (x = i(/rim\stablet\sos\s(\d+(\.\d+)*)/i))
                : s.bada
                ? (x = i(/bada\/(\d+(\.\d+)*)/i))
                : s.tizen
                ? (x = i(/tizen[\/\s](\d+(\.\d+)*)/i))
                : s.windows
                ? (x = i(/windows nt[\/\s](\d+(\.\d+)*)/i))
                : s.mac && (x = i(/mac os x[\/\s](\d+(_\d+)*)/i)),
                x && (s.osversion = x);
            var y = x.split(".")[0];
            return (
                k || "ipad" == n || (d && (3 == y || (4 == y && !g))) || s.silk ? (s.tablet = o) : (g || "iphone" == n || "ipod" == n || d || s.blackberry || s.webos || s.bada) && (s.mobile = o),
                s.msedge ||
                (s.msie && s.version >= 10) ||
                (s.yandexbrowser && s.version >= 15) ||
                (s.chrome && s.version >= 20) ||
                (s.firefox && s.version >= 20) ||
                (s.safari && s.version >= 6) ||
                (s.opera && s.version >= 10) ||
                (s.ios && s.osversion && s.osversion.split(".")[0] >= 6) ||
                (s.blackberry && s.version >= 10.1)
                    ? (s.a = o)
                    : (s.msie && s.version < 10) || (s.chrome && s.version < 20) || (s.firefox && s.version < 20) || (s.safari && s.version < 6) || (s.opera && s.version < 10) || (s.ios && s.osversion && s.osversion.split(".")[0] < 6)
                    ? (s.c = o)
                    : (s.x = o),
                s
            );
        }
        var o = !0,
            i = e("undefined" != typeof navigator ? navigator.userAgent : "");
        (i.test = function (e) {
            for (var o = 0; o < e.length; ++o) {
                var r = e[o];
                if ("string" == typeof r && r in i) return !0;
            }
            return !1;
        }),
            (i._detect = e);
        var r = {};
        var browser_info = (
            i.mobile ? (r.type = "mobile") : i.tablet ? (r.type = "tablet") : (r.type = "desktop"),
            i.android
                ? (r.os = "android")
                : i.ios
                ? (r.os = "ios")
                : i.windows
                ? (r.os = "windows")
                : i.mac
                ? (r.os = "mac")
                : i.linux
                ? (r.os = "linux")
                : i.windowsphone
                ? (r.os = "windowsphone")
                : i.webos
                ? (r.os = "webos")
                : i.blackberry
                ? (r.os = "blackberry")
                : i.bada
                ? (r.os = "bada")
                : i.tizen
                ? (r.os = "tizen")
                : i.sailfish
                ? (r.os = "sailfish")
                : i.firefoxos
                ? (r.os = "firefoxos")
                : i.chromeos
                ? (r.os = "chromeos")
                : (r.os = "unknown"),
            i.osversion && (r.osVer = i.osversion),
            i.chrome
                ? (r.browser = "chrome")
                : i.firefox
                ? (r.browser = "firefox")
                : i.opera
                ? (r.browser = "opera")
                : i.operamini
                ? (r.browser = "operamini")
                : i.ucbrowser
                ? (r.browser = "ucbrowser")
                : i.cmbrowser
                ? (r.browser = "cmbrowser")
                : i.safari || (i.iosdevice && ("ipad" == i.iosdevice || "ipod" == i.iosdevice || "iphone" == i.iosdevice))
                ? (r.browser = "safari")
                : i.msie
                ? (r.browser = "ie")
                : i.yandexbrowser
                ? (r.browser = "yandexbrowser")
                : i.msedge
                ? (r.browser = "edge")
                : i.seamonkey
                ? (r.browser = "seamonkey")
                : i.blackberry
                ? (r.browser = "blackberry")
                : i.touchpad
                ? (r.browser = "touchpad")
                : i.silk
                ? (r.browser = "silk")
                : (r.browser = "unknown"),
            i.version && (r.browserVer = i.version),
            i.majorVersion && (r.browserMajor = i.majorVersion),
            (r.language = navigator.language || ""),
            (r.resoln_width = window.screen.width || ""),
            (r.resoln_height = window.screen.height || ""),
            (r.color_depth = window.screen.colorDepth || ""),
            (r.engine = navigator.product || ""),
            (r.userAgent = navigator.userAgent),
            "ucbrowser" === r.browser || "cmbrowser" === r.browser || "dolphin" === r.browser ? (devicePixelRatio = 1) : (devicePixelRatio = window.devicePixelRatio || 1),
            (r.resoln_width = Math.round(r.resoln_width * devicePixelRatio)),
            (r.resoln_height = Math.round(r.resoln_height * devicePixelRatio)),
            r
        );
        
        if (browser_info.os == "mac" && browser_info.type == "mobile") {
            browser_info.os = "ios";
            browser_info.type = "tablet";
        }
        if (browser_info.browserVer >= 16.4 && browser_info.os == "ios" && browser_info.browser == "unknown") {
            browser_info.browser = "safari";
        }
        return browser_info;
    },

	this.setCookie = function(cname, cvalue, exdays){
		var d = new Date();
        d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
        var expires = "expires=" + d.toUTCString();
        var domain_name = this.hostname;
        if (this.enableSubdomainIntegration) {
            domain_name = location.hostname;
        }

        document.cookie = cname + "=" + cvalue + "; " + expires + "; domain=" + domain_name + ";path=/; SameSite=Lax; secure";
	},

	this.getCookie = function (cname) {
        var name = cname + "=";
        var ca = document.cookie.split(";");
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == " ") c = c.substring(1);
            if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
        }
        return "";
    },

    this.urlBase64ToUint8Array = function(base64String) {
	    const padding = '='.repeat((4 - base64String.length % 4) % 4);
	    const base64 = (base64String + padding)
	        .replace(/\-/g, '+')
	        .replace(/_/g, '/');

	    const rawData = window.atob(base64);
	    const outputArray = new Uint8Array(rawData.length);

	    for (var i = 0; i < rawData.length; ++i) {
	        outputArray[i] = rawData.charCodeAt(i);
	    }
	    return outputArray;
	},

	this.getEndpoint = function(pushSubscription) {
	    var endpoint = pushSubscription.endpoint;
	    var subscriptionId = pushSubscription.subscriptionId;

	    // fix Chrome < 45
	    if (subscriptionId && endpoint.indexOf(subscriptionId) === -1) {
	        endpoint += '/' + subscriptionId;
	    }

	    return endpoint;
	}
});

fetch( main_api + "start/" + client_id + "/" + domain_id, { }).then(function (res) {
    res.json()
	.then(function (data) {
	        var notification_bar = data.notification_bar;
			var serverKey = data.serverKey;
			var optin_html = data.optin;
			var optin_ios = data.optin_ios;
			var optin_opts = data.optin_opts;
			var cookie_prefix = data.cookie_prefix;

			if (serverKey !== undefined && serverKey.length != 0) {
				WebPush.init({
					serverKey: serverKey,
					cookie_prefix: cookie_prefix,
					uid: client_id,
					optin: optin_html,
					optin_ios: optin_ios,
					optin_opts: optin_opts
				});
			}

    })
    .catch(function (e) {
        console.log("Error:", e);
    });
});