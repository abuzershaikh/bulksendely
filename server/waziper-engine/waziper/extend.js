/**
 * EXTEND - Extended Functionality Module
 * Provides additional features for WhatsApp automation
 *
 * This module handles:
 * - OpenAI chatbot integration
 * - Phone number validation
 * - Message processing and filtering
 * - Live chat functionality
 * - Subscriber management
 *
 * Compatible with Baileys v6.7.21 (@whiskeysockets/baileys)
 */

// Core dependencies
const mysql = require('mysql');                  // MySQL database client
const config = require("./../config.js");        // Application configuration
const common = require("./common.js");           // Common utility functions
const moment = require('moment-timezone');       // Date/time with timezone support
const Queue = require('bull');                   // Redis-based queue for background jobs
const axios = require('axios');                  // HTTP client for API requests
const fs = require('fs');                        // File system operations
const util = require('util');                    // Node.js utility functions
const ioredis = require('ioredis');              // Redis client for caching

// Promisify file operations for async/await usage
const writeFileAsync = util.promisify(fs.writeFile);
const { join } = require('path');

/**
 * Baileys v6.7.21 imports
 * These functions are used for message processing and phone validation
 *
 * - WAMessageStubType: Message stub types (e.g., CIPHERTEXT for decryption failures)
 * - getContentType: Extracts the content type from a message object
 * - jidNormalizedUser: Normalizes WhatsApp JID (phone@s.whatsapp.net format)
 * - downloadContentFromMessage: Downloads media content from messages
 */
const {
    WAMessageStubType,                           // Message stub type enum (Baileys v6.7.21)
    getContentType,                              // Get message content type (text, image, video, etc.)
    jidNormalizedUser,                           // Normalize WhatsApp JID format
    downloadContentFromMessage                   // Download media from messages (Baileys v6.7.21 compatible)
} = require('@whiskeysockets/baileys')              // Aliased to @whiskeysockets/baileys@^6.7.21

/**
 * Redis connection for caching
 * Used to cache OpenAI conversation history and other temporary data
 */
var redis_ = null;
if (config.redis) {
    redis_ = new ioredis(config.redis);
}

/**
 * Cache Layer - Redis abstraction for key-value storage
 *
 * Provides simple get/set interface for caching:
 * - OpenAI conversation history (for chatbot context)
 * - Temporary data with expiration
 * - Session state
 */
var cacheLayer = {
    /**
     * Set a value in Redis cache
     * @param {string} key - Cache key
     * @param {string} value - Value to store
     * @param {string} option - Optional parameter (e.g., 'EX' for expiration)
     * @param {number} optionValue - Optional value (e.g., seconds for expiration)
     */
    set: async (key, value, option, optionValue) => {
        if (!redis_) return false;
        const setPromisefy = util.promisify(redis_.set).bind(redis_);
        if (option !== undefined && optionValue !== undefined) {
            return setPromisefy(key, value, option, optionValue);
        }
        return setPromisefy(key, value);
    },

    /**
     * Get a value from Redis cache
     * @param {string} key - Cache key
     * @returns {Promise<string>} - Cached value or null
     */
    get: (key) => {
        if (!redis_) return null;
        const getPromisefy = util.promisify(redis_.get).bind(redis_);
        return getPromisefy(key);
    }
}

/**
 * OpenAI Integration
 * Used for AI-powered chatbot responses
 */
const { OpenAI } = require('openai');

/**
 * Proxy Configuration for OpenAI API
 * Allows routing OpenAI requests through a proxy server
 * Useful for regions with restricted access or for load balancing
 */
const proxyUrl = config.proxy_openai ?? '';

if (proxyUrl != '') {
    var { HttpsProxyAgent } = require('https-proxy-agent');
    var proxyAgent = new HttpsProxyAgent(proxyUrl);
} else {
    var proxyAgent = null;
}



let OpenAi_History_Chat = {}
let OpenAi_Chats_Ids = {}

var lang = [
    'af', 'ar', 'ar-dz', 'ar-kw', 'ar-ly', 'ar-ma', 'ar-sa', 'ar-tn', 'az',
    'be', 'bg', 'bm', 'bn', 'bn-bd', 'bo', 'br', 'bs',
    'ca', 'cs', 'cv', 'cy',
    'da', 'de', 'de-at', 'de-ch', 'dv', 'el',
    'en-au', 'en-ca', 'en-gb', 'en-ie', 'en-il', 'en-in', 'en-nz', 'en-sg', 'eo', 'es', 'es-do', 'es-mx', 'es-us', 'et', 'eu',
    'fa', 'fi', 'fil', 'fo', 'fr', 'fr-ca', 'fr-ch', 'fy',
    'ga', 'gd', 'gl', 'gom-deva', 'gom-latn', 'gu',
    'he', 'hi', 'hr', 'hu', 'hy-am',
    'id', 'is', 'it', 'it-ch',
    'ja', 'jv',
    'ka', 'kk', 'km', 'kn', 'ko', 'ku', 'ky',
    'lb', 'lo', 'lt', 'lv',
    'me', 'mi', 'mk', 'ml', 'mn', 'mr', 'ms', 'ms-my', 'mt', 'my',
    'nb', 'ne', 'nl', 'nl-be', 'nn',
    'oc-lnc',
    'pa-in', 'pl', 'pt', 'pt-br',
    'ro', 'ru',
    'sd', 'se', 'si', 'sk', 'sl', 'sq', 'sr', 'sr-cyrl', 'ss', 'sv', 'sw',
    'ta', 'te', 'tet', 'tg', 'th', 'tk', 'tl-ph', 'tlh', 'tr', 'tzl', 'tzm', 'tzm-latn',
    'ug-cn', 'uk', 'ur', 'uz', 'uz-latn',
    'vi',
    'x-pseudo',
    'yo',
    'zh-cn', 'zh-hk', 'zh-mo', 'zh-tw'
];

lang.forEach(loc => {
    require(`./../node_modules/moment/locale/${loc}.js`);
});
moment.locale('en');

const db = common.db_connect;// mysql.createPool(config.database);




const Extend = {

    getDescendantProp: (obj, desc) => {
        var arr = desc.split(".");
        while (arr.length && obj) {
            var comp = arr.shift();
            var match = new RegExp("(.+)\\[([0-9]*)\\]").exec(comp);
            if ((match !== null) && (match.length == 3)) {
                var arrayData = { arrName: match[1], arrIndex: match[2] };
                if (obj[arrayData.arrName] != undefined) {
                    obj = obj[arrayData.arrName][arrayData.arrIndex];
                } else {
                    obj = undefined;
                    break;
                }
            } else {
                obj = obj[comp]
            }
        }
        return obj;
    },

    getSubscriber: async function (waziper, receiber, instance_id = '', contact_data = { name: '', number: '', profilePicUrl: '', isGroup: false, extraInfo: [] }, official_api = false) {

        if (!official_api) {
            var instance = await common.get_instance(instance_id);

            if (!instance) {
                return false;
            }
            var team_id = instance.team_id;
        } else {
            var account = await common.db_get("sp_accounts", [{ token: instance_id }]);
            if (!account) {
                return false;
            }
            var team_id = account.team_id;
        }

        var chat_id = receiber.key.remoteJid;
        var objSubscriber = await new Promise(async (resolve, reject) => {
            var nameQuery = "SELECT * FROM `sp_whatsapp_subscriber` where chatid = '" + chat_id + "' and team_id = '" + team_id + "' and instance_id = '" + instance_id + "'";

            db.query(nameQuery, (a, subscriber_res) => {
                if (subscriber_res && subscriber_res.length > 0) {

                    subscriber_res = subscriber_res[0];
                    if (subscriber_res.status == 0) {
                        common.db_update('sp_whatsapp_subscriber', [{ status: 1, kanban_group: '' }, { id: subscriber_res.id }])
                    }
                    resolve({
                        id: subscriber_res.id,
                        team_id: subscriber_res.team_id,
                        chatid: subscriber_res.chatid,
                        last_chatbot_id: subscriber_res.last_chatbot_id,
                        status: subscriber_res.status,
                        data: JSON.parse(subscriber_res.data),
                        last_response: subscriber_res.last_response,
                        instance_id: subscriber_res.instance_id,
                        last_response_time: subscriber_res.last_response_time,
                        tags: subscriber_res.tags,
                        kanban_group: subscriber_res.kanban_group,
                        enabled_chatbot: subscriber_res.enabled_chatbot,
                        contact_data: JSON.parse(subscriber_res.contact_data),
                        unreadMessages: subscriber_res.unreadMessages,
                        lastMessage: subscriber_res.lastMessage,
                        lastMessageTime: subscriber_res.lastMessageTime
                    });

                } else {

                    var createdData = moment();
                    var newSubscriberData = {
                        team_id: team_id,
                        chatid: chat_id,
                        data: JSON.stringify({ created: createdData }),
                        status: 1,
                        instance_id: instance_id,
                        last_response_time: receiber["messageTimestamp"],
                        tags: '',
                        kanban_group: '',
                        enabled_chatbot: 1,
                        contact_data: JSON.stringify(contact_data),
                        unreadMessages: 0,
                        lastMessage: '',
                        lastMessageTime: 0
                    }
                    db.query("INSERT INTO sp_whatsapp_subscriber SET ?", newSubscriberData, async (a, newSubscriberSuccess) => {
                        if (a) { console.error(a) }
                        try {
                            if (newSubscriberSuccess) {
                                var webhookData = {
                                    suscriptorId: newSubscriberSuccess.insertId,
                                    chatid: chat_id,
                                    instance_id: instance_id,
                                    newData: {
                                        inputName: 'created',
                                        value: createdData
                                    },
                                    data: { created: createdData }
                                }

                                await waziper.webhook(instance_id, { event: "new subscriber", data: webhookData });
                            }
                        } catch (error) {
                            console.error('chk phone webhook error:', error);
                        }

                        resolve({
                            id: newSubscriberSuccess.insertId,
                            team_id: team_id,
                            chatid: chat_id,
                            data: { created: createdData },
                            status: 1,
                            instance_id: instance_id,
                            last_response_time: receiber["messageTimestamp"],
                            tags: '',
                            kanban_group: '',
                            enabled_chatbot: 1,
                            contact_data: contact_data,
                            unreadMessages: 0,
                            lastMessage: '',
                            lastMessageTime: 0
                        })
                    });

                }
            })

        });

        return objSubscriber;
    },

    updateSubscriberContactData: async function (subscriptor, contact_data = { name: '', number: '', profilePicUrl: '', isGroup: false, extraInfo: [] }) {
        return new Promise((resolve, reject) => {
            var data = {
                contact_data: JSON.stringify(contact_data)
            }
            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                subscriptor.contact_data = contact_data;
                resolve(subscriptor);
            });
        })
    },

    updateSubscriberMessages: async function (subscriptor, unreadMessages, lastMessage, lastMessageTime) {
        return new Promise((resolve, reject) => {
            var data = {
                unreadMessages: unreadMessages,
                lastMessage: lastMessage,
                lastMessageTime: lastMessageTime
            }
            db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                subscriptor.unreadMessages = unreadMessages;
                subscriptor.lastMessage = lastMessage;
                subscriptor.lastMessageTime = lastMessageTime;
                resolve(subscriptor);
            });
        })
    },

    updateSubscriber: async function (waziper, subscriptor, message_text, instance_id, user_type, message_obj, chatbot = null) {
        return new Promise((resolve, reject) => {
            if (true) {
                var sData = subscriptor.data;
                if (chatbot != null) {
                    if (chatbot.save_data == 2) {
                        var data = {
                            last_chatbot_id: chatbot.id,
                            last_response: message_text,
                            data: JSON.stringify(sData)
                        }
                        db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                            resolve(true);
                        });
                    } else {
                        resolve(true);
                    }
                } else {
                    db.query("SELECT * FROM sp_whatsapp_chatbot WHERE id = '" + subscriptor.last_chatbot_id + "'", function (a, bot) {
                        if (bot && bot.length > 0) {
                            bot = bot[0];
                            if (bot.save_data == 2) {
                                //console.log('save data',subscriptor.id, subscriptor.last_chatbot_id, instance_id, message_text);
                                sData[bot.inputname] = message_text;
                                var data = {
                                    last_chatbot_id: null,
                                    last_response: message_text,
                                    data: JSON.stringify(sData)
                                }
                                db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor.id + "'", data, async (a, b) => {
                                    if (a) console.error(a);
                                    var jid_ = subscriptor.chatid;

                                    var webhookData = {
                                        suscriptorId: subscriptor.id,
                                        chatid: subscriptor.chatid,
                                        newData: {
                                            inputName: bot.inputname,
                                            value: message_text
                                        },
                                        data: sData
                                    }

                                    waziper.webhook(instance_id, { event: "capturer", data: webhookData });

                                    if (bot.nextBot != null && bot.nextBot != '') {

                                        message_obj['message'] = {};
                                        message_obj['message']['conversation'] = bot.nextBot;

                                        //console.log('nextbot save data',subscriptor.id, subscriptor.last_chatbot_id, instance_id, message_obj);

                                        resolve(false);
                                        waziper.chatbot(instance_id, user_type, message_obj)
                                    } else {
                                        resolve(false);
                                    }

                                });
                            } else {
                                resolve(true);
                            }
                        } else {
                            resolve(true);
                        }
                    });
                }
            } else {
                resolve(false);
            }
        });
    },

    query: async function (query, row = false) {
        var res = await new Promise(async (resolve, reject) => {
            db.query(query, (err, res) => {
                return resolve(res, true);
            });
        });
        return Extend.row(res, row);
    },

    update: async function (table, data) {
        var res = await new Promise(async (resolve, reject) => {
            db.query("UPDATE " + table + " SET ? WHERE ?", data, (err, res) => {
                return resolve(res, true);
            });
        });

        return res;
    },

    row: async (res, row) => {
        if (res != undefined && res.length > 0) {
            if (row || row == undefined) {
                return res[0];
            } else {
                return res;
            }
        }
        return false;
    },

    getAccountTimezone: async (instance_id) => {
        var query = "SELECT u.timezone FROM sp_accounts a LEFT JOIN sp_team t on t.id = a.team_id LEFT JOIN sp_users u on u.id = t.owner where a.token = ?";
        var res = await new Promise(async (resolve, reject) => {
            db.query(query, [instance_id], (err, res) => {
                return resolve(res, true);
            });
        });
        return Extend.row(res);
    },

    getGreet: async (timezone, input) => {
        var current_hour = -1;
        if (timezone) {
            var now = moment(), greet = '', greets = input.split('|'), defaults = ['', 'good morning', 'good afternoon', 'good evening']
            for (let index = greets.length; index < 4; index++) { greets.push(defaults[index]); }
            current_hour = now.tz(timezone.timezone).format('HH');
            current_hour = parseInt(current_hour);
            switch (true) {
                case current_hour >= 12 && current_hour <= 18:
                    greet = greets[2];
                    break;
                case current_hour >= 19 && current_hour <= 23:
                    greet = greets[3];
                    break;
                default:
                    greet = greets[1];
                    break;
            }
            return greet;
        } else {
            return '';
        }
    },

    disableBotKeyword: async (waziper, instance_id, user_type, message) => {
        var ai_item = await common.db_get('sp_whatsapp_ai', [{ instance_id: instance_id }]);
        var subscriptor_ = await Extend.getSubscriber(waziper, message, instance_id);
        var content = false;

        if (message.message?.ephemeralMessage) {
            message.message = message.message.ephemeralMessage.message;
        }

        if (message.message?.buttonsResponseMessage != undefined) {
            content = message.message.buttonsResponseMessage.selectedDisplayText;
        } else if (message.message?.templateButtonReplyMessage != undefined) {
            content = message.message.templateButtonReplyMessage.selectedDisplayText;
        } else if (message.message?.listResponseMessage != undefined) {
            content = message.message.listResponseMessage.title + " " + message.message.listResponseMessage.description;
        } else if (typeof message.message?.extendedTextMessage != "undefined" && message.message.extendedTextMessage != null) {
            content = message.message.extendedTextMessage.text;
        } else if (typeof message.message?.imageMessage != "undefined" && message.message.imageMessage != null) {
            content = message.message.imageMessage.caption;
        } else if (typeof message.message?.videoMessage != "undefined" && message.message.videoMessage != null) {
            content = message.message.videoMessage.caption;
        } else if (typeof message.message?.conversation != "undefined") {
            content = message.message.conversation;
        }

        if (!ai_item) {
            ai_item = {};
        }

        ai_item.key_disable = ai_item.key_disable != null && ai_item.key_disable != undefined && ai_item.key_disable != '' ? ai_item.key_disable : 'Disable';
        ai_item.key_enable = ai_item.key_enable != null && ai_item.key_enable != undefined && ai_item.key_enable != '' ? ai_item.key_enable : 'Enable';

        if (content && typeof content === 'string') {
            if (content.toLowerCase() === ai_item.key_disable.toLowerCase() || content.toLowerCase() === ai_item.key_enable.toLowerCase()) {
                var val = content.toLowerCase() === ai_item.key_disable.toLowerCase() ? '0' : '1';
                var action = val == '0' ? 'DISABLED' : 'ENABLED';
                var data = {
                    enabled_chatbot: val
                }

                console.log(`[Chatbot] ${action} for subscriber ${subscriptor_.chatid} (instance: ${instance_id}) via keyword "${content}"`);
                
                db.query("UPDATE `sp_whatsapp_subscriber` SET ? WHERE id = '" + subscriptor_.id + "'", data, async (a, b) => {
                    if (a) {
                        console.error(`[Chatbot] Error updating enabled_chatbot for subscriber ${subscriptor_.id}:`, a);
                    }
                });
            }
        }
    },

    getNowLocale: (prop, timeZone, defaultFormat = 'LLL', defaultLanguaje = 'en') => {
        var now = moment(), format = prop.split('|'), defaults = ['', defaultLanguaje, defaultFormat];
        for (let index = format.length; index < 3; index++) { format.push(defaults[index]); }
        now.locale(format[1]);
        return now.tz(timeZone).format(format[2])
    },

    sendPresence: async (instance, chat_id, item) => {
        if (instance) {
            var type = parseInt(item.presenceType), time = parseInt(item.presenceTime);

            if (type != 0 && time > 0) {
                await instance.presenceSubscribe(chat_id)
                await new Promise(u => setTimeout(u, 500));
                await instance.sendPresenceUpdate(type == 1 ? 'composing' : 'recording', chat_id)
                await new Promise(u => setTimeout(u, time * 1000 - 500));
                await instance.sendPresenceUpdate('paused', chat_id)
            }
        }
    },

    nextBot: async (result, item, message, instance_id, user_type, WAZIPER) => {
        if (true) {
            if (item.nextBot != '') {
                message['message'] = {};
                message['message']['conversation'] = item.nextBot;
                WAZIPER.chatbot(instance_id, user_type, message);
            }
        }
    },

    toLowerKeys: function (obj) {
        return Object.keys(obj).reduce((pValue, cValue) => {
            pValue[cValue.toLowerCase()] = obj[cValue];
            return pValue;
        }, {});
    },

    convert_data: function (params, caption, isUrl = false) {

        var params = Extend.toLowerKeys(params);
        var regexExp = /\[(.*?)\]/;

        var oldValue;
        var counterLimit = 0;
        while (oldValue = caption["match"](regexExp)) {
            oldValue = oldValue[0];
            var prop = oldValue["substring"](1, oldValue.length - 1);
            var val = Extend.getDescendantProp(params, prop);

            if (val != undefined) {
                if (isUrl) {
                    caption = caption["replace"](oldValue, encodeURIComponent(val));
                } else {
                    caption = caption["replace"](oldValue, val);
                }
            } else {
                caption = caption["replace"](oldValue, '');
            }

            counterLimit++;
            if (counterLimit == 150) {
                break;
            }

        }
        return caption;
    },

    common_data: async (waziper, instance, instance_id, item, message, processText, withPresense = false, isUrl = false) => {

        var timezone = await Extend.getAccountTimezone(instance_id);

        var commonProps = {
            user_phone: common.get_phone(message?.key?.remoteJid ?? ''),
            wa_name: message?.pushName ?? '',
            me_phone: common.get_phone(instance?.user?.id ?? ''),
            me_wa_name: instance?.user?.name ?? '',
        }

        var regexExp = /\[(.*?)\]/;
        var oldValue;
        var counterLimit = 0;

        if (message) {
            var subscriber_ = await Extend.getSubscriber(waziper, message, instance_id);
            if (subscriber_) {
                var data = subscriber_.data;
                commonProps = { ...commonProps, ...data }
            }
        }


        if (item && item.get_api_data == 2 && item.api_url != '') {

            try {
                // obtengo los parametros y los reemplazo e la url
                var url = Extend.convert_data(commonProps, item.api_url, true);

                // obtengo el objeto de configuracion de la api
                var api_config = JSON.parse(item.api_config);
                var api_data = {};
                var api_headers = {};


                if (api_config.body && api_config.body?.length > 0) {
                    api_config.body.forEach(element => {
                        api_data[element.name] = Extend.convert_data(commonProps, element.value, false);
                    });
                }

                if (api_config.header && api_config.header?.length > 0) {
                    api_config.header.forEach(element => {
                        api_headers[element.name] = Extend.convert_data(commonProps, element.value, false);
                    });
                }

                var axios_config = {
                    method: api_config.method,
                    url: url,
                    timeout: 120000,
                    //data: api_data,
                    headers: api_headers
                };

                // Si el mÃ©todo es GET, agregar los datos como parÃ¡metros de la URL
                if (api_config.method === 'get') {
                    axios_config.params = api_data;
                    axios_config.data = api_data;
                } else {
                    axios_config.data = api_data;
                }

                var dt = await axios(axios_config);

                // Verificar si dt.data es un array
                if (Array.isArray(dt.data)) {
                    // Agregar una propiedad 'items' a commonProps con el array
                    commonProps = { ...commonProps, items: dt.data };
                } else {
                    // Si no es un array, agregar directamente a commonProps
                    commonProps = { ...commonProps, ...dt.data };
                }


            } catch (error) {
                console.error('fail apirest general', error)
            }

        }

        while (oldValue = processText["match"](regexExp)) {
            oldValue = oldValue[0];
            var prop = oldValue["substring"](1, oldValue.length - 1);
            if (prop.includes('greet')) {
                var val = await Extend.getGreet(timezone, prop);
            } else if (prop.includes('time')) {
                var val = Extend.getNowLocale(prop, timezone.timezone, 'LT');
            } else if (prop.includes('date')) {
                var val = Extend.getNowLocale(prop, timezone.timezone, 'll');
            } else if (prop.includes('now_format')) {
                var val = Extend.getNowLocale(prop, timezone.timezone);
            } else {
                var val = Extend.getDescendantProp(commonProps, prop)
            }


            if (val) {
                if (isUrl) {
                    processText = processText.replace(oldValue, encodeURIComponent(val));
                } else {
                    processText = processText.replace(oldValue, val);
                }
            } else {
                processText = processText.replace(oldValue, '');
            }


            counterLimit++;
            if (counterLimit == 150) {
                break;
            }
        }


        return processText;
    },

    /*check_phone: function (instance, contactToSend, phoneStatus = 0, cloud = true) {
    return new Promise(async (res, rej) => {
        if (instance || cloud) {
            if (`${contactToSend}`.includes("g.us") || `${contactToSend}`.includes("status") || phoneStatus === 1) {
                res(true);
            } else if (phoneStatus === 2) {
                res(false);
            } else {
                try {
                    if (!cloud) {
                        var validPhone = await new Promise((resolve, reject) => {
                            const timeoutId = setTimeout(() => {
                                resolve([true, true]);
                            }, 10000);
                            instance["onWhatsApp"](contactToSend).then(value => {
                                clearTimeout(timeoutId);
                                resolve(value);
                            }).catch(err => {
                                clearTimeout(timeoutId);
                                reject(err);
                            });
                        });
                    } else {
                        throw new Error('trying from cloud account');
                    }
                } catch (err) {
                    var validPhone = [true, true];
                }

                if (validPhone.length > 0) {
                    res(true);
                } else {
                    res(false);
                }
            }
        } else {
            res(false);
        }
    });
}*/

    /**
     * check_phone - Validate if a phone number is registered on WhatsApp
     *
     * @param {object} e - WhatsApp socket instance (Baileys v6.7.21)
     * @param {string} a - Phone number or JID to validate
     * @param {number} t - Phone status override (0=check, 1=force valid, 2=force invalid)
     * @returns {Promise<boolean>} - True if phone is valid/registered on WhatsApp
     *
     * This function:
     * 1. Skips validation for groups (@g.us) and status broadcasts
     * 2. Uses Baileys onWhatsApp() method to check if number is registered
     * 3. Has 10-second timeout to prevent hanging
     * 4. Compatible with Baileys v6.7.21 phone validation API
     * 5. ✅ UPDATED: Supports both @s.whatsapp.net and @lid individual chat formats
     *
     * Note: Variable names are minified (e, a, t, s, n, r) - this appears to be
     * obfuscated code but is functionally correct for Baileys v6.7.21
     */
    check_phone: function (e, a, t = 0) {
    return new Promise(async (s, n) => {
      // ✅ UPDATED: Skip validation for groups (both @g.us and g.us), status broadcasts, or forced valid
      // Individual chats (@s.whatsapp.net or @lid) will proceed to validation
      if (("" + a).includes("g.us") || ("" + a).includes("@g.us") || ("" + a).includes("status") || 1 == t)
        s(!0);  // Return true (valid)
      else if (2 == t) s(!1);  // Forced invalid
      else {
        try {
          // Call Baileys onWhatsApp() method with 10-second timeout
          var r = await new Promise((t, s) => {
            const n = setTimeout(() => {
              t([!0, !0]);  // Timeout: assume valid
            }, 1e4);  // 10000ms = 10 seconds
            e.onWhatsApp(a).then((e) => {
              clearTimeout(n), t(e);  // Return validation result
            });
          });
        } catch (e) {
          r = [!0, !0];  // On error, assume valid
        }
        r.length > 0 ? s(!0) : s(!1);  // Resolve based on result
      }
    });
  },

    resetAi: function (instance_id) {
        console.log('restarting openai history for', instance_id);

        delete OpenAi_History_Chat[instance_id];
        delete OpenAi_Chats_Ids[instance_id];

        OpenAi_History_Chat[instance_id] = {};
        OpenAi_Chats_Ids[instance_id] = {};
    },

    /**
     * process_message - Process messages through OpenAI for chatbot responses
     *
     * @param {string} instance_id - WhatsApp instance identifier
     * @param {object} item - Chatbot configuration item from database
     * @param {string} chat_id - WhatsApp chat ID (phone@s.whatsapp.net or groupid@g.us)
     * @param {string} type - Message type ('chatbot' or 'autoresponder')
     * @param {string} content - User message content to process
     * @param {function} onFailGPTcallback - Callback function on OpenAI API failure
     * @returns {Promise<string>} - AI-generated response text
     *
     * This function:
     * 1. Retrieves OpenAI API configuration from database
     * 2. Manages conversation history for context-aware responses
     * 3. Calls OpenAI API (GPT-3.5 or GPT-4) to generate responses
     * 4. Handles conversation history limits (max 12 messages)
     * 5. Supports proxy configuration for OpenAI API access
     *
     * Compatible with OpenAI API and Baileys v6.7.21 message format
     */
    process_message: function (instance_id, item, chat_id, type, content, onFailGPTcallback = (error) => { }) {

        return new Promise(async (resolve, rejected) => {

            if (true) {

                // Check if this is a chatbot message with AI enabled
                if (type == 'chatbot' && item.use_ai) {

                    if (content) {
                        // Retrieve OpenAI configuration from database
                        var ai_item = await common.db_get('sp_whatsapp_ai', [{ instance_id: instance_id }]);

                        // Check if AI is configured (either instance-specific or default key)
                        if ((ai_item && (ai_item?.status ?? 0) == 1) || config.default_openai_key) {

                            var key = '';
                            var fix3_5 = false;  // Flag for GPT-3.5 compatibility mode

                            // Use default OpenAI key from config if available
                            if (config.default_openai_key != '') {
                                key = config.default_openai_key;
                                fix3_5 = true;  // Enable GPT-3.5 compatibility
                            }

                            // Override with instance-specific key if configured
                            if (ai_item && (ai_item?.status ?? 0) == 1) {
                                key = ai_item.apikey;
                                fix3_5 = false;
                            }

                            // Determine if using GPT-4 (supports system role)
                            var use_ai_system = false;
                            if (`${ai_item?.model}`.indexOf('gpt-4') >= 0) {
                                use_ai_system = true;
                            }

                            // Initialize OpenAI client with or without proxy
                            var openai = {};

                            if (!proxyAgent) {
                                openai = new OpenAI({ apiKey: key });
                            } else {
                                // Use proxy for regions with restricted OpenAI access
                                openai = new OpenAI({ apiKey: key, httpAgent: proxyAgent });
                            }


                            const messages_ia = [];

                            if (!(OpenAi_History_Chat[instance_id] != undefined)) {
                                OpenAi_History_Chat[instance_id] = {};
                            }

                            if (!(OpenAi_Chats_Ids[instance_id] != undefined)) {
                                OpenAi_Chats_Ids[instance_id] = {};
                            }
                            if (!(OpenAi_Chats_Ids[instance_id][chat_id] != undefined)) {
                                OpenAi_Chats_Ids[instance_id][chat_id] = item.id;
                            }



                            if (!(OpenAi_History_Chat[instance_id][chat_id] != undefined)) {
                                OpenAi_History_Chat[instance_id][chat_id] = [
                                    {
                                        role: fix3_5 && !use_ai_system ? "assistant" : "system", content: item.caption
                                    }
                                ]

                            } else {

                                if (item.id != OpenAi_Chats_Ids[instance_id][chat_id]) {
                                    const el = OpenAi_History_Chat[instance_id][chat_id].filter(e => e.content == item.caption);
                                    if (el.length <= 0 && !item.is_default) {
                                        OpenAi_History_Chat[instance_id][chat_id].push({ role: fix3_5 ? "assistant" : "system", content: item.caption });
                                    }
                                }

                                if (OpenAi_History_Chat[instance_id][chat_id].length >= 12) {
                                    var tmp = [
                                        {
                                            role: fix3_5 ? "assistant" : "system", content: item.caption
                                        }
                                    ];

                                    const result = OpenAi_History_Chat[instance_id][chat_id].slice(-10);

                                    result.forEach(item => {
                                        tmp.push(item);
                                    });

                                    OpenAi_History_Chat[instance_id][chat_id] = tmp;
                                }


                            }

                            OpenAi_History_Chat[instance_id][chat_id].forEach(item => {
                                messages_ia.push(item);
                            });

                            messages_ia.push({ role: "user", content: content });

                            var resolve_obj = {};
                            var err = 'check your apikey or your openai account';

                            for (let intent = 0; intent <= 5; intent++) {
                                try {
                                    const completion = await openai.chat.completions.create({
                                        model: fix3_5 ? "gpt-3.5-turbo" : ai_item?.model,
                                        messages: messages_ia,
                                    });

                                    OpenAi_History_Chat[instance_id][chat_id].push({ role: 'user', content: content });
                                    OpenAi_History_Chat[instance_id][chat_id].push(completion.choices[0].message);
                                    OpenAi_Chats_Ids[instance_id][chat_id] = item.id;

                                    const completion_text = completion.choices[0].message.content;
                                    resolve_obj = { new_caption: completion_text, can_continue: true };
                                    break;
                                } catch (error) {
                                    console.error('ia error intent', intent, error);
                                    err = error.message;
                                    //intent++;
                                }

                                if (intent == 5) {
                                    onFailGPTcallback(err);
                                    delete OpenAi_History_Chat[instance_id][chat_id];
                                    resolve_obj = { new_caption: '', can_continue: false };
                                }
                            }

                            resolve(resolve_obj);

                        } else {
                            console.error('ai is disabled from settings for', instance_id)
                            resolve({ new_caption: '', can_continue: false });
                        }
                    } else {
                        console.error('content is empty to send to ai', instance_id)
                        resolve({ new_caption: '', can_continue: false });
                    }
                }
            }

            resolve({ new_caption: item.caption, can_continue: true });

        })
    },

    validatePhones: async (waziper, sessions) => {


        try {
            if (true) {
                var set_progress = async function (id, val = 4) {
                    await common.db_query(`UPDATE sp_whatsapp_phone_numbers SET is_valid=${val}  WHERE id=${id}`)
                    /*db.query(`UPDATE sp_whatsapp_phone_numbers SET is_valid=${val}  WHERE id=${id}`, function (f, s) {
                        if (f) console.error(f);
                    });*/
                }
                var bulkQuery = `SELECT pn.id, pn.pid, pn.team_id, pn.phone, pn.is_valid, u.status, t.ids as team_ids FROM sp_whatsapp_phone_numbers as pn LEFT JOIN sp_team as t on t.id = pn.team_id LEFT JOIN sp_users as u on u.id = t.owner WHERE u.status = 2 AND(is_valid is null OR is_valid = 4) ORDER BY  is_valid LIMIT 50`;
                var toValidate = await Extend.query(bulkQuery);
                if (toValidate && toValidate.length > 0) {
                    for (let b_index = 0; b_index < toValidate.length; b_index++) {
                        const bulk = toValidate[b_index];
                        var bTeamIds = bulk["team_id"];
                        var bId = bulk["id"];
                        var bPhone = bulk["phone"];
                        var pId = bulk['pid'];
                        set_progress(bId, 3);
                        var queryAccount = `SELECT * FROM sp_accounts WHERE social_network = 'whatsapp' AND login_type = '2' AND status = '1' AND team_id= '${bTeamIds}'`;
                        var accounts = await Extend.query(queryAccount);
                        if (accounts && accounts.length > 0) {
                            var accounts_ids = accounts.map(u => u.id);
                            var account_id = accounts_ids[Math.floor(Math.random() * accounts_ids.length)];
                            var account = accounts.find(o => o.id == account_id);
                            var token = account.token;
                            if (sessions[token]) {
                                var newPhone = await common.check_especials(bPhone, bId);
                                var isValid = await Extend.check_phone(sessions[token], newPhone, 0);
                                set_progress(bId, isValid ? '1' : '2');
                            } else {
                                set_progress(bId, 4);
                            }
                        } else {
                            set_progress(bId, 0)
                        }
                    }
                    var s_toValidatePIDs = toValidate.reduce(function (acc, curr) {
                        if (!acc.includes(curr.pid)) acc.push(curr.pid);
                        return acc;
                    }, []);

                    for (let pid = 0; pid < s_toValidatePIDs.length; pid++) {
                        const element = s_toValidatePIDs[pid];
                        var item = toValidate.find(o => o.pid == element);
                        var bTeamIds = item["team_id"];
                        console.log('🔔 SOCKET.IO EMIT: instance-' + instance_id + '-appMessage-create', { remoteJid: message_.remoteJid, body: message_.body?.substring(0,50) });
            waziper.io.emit(`check_phone_update_${bTeamIds}`, {
                            id: element
                        })
                    }
                }
            }
        } catch (error) {

        }

    },

    handleMsgAck: async (waziper, instance_id, msg, ack = null) => {
        if (true) {
            await new Promise((r) => setTimeout(r, 500));
            try {

                var messageToUpdate = await common.db_get('sp_whatsapp_messages', [{ instance_id: instance_id }, { id: msg.key.id }]);
                if (!messageToUpdate) return;

                await Extend.update("sp_whatsapp_messages", [{ ack: ack }, { id: msg.key.id }]);

                messageToUpdate.ack = ack;

                console.log('🔔 SOCKET.IO EMIT: instance-' + instance_id + '-appMessage-create', { remoteJid: message_.remoteJid, body: message_.body?.substring(0,50) });
            waziper.io
                    //.to(`${instance_id}`)
                    .emit(`instance-${instance_id}-appMessage-update`, {
                        message: messageToUpdate
                    })

            } catch (err) {
                console.error(`Error handling message ack. Err: ${err}`);
            }
        }
    },

    autoresponder_time: async (message, instance_id, chat_id) => {

        const autoresponder_val = await cacheLayer.get(`autoresponder:${instance_id}:${chat_id}`);
        await cacheLayer.set(`autoresponder:${instance_id}:${chat_id}`, message.messageTimestamp);

        return Number.parseFloat(autoresponder_val);
    },

    process_official_sent_message: async function (messageBody, pid, message_id, pushname = "") {

        // common.special_log(messageBody, 'procesing sent message body');
        switch (messageBody.type ?? '') {
            case "image":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.image?.caption ?? '',
                        link: messageBody.image?.link
                    }
                }
                break;
            case "audio":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.audio?.caption ?? '',
                        link: messageBody.audio?.link
                    }
                }
                break;
            case "document":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.document?.caption ?? '',
                        link: messageBody.document?.link
                    }
                }
                break;
            case "video":
                message_to_script = {
                    message: {
                        has_media: true,
                        conversation: messageBody.video?.caption ?? '',
                        link: messageBody.video?.link
                    }
                }
                break;
            case "template":
                message_to_script = {
                    message: {
                        conversation: `Template Name: ${messageBody.template?.name || ''}`
                    }
                }
                break;
            default:

                message_to_script = {
                    message: {
                        conversation: messageBody.text?.body || ''
                    }
                }

                break;
        }
        message_to_script.messageTimestamp = common.time();
        message_to_script.pushName = pushname;
        message_to_script.official_api = true;
        message_to_script.key = {
            remoteJid: pid,
            id: message_id.slice(-15),
            fromMe: true
        };

        return message_to_script;
    },

    mark_as_read: async function (message, instance_id) {

        var account = await common.db_get("sp_accounts", [{ token: instance_id }]);

        if (account && account.login_type == 1) {

            const { access_token: bearer } = JSON.parse(account.tmp);
            const whatsappAPIURL = `https://graph.facebook.com/v19.0/${account.username}/messages`;

            let data = JSON.stringify({
                "messaging_product": "whatsapp",
                "status": "read",
                "message_id": message.id
            });

            let config = {
                method: 'post',
                maxBodyLength: Infinity,
                url: whatsappAPIURL,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${bearer}`
                },
                data: data
            };

            axios.request(config)
                .then((response) => {
                    console.log('mark as read', message.id, JSON.stringify(response.data));
                })
                .catch((error) => {
                    console.log('fail mark as read', message.id, error);
                });
        }
    },

    process_official_message: async function (message, pushname, from_me = false) {

        switch (message.type ?? '') {
            case 'interactive':

                message_to_script = {
                    message: {
                        buttonsResponseMessage: {
                            selectedDisplayText: message.interactive.button_reply.title
                        }
                    }
                }

                break;
            case 'image':
                message_to_script = {
                    message: {
                        imageMessage: {
                            caption: message.image.caption ?? '',
                            mimetype: message.image.mime_type,
                            id: message.image.id ?? ''
                        }
                    }
                }
                break;
            case 'video':
                message_to_script = {
                    message: {
                        videoMessage: {
                            caption: message.video.caption ?? '',
                            mimetype: message.video.mime_type,
                            id: message.video.id ?? ''
                        }
                    }
                }
                break;
            case 'audio':
                message_to_script = {
                    message: {
                        audioMessage: {
                            caption: message.audio.caption ?? '',
                            mimetype: message.audio.mime_type,
                            id: message.audio.id ?? ''
                        }
                    }
                }
                break;
            case 'sticker':
                message_to_script = {
                    message: {
                        stickerMessage: {
                            caption: message.sticker.caption ?? '',
                            mimetype: message.sticker.mime_type,
                            id: message.sticker.id ?? ''
                        }
                    }
                }
                break;
            default:
                message_to_script = {
                    message: {
                        conversation: message.text?.body || ''
                    }
                }
                break;
        }

        message_to_script.messageTimestamp = common.time();
        message_to_script.official_api = true;
        message_to_script.key = {
            // ✅ UPDATED: Default to @s.whatsapp.net for backward compatibility
            // WhatsApp will automatically handle @lid format when needed
            remoteJid: `${message.from}@s.whatsapp.net`,
            id: message.id.slice(-15),
            fromMe: from_me
        };
        message_to_script.pushName = pushname;
        // common.special_log(message_to_script, "message_to_script");
        return message_to_script;
    },

    chat: {
        resolveMessageJids: (msg) => {
            const remoteJid = msg?.key?.remoteJid || msg?.key?.remoteJidAlt || '';
            const participant = msg?.key?.participant || msg?.key?.participantAlt || remoteJid;
            return { remoteJid, participant };
        },
        filterMessages: (msg) => {
            // common.special_log(msg, 'procesando mensaje', "-")

            if (msg.message?.protocolMessage) return false;

            if ([
                WAMessageStubType.REVOKE,
                WAMessageStubType.E2E_DEVICE_CHANGED,
                WAMessageStubType.E2E_IDENTITY_CHANGED,
                WAMessageStubType.CIPHERTEXT
            ].includes(msg.messageStubType)) return false;

            return true;
        },
        getTypeMessage: (msg) => {
            return getContentType(msg.message);
        },
        isValidMsg: (msg) => {
            if (msg.key.remoteJid === "status@broadcast") return false;
            try {
                const msgType = Extend.chat.getTypeMessage(msg);
                if (!msgType) {
                    return;
                }

                const ifType =
                    msgType === "conversation" ||
                    msgType === "extendedTextMessage" ||
                    msgType === "audioMessage" ||
                    msgType === "videoMessage" ||
                    msgType === "imageMessage" ||
                    msgType === "documentMessage" ||
                    msgType === "documentWithCaptionMessage" ||
                    msgType === "stickerMessage" ||
                    msgType === "buttonsResponseMessage" ||
                    msgType === "buttonsMessage" ||
                    msgType === "messageContextInfo" ||
                    msgType === "locationMessage" ||
                    msgType === "liveLocationMessage" ||
                    msgType === "contactMessage" ||
                    msgType === "voiceMessage" ||
                    msgType === "mediaMessage" ||
                    msgType === "contactsArrayMessage" ||
                    msgType === "reactionMessage" ||
                    msgType === "ephemeralMessage" ||
                    msgType === "protocolMessage" ||
                    msgType === "listResponseMessage" ||
                    msgType === "listMessage" ||
                    msgType === "viewOnceMessage"

                if (!ifType) {
                    //console.error(`>>> not isValidMsg: ${msgType} \n${JSON.stringify(msg?.message)}`);
                    return false;
                }

                return !!ifType;
            } catch (error) {
                return false;
            }
        },
        getBodyButton: (msg) => {
            if (msg.key.fromMe && msg?.message?.viewOnceMessage?.message?.buttonsMessage?.contentText) {
                let bodyMessage = `*${msg?.message?.viewOnceMessage?.message?.buttonsMessage?.contentText}*`;

                for (const buton of msg.message?.viewOnceMessage?.message?.buttonsMessage?.buttons) {
                    bodyMessage += `\n\n${buton.buttonText?.displayText}`;
                }
                return bodyMessage;
            }

            if (msg.key.fromMe && msg?.message?.viewOnceMessage?.message?.listMessage) {
                let bodyMessage = `*${msg?.message?.viewOnceMessage?.message?.listMessage?.description}*`;
                for (const buton of msg.message?.viewOnceMessage?.message?.listMessage?.sections) {
                    for (const rows of buton.rows) {
                        bodyMessage += `\n\n${rows.title}`;
                    }
                }

                return bodyMessage;
            }
        },
        msgLocation: (image, latitude, longitude) => {
            // Return valid string for location messages to prevent null body error
            if (image) {
                var b64 = Buffer.from(image).toString("base64");
                let data = `data:image/png;base64, ${b64} | https://maps.google.com/maps?q=${latitude}%2C${longitude}&z=17&hl=pt-BR|${latitude}, ${longitude} `;
                return data;
            }
            // Fallback when no image - prevents NULL body error in database insert
            return `Location: https://maps.google.com/maps?q=${latitude || 0}%2C${longitude || 0}&z=17 | Lat: ${latitude || 0}, Lng: ${longitude || 0}`;
        },
        getBodyMessage: (msg) => {
            try {
                if (msg.message?.ephemeralMessage) {
                    msg.message = msg.message.ephemeralMessage.message;
                }
                let type = Extend.chat.getTypeMessage(msg);

                const types = {
                    conversation: msg?.message?.conversation,
                    imageMessage: msg.message?.imageMessage?.caption,
                    videoMessage: msg.message.videoMessage?.caption,
                    extendedTextMessage: msg.message.extendedTextMessage?.text,
                    buttonsResponseMessage: msg.message.buttonsResponseMessage?.selectedButtonId || msg.message.templateMessage?.hydratedTemplate?.hydratedContentText,
                    templateButtonReplyMessage: msg.message?.templateButtonReplyMessage?.selectedId,
                    messageContextInfo: msg.message.buttonsResponseMessage?.selectedButtonId || msg.message.listResponseMessage?.title,
                    buttonsMessage: Extend.chat.getBodyButton(msg) || msg.message.listResponseMessage?.singleSelectReply?.selectedRowId,
                    viewOnceMessage: Extend.chat.getBodyButton(msg) || msg.message?.listResponseMessage?.singleSelectReply?.selectedRowId,
                    stickerMessage: "sticker",
                    contactMessage: msg.message?.contactMessage?.vcard,
                    contactsArrayMessage: "varios contatos",
                    //locationMessage: `Latitude: ${msg.message.locationMessage?.degreesLatitude} - Longitude: ${msg.message.locationMessage?.degreesLongitude}`,
                    locationMessage: Extend.chat.msgLocation(
                        msg.message?.locationMessage?.jpegThumbnail,
                        msg.message?.locationMessage?.degreesLatitude,
                        msg.message?.locationMessage?.degreesLongitude
                    ),
                    liveLocationMessage: `Latitude: ${msg.message.liveLocationMessage?.degreesLatitude} - Longitude: ${msg.message.liveLocationMessage?.degreesLongitude}`,
                    documentMessage: msg.message?.documentMessage?.title,
                    audioMessage: "audio",
                    listMessage: Extend.chat.getBodyButton(msg) || msg.message.listResponseMessage?.title,
                    listResponseMessage: msg.message?.listResponseMessage?.singleSelectReply?.selectedRowId,
                    reactionMessage: msg.message.reactionMessage?.text || "reaction",
                    documentWithCaptionMessage: msg.message.documentMessage?.caption || 'document'
                };

                const objKey = Object.keys(types).find(key => key === type);

                if (!objKey) {
                    throw new Error(`no body message: ${type} \n ${JSON.stringify(msg)}`)
                }
                return types[type];

            } catch (error) {
                //console.error(error);
                return false;
            }
        },
        getSenderMessage: (session, msg) => {
            const me = {
                id: jidNormalizedUser(session.user.id),
                name: session.user.name
            }

            if (msg.key.fromMe) return me.id;
            const senderId = msg.participant || msg.key.participant || msg.key.remoteJid || undefined;
            return senderId && jidNormalizedUser(senderId);
        },
        getContactMessage: async (session, msg) => {
            const jids = Extend.chat.resolveMessageJids(msg);
            const rawNumber = `${jids.remoteJid || ''}`.replace(/\D/g, "");
            return { id: jids.remoteJid, name: msg.key.fromMe ? rawNumber : msg.pushName };
        },
        CreateOrUpdateContactService: async (waziper, message, instance_id, { name, number, profilePicUrl, isGroup, extraInfo = [] }) => {
            var subscriptor_ = await Extend.getSubscriber(waziper, message, instance_id, { name: name, number: number, profilePicUrl: profilePicUrl, isGroup: isGroup, extraInfo: extraInfo }, message.official_api ?? false);
            if (!message.key.fromMe) {
                var subscriptor_ = await Extend.updateSubscriberContactData(subscriptor_, { name: name, number: number, profilePicUrl: profilePicUrl, isGroup: isGroup, extraInfo: extraInfo })
            }
            return subscriptor_;
        },
        verifyContact: async (waziper, session, message, instance_id, msgContact) => {
            let profilePicUrl;
            try {
                if (message.official_api ?? false)
                    profilePicUrl = ''
                else
                    profilePicUrl = await session.profilePictureUrl(msgContact.id);
            } catch (e) {
                profilePicUrl = '';//join(__dirname, "..", "files", 'nopicture.png'); //`${process.env.FRONTEND_URL}/nopicture.png`;
            }

            const contactData = {
                name: msgContact?.name || msgContact.id.replace(/\D/g, ""),
                number: msgContact.id.replace(/\D/g, ""),
                profilePicUrl,
                // ✅ UPDATED: Check for group chats (supports both @g.us and g.us formats)
                isGroup: msgContact.id.includes("g.us") || msgContact.id.includes("@g.us"),
                instance_id: instance_id
            };

            const contact = Extend.chat.CreateOrUpdateContactService(waziper, message, instance_id, contactData);

            return contact;
        },
        CreateMessageService: async ({ messageData, instance_id }, contact, waziper) => {

            let message_ = { ...messageData, instance_id, createdAt: common.time(), updatedAt: common.time() };
            try {
                const rjid = `${message_.remoteJid || ''}`;
                const pjid = `${message_.participant || ''}`;
                const isGroup = rjid.includes('@g.us') || rjid.endsWith('g.us');
                console.log(`[DB DEBUG] instance=${instance_id} fromMe=${message_.fromMe === true} isGroup=${isGroup} remoteJid=${rjid} participant=${pjid} mediaType=${message_.mediaType || ''} hasMediaUrl=${!!message_.mediaUrl}`);
            } catch (e) {
                console.log('[DB DEBUG] log failed:', e?.message || e);
            }

            var res = await common.db_insert('sp_whatsapp_messages', message_);

            console.log('🔔 SOCKET.IO EMIT: instance-' + instance_id + '-appMessage-create', { remoteJid: message_.remoteJid, body: message_.body?.substring(0,50) });
            waziper.io
                //.to(`${instance_id}`)
                //.to("notification")
                .emit(`instance-${instance_id}-appMessage-create`, {
                    message: message_,
                    subscriber: contact
                });

            return message_;

        },
        verifyMessage: async (msg, body, instance_id, contact, waziper) => {
            var plain_message = JSON.stringify(msg);


            if (!Number.isInteger(msg.status)) {
                msg.status = 3;
            }

            const messageData = {
                id: msg.key.id,
                instance_id: instance_id,
                contactId: msg.key.fromMe ? undefined : contact.id,
                body: body || "Message",
                fromMe: msg.key.fromMe,
                mediaType: Extend.chat.getTypeMessage(msg),
                read: msg.key.fromMe,
                ack: msg.status ?? 3,
                remoteJid: Extend.chat.resolveMessageJids(msg).remoteJid,
                participant: Extend.chat.resolveMessageJids(msg).participant,
                dataJson: plain_message
            };
            return await Extend.chat.CreateMessageService({ messageData, instance_id: instance_id }, contact, waziper);

        },
        downloadMedia: async (msg, instance_id) => {
            try {

                const mineType =
                    msg.message?.imageMessage ||
                    msg.message?.audioMessage ||
                    msg.message?.videoMessage ||
                    msg.message?.stickerMessage ||
                    msg.message?.documentMessage ||
                    msg.message?.extendedTextMessage?.contextInfo?.quotedMessage?.imageMessage;




                const messageType = msg.message?.documentMessage
                    ? "document"
                    : mineType.mimetype.split("/")[0].replace("application", "document")
                        ? (mineType.mimetype.split("/")[0].replace("application", "document"))
                        : (mineType.mimetype.split("/")[0]);

                let stream;
                let contDownload = 0;

                while (contDownload < 3 && !stream) {
                    try {

                        var account = await common.db_get("sp_accounts", [{ token: instance_id }]);

                        if (account && account.login_type == 1) {
                            if (mineType.id) {
                                //common.special_log(mineType.id, 'mineType.id');

                                const { access_token: bearer } = JSON.parse(account.tmp);
                                const whatsappAPIURL = `https://graph.facebook.com/v19.0/${mineType.id}`;

                                var test = await axios.get(whatsappAPIURL, {
                                    headers: { Authorization: `Bearer ${bearer}` }
                                })

                                if (test.data?.url) {
                                    //common.special_log(test.data.url, "download media result", "-",);
                                    result = await axios.get(test.data?.url, {
                                        headers: { Authorization: `Bearer ${bearer}` }, responseType: 'stream'
                                    })

                                    if (result.data) {
                                        stream = result.data;
                                        //common.special_log(result.data, "download media stream")
                                    } else {
                                        throw new Error('fail to obtain media data')
                                    }


                                } else {
                                    throw new Error('fail to obtain url')
                                }


                            }
                            contDownload++;

                        } else {
                            stream = await downloadContentFromMessage(
                                msg.message.audioMessage ||
                                msg.message.videoMessage ||
                                msg.message.documentMessage ||
                                msg.message.imageMessage ||
                                msg.message.stickerMessage ||
                                msg.message.extendedTextMessage?.contextInfo.quotedMessage.imageMessage ||
                                msg.message?.buttonsMessage?.imageMessage ||
                                msg.message?.templateMessage?.fourRowTemplate?.imageMessage ||
                                msg.message?.templateMessage?.hydratedTemplate?.imageMessage ||
                                msg.message?.templateMessage?.hydratedFourRowTemplate?.imageMessage ||
                                msg.message?.interactiveMessage?.header?.imageMessage,
                                messageType
                            );
                        }
                    } catch (error) {
                        // common.special_log(error, "error download media result", "*", "error");
                        contDownload++;
                        await new Promise(resolve =>
                            setTimeout(resolve, 1000 * contDownload * 2)
                        );
                        console.error(
                            `>>>> error ${contDownload} al descargar el archivo ${msg?.key.id}`
                        );
                    }
                }

                let buffer = Buffer.from([]);

                try {
                    for await (const chunk of stream) {
                        buffer = Buffer.concat([buffer, chunk]);
                    }
                } catch (error) {
                    console.error('error download Media:', error)
                    return null;
                }

                if (!buffer) {
                    return null;
                }

                let filename = msg.message?.documentMessage?.fileName || "";

                if (!filename) {
                    const ext = mineType.mimetype.split("/")[1].split(";")[0];
                    var id = common.makeid(8);
                    filename = `${instance_id}_${id}.${ext}`;
                }

                const media_ = {
                    data: buffer,
                    mimetype: mineType.mimetype,
                    filename
                };

                return media_;
            } catch (error) {
                console.error('error download Media:', error)
                return null;
            }
        },
        verifyMediaMessage: async (msg, body, instance_id, contact, waziper) => {

            if (!msg.message.has_media) {
                //console.error('no has media on msg')
                var media = await Extend.chat.downloadMedia(msg, instance_id);

                if (!media) {
                    throw new Error("ERR_WAPP_DOWNLOAD_MEDIA");
                }

                const ext = media.mimetype.split("/")[1].split(";")[0];

                if (!media.filename) {
                    var id = common.makeid(8);
                    media.filename = `${instance_id}_${id}.${ext}`;
                }

                if (!['js', 'php', 'py', 'json'].includes(`${ext}`.toLowerCase()) && (config['save_files'] ?? true)) {

                    try {
                        await writeFileAsync(
                            join(__dirname, "..", "files", `${media.filename}`),
                            media.data,
                            "base64"
                        );
                    } catch (err) {
                        console.error(err);
                    }

                }
            } else {
                var media = {
                    mimetype: common.ext2mime(msg.message.link),
                    filename: msg.message.link
                }
            }

            const messageData = {
                id: msg.key.id,
                instance_id: instance_id,
                contactId: msg.key.fromMe ? undefined : contact.id,
                body: body || media.filename || "Media",
                fromMe: msg.key.fromMe,
                read: msg.key.fromMe,
                mediaUrl: media.filename,
                mediaType: media.mimetype.split("/")[0],
                ack: msg.status,
                remoteJid: Extend.chat.resolveMessageJids(msg).remoteJid,
                participant: Extend.chat.resolveMessageJids(msg).participant,
                dataJson: JSON.stringify(msg),
            };
            // common.special_log(messageData, 'message_data', '+')

            return await Extend.chat.CreateMessageService({ messageData, instance_id: instance_id }, contact, waziper);


        },
        processChatMessages: async (waziper, sessions, messages, instance_id, official_api = false) => {
            if (true) {
                try {
                    const messages_filtered = messages.messages
                        .filter(Extend.chat.filterMessages)
                        .map(msg => msg);

                    if (messages_filtered) {
                        messages_filtered.forEach(async (originalMessage) => {
                            var msg_ = JSON.parse(JSON.stringify(originalMessage));
                            var messageExists = await common.db_get('sp_whatsapp_messages', [{ instance_id: instance_id }, { id: msg_.key.id }]);

                            if (!messageExists) {
                                if (Extend.chat.isValidMsg(msg_)) {
                                    const bodyMessage = Extend.chat.getBodyMessage(msg_);
                                    const msgType = Extend.chat.getTypeMessage(msg_);
                                    const hasMedia = !!(
                                        msg_.message?.audioMessage ||
                                        msg_.message?.imageMessage ||
                                        msg_.message?.videoMessage ||
                                        msg_.message?.documentMessage ||
                                        msg_.message?.documentWithCaptionMessage ||
                                        msg_.message?.stickerMessage ||
                                        msg_.message?.has_media
                                    );

                                    if (msg_.key.fromMe) {
                                        if (!hasMedia && msgType !== "conversation" && msgType !== "extendedTextMessage" && msgType !== "vcard") return;
                                    }

                                    var msgContact = await Extend.chat.getContactMessage(sessions[instance_id], msg_);
                                    const contact = await Extend.chat.verifyContact(waziper, sessions[instance_id], msg_, instance_id, msgContact);

                                    var unreadMessages = 0;
                                    if (msg_.key.fromMe) {
                                        await cacheLayer.set(`contacts:${contact.id}:unreads`, "0");
                                    } else {
                                        const unreads = await cacheLayer.get(`contacts:${contact.id}:unreads`);
                                        unreadMessages = +unreads + 1;
                                        await cacheLayer.set(`contacts:${contact.id}:unreads`, `${unreadMessages}`);
                                    }

                                    var contact_ = await Extend.updateSubscriberMessages(contact, unreadMessages, bodyMessage, common.time());
                                    if (unreadMessages > 0) { }

                                    if (hasMedia) {
                                        await Extend.chat.verifyMediaMessage(msg_, bodyMessage, instance_id, contact, waziper);
                                    } else {
                                        await Extend.chat.verifyMessage(msg_, bodyMessage, instance_id, contact, waziper);
                                    }
                                } else {
                                    //console.error('msg invalid', msg);
                                }
                            }
                        });
                    }

                } catch (e) {
                    console.error(e);
                }
            }
        }
    },

    /**
     * Message Type Helper Functions
     * These functions provide easy-to-use interfaces for sending different WhatsApp message types
     * Compatible with Baileys v6.5.0 (@whiskeysockets/baileys)
     */

    /**
     * Send Location Message
     * @param {Object} sock - WhatsApp socket instance
     * @param {string} jid - Recipient JID (phone@s.whatsapp.net or groupid@g.us)
     * @param {number} latitude - Location latitude
     * @param {number} longitude - Location longitude
     * @param {string} name - Location name/caption (optional)
     * @returns {Promise<Object>} - Message send result
     */
    sendLocation: async function(sock, jid, latitude, longitude, name = '') {
        try {
            const locationMessage = {
                location: {
                    degreesLatitude: latitude,
                    degreesLongitude: longitude,
                    name: name
                }
            };
            return await sock.sendMessage(jid, locationMessage);
        } catch (error) {
            console.error('Error sending location:', error);
            throw error;
        }
    },

    /**
     * Send Contact Card (vCard)
     * @param {Object} sock - WhatsApp socket instance
     * @param {string} jid - Recipient JID
     * @param {string} contactName - Contact display name
     * @param {string} contactNumber - Contact phone number (without + symbol)
     * @returns {Promise<Object>} - Message send result
     */
    sendContact: async function(sock, jid, contactName, contactNumber) {
        try {
            const vcard =
                'BEGIN:VCARD\n' +
                'VERSION:3.0\n' +
                `FN:${contactName}\n` +
                `TEL;type=CELL;type=VOICE;waid=${contactNumber}:+${contactNumber}\n` +
                'END:VCARD';

            const contactMessage = {
                contacts: {
                    displayName: contactName,
                    contacts: [{ vcard }]
                }
            };
            return await sock.sendMessage(jid, contactMessage);
        } catch (error) {
            console.error('Error sending contact:', error);
            throw error;
        }
    },

    /**
     * Send Poll Message
     * @param {Object} sock - WhatsApp socket instance
     * @param {string} jid - Recipient JID
     * @param {string} question - Poll question
     * @param {Array<string>} options - Array of poll options
     * @param {boolean} selectableCount - Number of options that can be selected (default: 1)
     * @returns {Promise<Object>} - Message send result
     */
    sendPoll: async function(sock, jid, question, options, selectableCount = 1) {
        try {
            const pollMessage = {
                poll: {
                    name: question,
                    values: options,
                    selectableCount: selectableCount
                }
            };
            return await sock.sendMessage(jid, pollMessage);
        } catch (error) {
            console.error('Error sending poll:', error);
            throw error;
        }
    },

    /**
     * Send Simple Button Message
     *
     * ✅ UPDATED: Now uses baileys_helper package for proper interactive message support.
     * The baileys_helper package provides the required binary node wrappers (biz, interactive, native_flow)
     * that WhatsApp expects for interactive messages.
     *
     * @param {Object} sock - WhatsApp socket instance
     * @param {string} jid - Recipient JID
     * @param {string} text - Message text
     * @param {Array<Object>} buttons - Array of button objects [{buttonId, displayText}, ...]
     * @param {string} footer - Footer text (optional)
     * @returns {Promise<Object>} - Message send result
     */
    sendSimpleButtons: async function(sock, jid, text, buttons, footer = '') {
        try {
            const { sendButtons } = require('baileys_helper');

            // Convert buttons to baileys_helper format
            const convertedButtons = buttons.map(btn => ({
                id: btn.buttonId || btn.id,
                text: btn.displayText || btn.text || btn.buttonText?.displayText
            }));

            return await sendButtons(sock, jid, {
                text: text,
                footer: footer,
                buttons: convertedButtons
            });
        } catch (error) {
            console.error('Error sending simple buttons:', error);
            throw error;
        }
    },

    /**
     * Send Template Button Message (with URL, Call, and Quick Reply buttons)
     *
     * ✅ UPDATED: Now uses baileys_helper package for proper interactive message support.
     * The baileys_helper package provides the required binary node wrappers (biz, interactive, native_flow)
     * that WhatsApp expects for interactive messages.
     *
     * @param {Object} sock - WhatsApp socket instance
     * @param {string} jid - Recipient JID
     * @param {string} text - Message text
     * @param {Array<Object>} templateButtons - Array of template button objects
     * @param {string} footer - Footer text (optional)
     * @returns {Promise<Object>} - Message send result
     *
     * Template button format (converted to native flow):
     * - Quick Reply: {index: 1, quickReplyButton: {displayText: 'Yes', id: 'yes-id'}}
     * - URL Button: {index: 2, urlButton: {displayText: 'Visit', url: 'https://example.com'}}
     * - Call Button: {index: 3, callButton: {displayText: 'Call', phoneNumber: '+1234567890'}}
     */
    sendTemplateButtons: async function(sock, jid, text, templateButtons, footer = '') {
        try {
            const { sendInteractiveMessage } = require('baileys_helper');

            // Convert template buttons to native flow format
            const interactiveButtons = templateButtons.map(btn => {
                // Quick Reply Button
                if (btn.quickReplyButton) {
                    return {
                        name: 'quick_reply',
                        buttonParamsJson: JSON.stringify({
                            display_text: btn.quickReplyButton.displayText,
                            id: btn.quickReplyButton.id
                        })
                    };
                }
                // URL Button
                if (btn.urlButton) {
                    return {
                        name: 'cta_url',
                        buttonParamsJson: JSON.stringify({
                            display_text: btn.urlButton.displayText,
                            url: btn.urlButton.url
                        })
                    };
                }
                // Call Button
                if (btn.callButton) {
                    return {
                        name: 'cta_call',
                        buttonParamsJson: JSON.stringify({
                            display_text: btn.callButton.displayText,
                            phone_number: btn.callButton.phoneNumber
                        })
                    };
                }
                return btn;
            });

            return await sendInteractiveMessage(sock, jid, {
                text: text,
                footer: footer,
                interactiveButtons: interactiveButtons
            });
        } catch (error) {
            console.error('Error sending template buttons:', error);
            throw error;
        }
    },

    /**
     * Send List Message
     *
     * ✅ UPDATED: Now uses baileys_helper package for proper interactive message support.
     * The baileys_helper package provides the required binary node wrappers (biz, interactive, native_flow)
     * that WhatsApp expects for interactive messages.
     *
     * List messages are converted to single_select native flow buttons.
     *
     * @param {Object} sock - WhatsApp socket instance
     * @param {string} jid - Recipient JID
     * @param {string} title - List title
     * @param {string} text - Message text/description
     * @param {string} buttonText - Button text to open list
     * @param {Array<Object>} sections - Array of section objects
     * @param {string} footer - Footer text (optional)
     * @returns {Promise<Object>} - Message send result
     *
     * Section format (converted to single_select):
     * [{
     *   title: "Section 1",
     *   rows: [
     *     {title: "Option 1", rowId: "option1", description: "Description 1"},
     *     {title: "Option 2", rowId: "option2", description: "Description 2"}
     *   ]
     * }]
     */
    sendListMessage: async function(sock, jid, title, text, buttonText, sections, footer = '') {
        try {
            const { sendInteractiveMessage } = require('baileys_helper');

            // Convert sections to single_select format
            const convertedSections = sections.map(section => ({
                title: section.title,
                rows: section.rows.map(row => ({
                    id: row.rowId || row.id,
                    title: row.title,
                    description: row.description,
                    header: row.header
                }))
            }));

            // Use single_select native flow button
            const interactiveButtons = [{
                name: 'single_select',
                buttonParamsJson: JSON.stringify({
                    title: buttonText || title,
                    sections: convertedSections
                })
            }];

            return await sendInteractiveMessage(sock, jid, {
                text: text,
                title: title,
                footer: footer,
                interactiveButtons: interactiveButtons
            });
        } catch (error) {
            console.error('Error sending list message:', error);
            throw error;
        }
    }
}



module.exports = Extend;

