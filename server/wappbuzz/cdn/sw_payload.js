var last_url_id = -1;

self.addEventListener("install", function (i) {
    self.skipWaiting();
}),

self.addEventListener("push", function (event) {
    if (event.data) {
        var data = JSON.parse(event.data.text());
        var browser_info = getBrowserInfo();
        var is_firefox = browser_info["browser"] == "firefox";
        var title, options;
        title = data.title;
        var message = data.message;
        var icon = data.icon;
        var url = data.url;
        var sid = 0;
        if (data.hasOwnProperty("sid")) {
            sid = data.sid;
        }

        var timeout = 0;
        if (is_firefox) {
            timeout = Math.floor(Math.random() * 1e3 + 1);
        }

        var pid = 0;
        if (data.hasOwnProperty("pid")) {
            pid = data.pid;
        }

        var tag = data.tag;
        var requireInteraction = true;
        var image = "";
        if (typeof data.image !== "undefined") {
            image = data.image;
        }
        if (typeof callbackOnNotificationReceive === "function") {
            callbackOnNotificationReceive(title, message, url, icon, image);
        }
        if (is_firefox) {
            if (data.hasOwnProperty("requireInteraction") && data.requireInteraction == false) {
                requireInteraction = false;
            }

            var extra_data = [];
            extra_data.url = url;
            extra_data.sid = sid;
            extra_data.pid = pid;
            options = { body: message, icon: icon, tag: tag, requireInteraction: requireInteraction, data: extra_data };
        } else {
            var badge_icon = data.badge_icon;
            if (browser_info["os"] === "mac" && (browser_info["browser"] === "chrome" || browser_info["browser"] === "opera")) {
                requireInteraction = false;
            } else if (data.hasOwnProperty("requireInteraction") && data.requireInteraction == false) {
                requireInteraction = false;
            }
            var actions = [];
            var extra_data = [];
            extra_data.url = url;
            extra_data.sid = sid;
            extra_data.pid = pid;
            if (typeof data.action1 !== "undefined") {
                actions[0] = { action: "action1", title: data.action1.title };
                extra_data.action1 = data.action1.url;
            }
            if (typeof data.action2 !== "undefined") {
                actions[1] = { action: "action2", title: data.action2.title };
                extra_data.action2 = data.action2.url;
            }
            options = { body: message, icon: icon, image: image, badge: badge_icon, tag: tag, requireInteraction: requireInteraction, data: extra_data, actions: actions };
        }
        var browser = [];
        for (key in browser_info) {
            browser.push(key + "=" + encodeURIComponent(browser_info[key]));
        }
        browser = browser.join("&");
        fetch(comm_url + "delivered?" + browser + "&client_id=" + client_id + "&domain_id=" + domain_id + "&sid=" + sid + "&pid=" + pid)
            .then(function (response) {
                if (response.status !== 200) {
                    console.log("Looks like there was a problem. Status Code D: " + response.status);
                }
            })
            .catch(function (err) {});
        event.waitUntil(self.registration.showNotification(title, options));
    } else {
        event.waitUntil(
            self.registration.pushManager
                .getSubscription()
                .then(function (pushSubscription) {
                    

                    var key = pushSubscription.getKey('p256dh');
                    var token = pushSubscription.getKey('auth');

                    key = key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null;
                    token = token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null;

                    endpoint = pushSubscription.endpoint.slice(pushSubscription.endpoint.lastIndexOf("/") + 1);
                    endpoint_full = pushSubscription.endpoint;
                    var url =
                        comm_url +
                        "getNotification?t=" +
                        new Date().getTime() +
                        "&key=" +
                        encodeURIComponent(key) +
                        "&token=" +
                        encodeURIComponent(token) +
                        "&endpoint=" +
                        encodeURIComponent(endpoint) +
                        "&endpoint_full=" +
                        encodeURIComponent(endpoint_full) +
                        "&client_id=" +
                        client_id +
                        "&domain_id=" +
                        domain_id;
                    return fetch(url)
                        .then(function (response) {
                            if (response.status !== 200) {
                                console.log("Looks like there was a problem. Status Code G: " + response.status);
                                throw new Error("Looks like there was a problem. Status Code: " + response.status);
                            }
                            return response.json().then(function (data) {

                                var options;
                                var title = data.notification.title;

                                var sid = 0;
                                if (data.notification.hasOwnProperty("sid")) {
                                    sid = data.notification.sid;
                                }

                                var pid = 0;
                                if (data.notification.hasOwnProperty("pid")) {
                                    pid = data.notification.pid;
                                }
                                
                                var is_firefox = /firefox/i.test(navigator.userAgent);
                                if (is_firefox) {
                                    if (data.error || !data.notification) {
                                        throw new Error("Data not received: " + JSON.stringify(data));
                                    }

                                    var message = data.notification.message;
                                    var icon = data.notification.icon;
                                    var url = data.notification.url;
                                    var tag = data.notification.tag;
                                    var requireInteraction = true;
                                    if (data.notification.hasOwnProperty("requireInteraction") && data.notification.requireInteraction == false) {
                                        requireInteraction = false;
                                    }

                                    var extra_data = [];
                                    extra_data.url = url;
                                    extra_data.sid = sid;
                                    extra_data.pid = pid;
                                    options = { body: message, icon: icon, tag: tag, requireInteraction: requireInteraction, data: extra_data };

                                    setTimeout(function () {
                                        return self.registration.showNotification(title, options);
                                    }, timeout);

                                } else {
                                    if (data.error || !data.notification) {
                                        throw new Error("Data not received: " + JSON.stringify(data));
                                    }
                                    var message = data.notification.message;
                                    var icon = data.notification.icon;
                                    var badge_icon = data.notification.badge_icon;
                                    var url = data.notification.url;
                                    var image = "";
                                    var tag = data.notification.tag;
                                    var requireInteraction = true;
                                    if (data.notification.hasOwnProperty("requireInteraction") && data.notification.requireInteraction == false) {
                                        requireInteraction = false;
                                    }
                                    var actions = [];
                                    var extra_data = [];
                                    extra_data.url = url;
                                    extra_data.sid = sid;
                                    extra_data.pid = pid;

                                    if (data.notification.action1 && typeof data.notification.action1 !== "undefined") {
                                        actions[0] = { action: "action1", title: data.notification.action1.title };
                                        extra_data.action1 = data.notification.action1.url;
                                    }
                                    if (data.notification.action1 && typeof data.notification.action2 !== "undefined") {
                                        actions[1] = { action: "action2", title: data.notification.action2.title };
                                        extra_data.action2 = data.notification.action2.url;
                                    }
                                    if (typeof data.notification.image !== "undefined") {
                                        image = data.notification.image;
                                    }

                                    options = { body: message, icon: icon, image: image, badge: badge_icon, tag: tag, requireInteraction: requireInteraction, data: extra_data, actions: actions }

                                    return self.registration.showNotification(title, options);
                                }

                                
                            });
                        })
                        .catch(function (err) {
                            fetch(
                                comm_url +
                                    "error?txt=" +
                                    encodeURIComponent(err.toString()) +
                                    "&key=" +
                                    encodeURIComponent(key) +
                                    "&token=" +
                                    encodeURIComponent(token) +
                                    "&endpoint=" +
                                    encodeURIComponent(endpoint) +
                                    "&endpoint_full=" +
                                    encodeURIComponent(endpoint_full) +
                                    "&client_id=" +
                                    client_id +
                                    "&domain_id=" +
                                    domain_id
                            );
                        });
                })
                .catch(function (err) {
                    fetch(
                        comm_url +
                            "error.php?txt=" +
                            encodeURIComponent(err.toString()) +
                            "&key=" +
                            encodeURIComponent(key) +
                            "&token=" +
                            encodeURIComponent(token) +
                            "&endpoint=" +
                            encodeURIComponent(endpoint) +
                            "&endpoint_full=" +
                            encodeURIComponent(endpoint_full) +
                            "&client_id=" +
                            client_id +
                            "&domain_id=" +
                            domain_id
                    );
                })
        );
    }
}),

self.addEventListener("pushsubscriptionchange", function (i) {
    var e = i.oldSubscription ? i.oldSubscription : null,
        o = i.newSubscription ? i.newSubscription : null;
    if (null != e && null != o) {
        var n,
            t,
            r = getBrowserInfo().browser,
            a = e.endpoint.lastIndexOf("/");
        (n = "edge" === r ? new URL(e.endpoint).searchParams.get("token") : e.endpoint.slice(a + 1)),
            (a = o.endpoint.lastIndexOf("/")),
            (t = "edge" === r ? new URL(o.endpoint).searchParams.get("token") : o.endpoint.slice(a + 1)),
            i.waitUntil(
                fetch(
                    comm_url +
                        "pushsubscriptionchange?client_id=" +
                        client_id +
                        "&domain_id=" +
                        domain_id +
                        "&old_endpoint_url=" +
                        encodeURIComponent(n) +
                        "&new_endpoint_url=" +
                        encodeURIComponent(t) +
                        "&new_endpoint=" +
                        encodeURIComponent(JSON.stringify(o))
                )
                    .then(function (i) {
                        i.status;
                    })
                    .catch(function (i) {})
            );
    } else
        null != e &&
            i.waitUntil(
                registration.pushManager.subscribe(i.oldSubscription.options).then((i) => {
                    var o,
                        n,
                        t = getBrowserInfo().browser,
                        r = e.endpoint.lastIndexOf("/");
                    (o = "edge" === t ? new URL(e.endpoint).searchParams.get("token") : e.endpoint.slice(r + 1)),
                        (r = i.endpoint.lastIndexOf("/")),
                        (n = "edge" === t ? new URL(i.endpoint).searchParams.get("token") : i.endpoint.slice(r + 1)),
                        fetch(
                            comm_url +
                                "pushsubscriptionchange?client_id=" +
                                client_id +
                                "&domain_id=" +
                                domain_id +
                                "&old_endpoint_url=" +
                                encodeURIComponent(o) +
                                "&new_endpoint_url=" +
                                encodeURIComponent(n) +
                                "&new_endpoint=" +
                                encodeURIComponent(JSON.stringify(i))
                        )
                            .then(function (i) {
                                i.status;
                            })
                            .catch(function (i) {});
                })
            );
}),

self.addEventListener("notificationclick", function (i) {
        t = i.notification.data.sid,
        p = i.notification.data.pid,
        i.notification.close();
    var a = i.notification.data.url,
        s = 0;

    if (("action1" === i.action ? ((a = i.notification.data.action1), (s = 1)) : "action2" === i.action && ((a = i.notification.data.action2), (s = 2))));

    var d = [],
            c = getBrowserInfo();
        for (key in c) d.push(key + "=" + encodeURIComponent(c[key]));
        fetch(comm_url + "track_clicked?" + (d = d.join("&")) + "&client_id=" + client_id + "&domain_id=" + domain_id + "&clicked_on=" + s + "&sid=" + t + "&pid=" + p )
            .then(function (i) {
                200 !== i.status && console.log("Looks like there was a problem. Status Code: " + i.status);
            })
            .catch(function (i) {});
            
    i.waitUntil(
        clients.matchAll({ type: "window" }).then(function (i) {
            for (var e = 0; e < i.length; e++) {
                var o = i[e];
                if (o.url === a && "focus" in o) return o.focus();
            }
            if (clients.openWindow) return clients.openWindow(a);
        })
    );
});

self.addEventListener("activate", function (a) {
    a.waitUntil(clients.claim());
});

function getBrowserInfo() {
    return (
        !(function (i, e) {
            this[i] = e();
        })("browser_info", function () {
            function i(i) {
                function o(e) {
                    var o = i.match(e);
                    return (o && o.length > 1 && o[1]) || "";
                }
                var n,
                    t,
                    r = o(/(ipod|iphone|ipad)/i).toLowerCase(),
                    a = !/like android/i.test(i) && /android/i.test(i),
                    s = /CrOS/.test(i),
                    d = /silk/i.test(i),
                    c = /sailfish/i.test(i),
                    l = /tizen/i.test(i),
                    f = /(web|hpw)os/i.test(i),
                    u = /windows phone/i.test(i),
                    m = !u && /windows/i.test(i),
                    p = !r && !d && /macintosh/i.test(i),
                    w = !a && !c && !l && !f && /linux/i.test(i),
                    b = o(/edge\/(\d+(\.\d+)?)/i),
                    h = o(/version\/(\d+(\.\d+)?)/i),
                    v = /tablet/i.test(i) || (/android/i.test(i) && !/mobile/i.test(i)),
                    g = !v && /[^-]mobi/i.test(i);
                /opera mini/i.test(i)
                    ? ((n = { name: "Opera Mini", operamini: e, majorVersion: o(/(?:opera mini)[\s\/](\d+(\.\d+)?)/i) || h, version: o(/(?:opera mini)\/([\d\.]+)/i) }), (g = e), (v = !1))
                    : /opera|opr/i.test(i)
                    ? (n = { name: "Opera", opera: e, majorVersion: h || o(/(?:opera|opr)[\s\/](\d+(\.\d+)?)/i), version: o(/(?:opera|opr)\/([\d\.]+)/i) })
                    : /ucbrowser/i.test(i)
                    ? (n = { name: "UC Browser", ucbrowser: e, majorVersion: o(/(?:ucbrowser)[\s\/](\d+(\.\d+)?)/i) || h, version: o(/(?:ucbrowser)\/([\d\.]+)/i) })
                    : /acheetahi/i.test(i)
                    ? (n = { name: "CM Browser", cmbrowser: e, majorVersion: o(/(?:acheetahi)[\s\/](\d+(\.\d+)?)/i) || h, version: o(/(?:acheetahi)\/([\d\.]+)/i) })
                    : /yabrowser/i.test(i)
                    ? (n = { name: "Yandex Browser", yandexbrowser: e, version: h || o(/(?:yabrowser)[\s\/](\d+(\.\d+)?)/i) })
                    : u
                    ? ((n = { name: "Windows Phone", windowsphone: e }), b ? ((n.msedge = e), (n.version = b)) : ((n.msie = e), (n.version = o(/iemobile\/(\d+(\.\d+)?)/i))))
                    : /msie|trident/i.test(i)
                    ? (n = { name: "Internet Explorer", msie: e, version: o(/(?:msie |rv:)([\.\d]+)/i), majorVersion: o(/(?:msie |rv:)(\d+(\.\d+)?)/i) })
                    : s
                    ? (n = { name: "Chrome", chromeos: e, chromeBook: e, chrome: e, version: o(/(?:chrome|crios|crmo)\/(\d+(\.\d+)?)/i) })
                    : /chrome.+? edge/i.test(i)
                    ? (n = { name: "Microsoft Edge", msedge: e, version: b, majorVersion: o(/(?:edge)\/(\d+(\.\d+)?)/i) })
                    : /chrome.+? edgA/i.test(i)
                    ? (n = { name: "Microsoft Edge", msedgeA: e, majorVersion: o(/(?:edgA)\/(\d+(\.\d+)?)/i), version: o(/(?:edgA)\/([\d\.]+)/i) || b })
                    : /chrome.+? edg/i.test(i) || /edgios/i.test(i)
                    ? (n = { name: "Microsoft Edge", msedgeA: e, version: o(/(?:edgios|edg)\/([\d\.]+)/i), majorVersion: o(/(?:edgios|edg)\/(\d+(\.\d+)?)/i) })
                    : /chrome|crios|crmo/i.test(i)
                    ? (n = { name: "Chrome", chrome: e, version: o(/(?:chrome|crios|crmo)\/([\d\.]+)/i), majorVersion: o(/(?:chrome|crios|crmo)\/(\d+(\.\d+)?)/i) })
                    : c
                    ? (n = { name: "Sailfish", sailfish: e, version: o(/sailfish\s?browser\/(\d+(\.\d+)?)/i) })
                    : /seamonkey\//i.test(i)
                    ? (n = { name: "SeaMonkey", seamonkey: e, version: o(/seamonkey\/(\d+(\.\d+)?)/i) })
                    : /firefox|iceweasel|fxios/i.test(i)
                    ? ((n = { name: "Firefox", firefox: e, version: o(/(?:firefox|iceweasel|fxios)[ \/]([\d\.]+)/i), majorVersion: o(/(?:firefox|iceweasel|fxios)[ \/](\d+(\.\d+)?)/i) }),
                      /\((mobile|tablet);[^\)]*rv:[\d\.]+\)/i.test(i) && (n.firefoxos = e))
                    : d
                    ? (n = { name: "Amazon Silk", silk: e, version: o(/silk\/(\d+(\.\d+)?)/i) })
                    : a
                    ? (n = { name: "Android", version: h })
                    : /phantom/i.test(i)
                    ? (n = { name: "PhantomJS", phantom: e, version: o(/phantomjs\/(\d+(\.\d+)?)/i) })
                    : /blackberry|\bbb\d+/i.test(i) || /rim\stablet/i.test(i)
                    ? (n = { name: "BlackBerry", blackberry: e, version: h || o(/blackberry[\d]+\/(\d+(\.\d+)?)/i) })
                    : f
                    ? ((n = { name: "WebOS", webos: e, version: h || o(/w(?:eb)?osbrowser\/(\d+(\.\d+)?)/i) }), /touchpad\//i.test(i) && (n.touchpad = e))
                    : (n = /bada/i.test(i)
                          ? { name: "Bada", bada: e, version: o(/dolfin\/(\d+(\.\d+)?)/i) }
                          : l
                          ? { name: "Tizen", tizen: e, version: o(/(?:tizen\s?)?browser\/(\d+(\.\d+)?)/i) || h }
                          : /safari/i.test(i)
                          ? { name: "Safari", safari: e, version: o(/(?:version)\/([\d\.]+)/i), majorVersion: o(/(?:version)\/(\d+(\.\d+)?)/i) || h }
                          : { name: o(/^(.*)\/(.*) /), version: ((t = i.match(/^(.*)\/(.*) /)) && t.length > 1 && t[2]) || "" }),
                    !n.msedge && /(apple)?webkit/i.test(i)
                        ? ((n.name = n.name || "Webkit"), (n.webkit = e), !n.version && h && (n.version = h))
                        : !n.opera && /gecko\//i.test(i) && ((n.name = n.name || "Gecko"), (n.gecko = e), (n.version = n.version || o(/gecko\/(\d+(\.\d+)?)/i))),
                    !n.msedge && (a || n.silk) ? (n.android = e) : r ? ((n[r] = e), (n.ios = e)) : m ? (n.windows = e) : p ? (n.mac = e) : w && (n.linux = e);
                var k = "";
                n.windowsphone
                    ? (k = o(/windows phone (?:os)?\s?(\d+(\.\d+)*)/i))
                    : r
                    ? (k = (k = o(/os (\d+([_\s]\d+)*) like mac os x/i)).replace(/[_\s]/g, "."))
                    : a
                    ? (k = o(/android[ \/-](\d+(\.\d+)*)/i))
                    : n.webos
                    ? (k = o(/(?:web|hpw)os\/(\d+(\.\d+)*)/i))
                    : n.blackberry
                    ? (k = o(/rim\stablet\sos\s(\d+(\.\d+)*)/i))
                    : n.bada
                    ? (k = o(/bada\/(\d+(\.\d+)*)/i))
                    : n.tizen
                    ? (k = o(/tizen[\/\s](\d+(\.\d+)*)/i))
                    : n.windows
                    ? (k = o(/windows nt[\/\s](\d+(\.\d+)*)/i))
                    : n.mac && (k = o(/mac os x[\/\s](\d+(_\d+)*)/i)),
                    k && (n.osversion = k);
                var y = k.split(".")[0];
                return (
                    v || "ipad" == r || (a && (3 == y || (4 == y && !g))) || n.silk ? (n.tablet = e) : (g || "iphone" == r || "ipod" == r || a || n.blackberry || n.webos || n.bada) && (n.mobile = e),
                    n.msedge ||
                    (n.msie && n.version >= 10) ||
                    (n.yandexbrowser && n.version >= 15) ||
                    (n.chrome && n.version >= 20) ||
                    (n.firefox && n.version >= 20) ||
                    (n.safari && n.version >= 6) ||
                    (n.opera && n.version >= 10) ||
                    (n.ios && n.osversion && n.osversion.split(".")[0] >= 6) ||
                    (n.blackberry && n.version >= 10.1)
                        ? (n.a = e)
                        : (n.msie && n.version < 10) || (n.chrome && n.version < 20) || (n.firefox && n.version < 20) || (n.safari && n.version < 6) || (n.opera && n.version < 10) || (n.ios && n.osversion && n.osversion.split(".")[0] < 6)
                        ? (n.c = e)
                        : (n.x = e),
                    n
                );
            }
            var e = !0,
                o = i("undefined" != typeof navigator ? navigator.userAgent : "");
            (o.test = function (i) {
                for (var e = 0; e < i.length; ++e) {
                    var n = i[e];
                    if ("string" == typeof n && n in o) return !0;
                }
                return !1;
            }),
                (o._detect = i);
            var n = {};
            return (
                o.mobile ? (n.type = "mobile") : o.tablet ? (n.type = "tablet") : (n.type = "desktop"),
                o.android
                    ? (n.os = "android")
                    : o.ios
                    ? (n.os = "ios")
                    : o.windows
                    ? (n.os = "windows")
                    : o.mac
                    ? (n.os = "mac")
                    : o.linux
                    ? (n.os = "linux")
                    : o.windowsphone
                    ? (n.os = "windowsphone")
                    : o.webos
                    ? (n.os = "webos")
                    : o.blackberry
                    ? (n.os = "blackberry")
                    : o.bada
                    ? (n.os = "bada")
                    : o.tizen
                    ? (n.os = "tizen")
                    : o.sailfish
                    ? (n.os = "sailfish")
                    : o.firefoxos
                    ? (n.os = "firefoxos")
                    : o.chromeos
                    ? (n.os = "chromeos")
                    : (n.os = "unknown"),
                o.osversion && (n.osVer = o.osversion),
                o.chrome
                    ? (n.browser = "chrome")
                    : o.firefox
                    ? (n.browser = "firefox")
                    : o.opera
                    ? (n.browser = "opera")
                    : o.operamini
                    ? (n.browser = "operamini")
                    : o.ucbrowser
                    ? (n.browser = "ucbrowser")
                    : o.cmbrowser
                    ? (n.browser = "cmbrowser")
                    : o.safari || (o.iosdevice && ("ipad" == o.iosdevice || "ipod" == o.iosdevice || "iphone" == o.iosdevice))
                    ? (n.browser = "safari")
                    : o.msie
                    ? (n.browser = "ie")
                    : o.yandexbrowser
                    ? (n.browser = "yandexbrowser")
                    : o.msedgeA
                    ? (n.browser = "edge")
                    : o.msedge
                    ? (n.browser = "edge")
                    : o.seamonkey
                    ? (n.browser = "seamonkey")
                    : o.blackberry
                    ? (n.browser = "blackberry")
                    : o.touchpad
                    ? (n.browser = "touchpad")
                    : o.silk
                    ? (n.browser = "silk")
                    : (n.browser = "unknown"),
                o.version && (n.browserVer = o.version),
                o.majorVersion && (n.browserMajor = o.majorVersion),
                (n.language = navigator.language || ""),
                (n.engine = navigator.product || ""),
                (n.userAgent = navigator.userAgent),
                n
            );
        }),
        browser_info
    );
}