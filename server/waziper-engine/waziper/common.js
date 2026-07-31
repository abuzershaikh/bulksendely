/**
 * COMMON - Common Utility Functions Module
 * Provides database operations, phone number handling, and helper functions
 *
 * This module handles:
 * - MySQL database operations (query, insert, update, delete, fetch)
 * - Phone number formatting and validation
 * - File operations and MIME type detection
 * - Avatar retrieval from WhatsApp
 * - Utility functions (time, random ID generation, etc.)
 *
 * Note: This module does NOT directly use Baileys, but supports Baileys-based
 * operations through database management and utility functions
 */

// Core dependencies
const mysql = require('mysql');                  // MySQL database client
const config = require("./../config.js");        // Application configuration
const moment = require('moment-timezone');       // Date/time with timezone support

/**
 * MySQL connection pool
 * Uses connection pooling for better performance and resource management
 * Configuration from config.database (host, user, password, database)
 */
const db_connect = mysql.createPool(config.database);

/**
 * Common module - Utility functions and database operations
 */
const Common = {
	/**
	 * special_log - Enhanced console logging with visual separators
	 *
	 * @param {any} obj - Object or message to log
	 * @param {string} title - Log title/header
	 * @param {string} char - Character to use for separator
	 * @param {string} level - Console level (log, error, warn, info)
	 *
	 * Useful for debugging and highlighting important log messages
	 */
	special_log: async (obj, title = "Log", char = "*", level = "log") => {
		let lengh__ = Math.round(title.length / char.length);
		console[level]("\n\n\n" + char.repeat(15), title, char.repeat(15));
		console[level](obj);
		console[level](char.repeat(32 + lengh__) + "\n\n\n");
	},

	// Expose database connection for direct access if needed
	db_connect: db_connect,

	/**
	 * db_query - Execute a raw SQL query
	 *
	 * @param {string} query - SQL query string
	 * @param {boolean} row - If true, return single row; if false, return all rows
	 * @returns {Promise<object|array>} - Query result(s)
	 *
	 * Use for complex queries that don't fit the helper functions
	 */
	db_query: async function (query, row) {
		var res = await new Promise(async (resolve, reject) => {
			db_connect.query(query, (err, res) => {
				return resolve(res, true);
			});
		});

		return Common.response(res, row);
	},

	/**
	 * db_insert - Insert a new record into a table
	 *
	 * @param {string} table - Table name
	 * @param {object} data - Object with column:value pairs to insert
	 * @returns {Promise<object>} - Insert result with insertId
	 *
	 * Example: db_insert('sp_accounts', { name: 'John', email: 'john@example.com' })
	 */
	db_insert: async function (table, data) {
		// ✅ PRODUCTION RULE: Handle @lid and @s.whatsapp.net formats correctly
		//
		// Database Storage Rules for sp_whatsapp_messages:
		// 1. If incoming message uses @s.whatsapp.net:
		//    - Store @s.whatsapp.net in BOTH remoteJid AND participant
		// 2. If incoming message uses @lid:
		//    - Store @lid in participant (preserve original format)
		//    - Store @s.whatsapp.net in remoteJid (normalized for queries)
		//
		// This dual storage ensures:
		// - participant: Preserves exact format for reply routing
		// - remoteJid: Normalized format for database queries
		if (table === 'sp_whatsapp_messages') {
			// Handle participant field (preserve original format)
			if (data.participant && typeof data.participant === 'string') {
				// If participant is @lid format, keep it as-is
				// If participant is @s.whatsapp.net, keep it as-is
				// DO NOT normalize participant - it must preserve the original format
			}

			// Handle remoteJid field (normalize to @s.whatsapp.net)
			if (data.remoteJid && typeof data.remoteJid === 'string') {
				// If remoteJid is @lid, convert to @s.whatsapp.net for database queries
				if (data.remoteJid.includes('@lid')) {
					const phoneNumber = data.remoteJid.split('@')[0];
					data.remoteJid = `${phoneNumber}@s.whatsapp.net`;
				}
				// If already @s.whatsapp.net, keep as-is
			}

			// Handle dataJson field (preserve original for debugging)
			// DO NOT modify dataJson - keep original message structure
		}

		var res = await new Promise(async (resolve, reject) => {
			// ✅ FIX: Use INSERT IGNORE to prevent duplicate key errors
			// This handles cases where the same message might be processed twice
			const query = table === 'sp_whatsapp_messages'
				? "INSERT IGNORE INTO " + table + " SET ?"
				: "INSERT INTO " + table + " SET ?";

			db_connect.query(query, data, (err, res) => {
				if (err) Common.special_log(err, 'error on insert query', "*", 'error');
				return resolve(res, true);
			});
		});

		return res;
	},

	/**
	 * db_update - Update records in a table
	 *
	 * @param {string} table - Table name
	 * @param {array} data - Array with [update_data, where_conditions]
	 * @returns {Promise<object>} - Update result with affectedRows
	 *
	 * Example: db_update('sp_accounts', [{ status: 1 }, { id: 123 }])
	 */
	db_update: async function (table, data) {
		var res = await new Promise(async (resolve, reject) => {
			db_connect.query("UPDATE " + table + " SET ? WHERE ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	/**
	 * db_get - Get a single record from a table
	 *
	 * @param {string} table - Table name
	 * @param {array} data - Array of objects with column:value pairs for WHERE clause
	 * @returns {Promise<object|null>} - Single record or null if not found
	 *
	 * Example: db_get('sp_accounts', [{ id: 123 }, { status: 1 }])
	 * Generates: SELECT * FROM sp_accounts WHERE id = 123 AND status = 1
	 */
	db_get: async function (table, data) {
		var query = "SELECT * FROM " + table + " ";
		var where = "";
		if (data.length > 0) {
			for (var i = 0; i < data.length; i++) {
				if (i == 0) {
					where = where + " ?";
				} else {
					where = where + " AND ?";
				}
			}
		}

		if (where != "") {
			query = query + " WHERE " + where;
		}

		var res = await new Promise(async (resolve, reject) => {
			db_connect.query(query, data, (err, res) => {
				return resolve(res);
			});
		});

		return Common.response(res, true);  // Return single row
	},

	/**
	 * db_fetch - Get multiple records from a table
	 *
	 * @param {string} table - Table name
	 * @param {array} data - Array of objects with column:value pairs for WHERE clause
	 * @returns {Promise<array>} - Array of records
	 *
	 * Example: db_fetch('sp_accounts', [{ team_id: 456 }])
	 * Generates: SELECT * FROM sp_accounts WHERE team_id = 456
	 */
	db_fetch: async function (table, data) {
		var query = "SELECT * FROM " + table + " ";
		var where = "";
		if (data.length > 0) {
			for (var i = 0; i < data.length; i++) {
				if (i == 0) {
					where = where + " ?";
				} else {
					where = where + " AND ?";
				}
			}
		}

		if (where != "") {
			query = query + " WHERE " + where;
		}

		var res = await new Promise(async (resolve, reject) => {
			db_connect.query(query, data, (err, res) => {
				return resolve(res);
			});
		});

		return Common.response(res, false);  // Return all rows
	},

	/**
	 * db_delete - Delete records from a table
	 *
	 * @param {string} table - Table name
	 * @param {array} data - Array of objects with column:value pairs for WHERE clause
	 * @returns {Promise<object>} - Delete result with affectedRows
	 *
	 * Example: db_delete('sp_accounts', [{ id: 123 }])
	 * Generates: DELETE FROM sp_accounts WHERE id = 123
	 */
	db_delete: async function (table, data) {
		var query = "DELETE FROM " + table + " ";
		var where = "";
		if (data.length > 0) {
			for (var i = 0; i < data.length; i++) {
				if (i == 0) {
					where = where + " ?";
				} else {
					where = where + " AND ?";
				}
			}
		}

		if (where != "") {
			query = query + " WHERE " + where;
		}

		var res = await new Promise(async (resolve, reject) => {
			db_connect.query(query, data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	get_phone_number: async function (contact_id, phone_numbers) {
		var res = await new Promise(async (resolve, reject) => {
			db_connect.query(`SELECT *  FROM sp_whatsapp_phone_numbers WHERE pid = '` + contact_id + `' AND phone NOT IN( ? ) LIMIT 5`, [phone_numbers], (err, res) => {
				return resolve(res);
			});
		});
		return Common.response(res, true);
	},

	get_total_phone_number: async function (contact_id) {
		var res = await new Promise(async (resolve, reject) => {
			db_connect.query(`SELECT count(id) as count  FROM sp_whatsapp_phone_numbers WHERE pid = '` + contact_id + `'`, (err, res) => {
				return resolve(res);
			});
		});
		return Common.response(res, true);
	},

	get_instance: async function (instance_id) {
		var res = await new Promise(async (resolve, reject) => {
			var data = [{
				instance_id: instance_id
			}];

			db_connect.query("SELECT * FROM sp_whatsapp_sessions WHERE ?", data, (err, res) => {
				return resolve(res);
			});
		});
		return Common.response(res, true);
	},

	get_accounts: async function (accounts) {
		var res = await new Promise(async (resolve, reject) => {
			db_connect.query("SELECT count(*) as count FROM sp_accounts WHERE id IN  (" + accounts + ") AND status = 1", (err, res) => {
				return resolve(res);
			});
		});
		return Common.response(res, true);
	},

	update_status_instance: async function (instance_id, info) {
		var res = await new Promise(async (resolve, reject) => {
			var data = [{
				status: 1,
				data: JSON.stringify(info)
			}, {
				instance_id: instance_id
			}];

			db_connect.query("UPDATE sp_whatsapp_sessions SET ? WHERE ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	update_creds: async function (clients, instance_id, info) {
		var res = await new Promise(async (resolve, reject) => {
			var data = [{
				creds: JSON.stringify(clients.authState.creds)
			}, {
				instance_id: instance_id
			}];

			db_connect.query("UPDATE sp_whatsapp_sessions SET ? WHERE ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	db_insert_account: async function (instance_id, team_id, wa_info) {
		var res = await new Promise(async (resolve, reject) => {
			var data = {
				ids: Common.makeid(13),
				module: 'whatsapp_profiles',
				social_network: 'whatsapp',
				category: 'profile',
				login_type: 2,
				can_post: 0,
				team_id: team_id,
				pid: Common.get_phone(wa_info.id, 'wid'),
				name: wa_info.name,
				username: Common.get_phone(wa_info.id),
				token: instance_id,
				avatar: wa_info.avatar,
				url: 'https://web.whatsapp.com/',
				tmp: JSON.stringify(wa_info),
				status: 1,
				changed: Common.time(),
				created: Common.time()
			};

			db_connect.query("INSERT INTO sp_accounts SET ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	db_update_account: async function (instance_id, team_id, wa_info, account_id) {
		var res = await new Promise(async (resolve, reject) => {
			var data = [{
				pid: Common.get_phone(wa_info.id, 'wid'),
				name: wa_info.name,
				username: Common.get_phone(wa_info.id),
				token: instance_id,
				avatar: wa_info.avatar,
				tmp: JSON.stringify(wa_info),
				status: 1,
				module: 'whatsapp_profiles',
				social_network: 'whatsapp',
				category: 'profile',
				login_type: 2,
				url: 'https://web.whatsapp.com/',
				changed: Common.time(),
			}, {
				id: account_id
			}];

			db_connect.query("UPDATE sp_accounts SET ? WHERE ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	db_insert_stats: async function (team_id) {
		var res = await new Promise(async (resolve, reject) => {
			var data = {
				ids: Common.makeid(13),
				team_id: team_id,
				wa_total_sent_by_month: 0,
				wa_total_sent: 0,
				wa_chatbot_count: 0,
				wa_autoresponder_count: 0,
				wa_api_count: 0,
				wa_bulk_total_count: 0,
				wa_bulk_sent_count: 0,
				wa_bulk_failed_count: 0,
				wa_time_reset: 0,
				next_update: 0
			};

			db_connect.query("INSERT INTO sp_whatsapp_stats SET ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	db_insert_stats: async function (team_id) {
		var res = await new Promise(async (resolve, reject) => {
			var data = {
				ids: Common.makeid(13),
				team_id: team_id,
				wa_total_sent_by_month: 0,
				wa_total_sent: 0,
				wa_chatbot_count: 0,
				wa_autoresponder_count: 0,
				wa_api_count: 0,
				wa_bulk_total_count: 0,
				wa_bulk_sent_count: 0,
				wa_bulk_failed_count: 0,
				wa_time_reset: 0,
				next_update: 0
			};

			db_connect.query("INSERT INTO sp_whatsapp_stats SET ?", data, (err, res) => {
				return resolve(res, true);
			});
		});

		return res;
	},

	response: async function (res, row) {
		if (res != undefined && res.length > 0) {
			if (row || row == undefined) {
				return res[0];
			} else {
				return res;
			}

		}
		return false;
	},

	check_especials: function (phone, id) {
		return new Promise((resolve, reject) => {
			var updateQuery = '';
			var current_phone = phone;
			if (phone != '') {
				if (phone.startsWith('55')) {
					var ddd = phone.substring(2, 4);
					if (ddd >= 31 && phone.length >= 13) {
						phone = phone.substring(0, 4) + phone.substring(5, phone.length);
					}
				}

				if (phone.startsWith('52') && phone.length == 12 && phone.substring(2, 3) != '1') {
					phone = phone.substring(0, 2) + '1' + phone.substring(2, phone.length);
				}

				if (phone != current_phone) {
					updateQuery = `UPDATE sp_whatsapp_phone_numbers SET phone=? WHERE id=?`;
					db_connect.query(updateQuery, [phone, id], function (f, s) {
						if (f) console.error(f);
						resolve(phone);
					});
				} else {
					resolve(phone);
				}
			} else {
				resolve(phone);
			}
		});
	},

	/**
	 * makeid - Generate a random alphanumeric ID
	 *
	 * @param {number} length - Length of the ID to generate
	 * @returns {string} - Random lowercase alphanumeric string
	 *
	 * Used for generating unique identifiers for sessions, files, etc.
	 * Example: makeid(10) => "a3k9d2f7h1"
	 */
	makeid: function (length) {
		let result = '';
		const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		const charactersLength = characters.length;
		let counter = 0;
		while (counter < length) {
			result += characters.charAt(Math.floor(Math.random() * charactersLength));
			counter += 1;
		}
		return result.toLowerCase();
	},

	/**
	 * time - Get current Unix timestamp in seconds
	 *
	 * @returns {number} - Current Unix timestamp (seconds since epoch)
	 *
	 * Used for createdAt/updatedAt timestamps in database records
	 * Compatible with Baileys message timestamps
	 */
	time: function (length) {
		return Math.round(new Date().getTime() / 1000);
	},

	randomIntFromInterval: function (min, max) {
		return Math.floor(Math.random() * (max - min + 1) + min)
	},

	get_avatar: function (text, color) {
		if (text != undefined) {
			if (color == undefined) {
				var colors = [
					"E74645",
					"FB7756",
					"FACD60",
					"12492F",
					"F7A400",
					"58B368"
				];

				var random = Math.floor(Math.random() * colors.length);
				color = colors[random];
			}

			text = text.replace("&", "");
			text = text.replace("&amp;", "");
			text = text.replace("=", "");
			text = text.replace("&quot", "");
			text = text.replace("\"", "");
			text = text.replace("'", "");
			text = text.replace("~", "");
			text = text.replace(" ", "");

			return "https://ui-avatars.com/api/?name=" + encodeURI(text) + "&background=" + color + "&color=fff&font-size=0.5&rounded=false&format=png";
		}

		return false;
	},

	/**
	 * get_phone - Extract phone number from WhatsApp JID (Jabber ID)
	 *
	 * @param {string} id - WhatsApp JID (e.g., "1234567890@s.whatsapp.net" or "1234567890:12@s.whatsapp.net")
	 * @param {string} type - Extraction type: 'wid' (keep full JID) or default (extract phone only)
	 * @returns {string} - Extracted phone number or JID
	 *
	 * WhatsApp JID formats (Baileys v6.7.21):
	 * - User (old): "phone@s.whatsapp.net" (e.g., "1234567890@s.whatsapp.net")
	 * - User (new): "phone@lid" (e.g., "176695155916836@lid") ✅ UPDATED
	 * - User with device: "phone:device@s.whatsapp.net" (e.g., "1234567890:12@s.whatsapp.net")
	 * - Group: "groupid@g.us" (e.g., "120363123456789012@g.us")
	 *
	 * Examples:
	 * - get_phone("1234567890:12@s.whatsapp.net", "wid") => "1234567890@s.whatsapp.net"
	 * - get_phone("1234567890:12@s.whatsapp.net") => "1234567890"
	 * - get_phone("1234567890@s.whatsapp.net") => "1234567890"
	 * - get_phone("176695155916836@lid") => "176695155916836" ✅ NEW
	 */
	get_phone: function (id, type) {
		switch (type) {

			case 'wid':
				// Extract phone and domain, removing device ID
				// "1234567890:12@s.whatsapp.net" => "1234567890@s.whatsapp.net"
				// "176695155916836@lid" => "176695155916836@lid" ✅ UPDATED
				id = id.split(":");

				if (id.length == 2) {
					id1 = id[0];
					id2 = id[1];

					id2 = id2.split("@");

					id = id1 + "@" + id2[1];
				} else {
					id = id;
				}

				break;

			default:
				// Extract phone number only
				// "1234567890:12@s.whatsapp.net" => "1234567890"
				// "1234567890@s.whatsapp.net" => "1234567890"
				// "176695155916836@lid" => "176695155916836" ✅ UPDATED
				id = id.split(":");

				if (id.length == 2) {
					id = id[0];
				} else {
					id = id[0].split("@");
					id = id[0];
				}

				break;
		}

		return id;
	},

	roundMinutes: function (date) {
		date.setHours(date.getHours() + 1);
		date.setMinutes(0, 0, 0);
		return date;
	},

	getTZDiff: function (timezone) {
		var now = moment();
		var localOffset = now.utcOffset();
		now.tz(timezone);
		var centralOffset = now.utcOffset();
		var diffInMinutes = localOffset - centralOffset;
		return diffInMinutes / 60;
	},

	convert_timezone: function (date, tzString) {
		return new Date((typeof date === "string" ? new Date(date) : date).toLocaleString("en-US", { timeZone: tzString }));
	},

	sleep: async function (ms) {
		return new Promise((resolve) => {
			setTimeout(resolve, ms);
		});
	},

	params: function (params, content) {
		if (params != "" && params != undefined && params != null) {
			params = JSON.parse(params);
			var params = Common.toLowerKeys(params);
			var PARAMS_PATTERN = /\%(.*?)\%/;
			var match;

			var count = 0;

			while (match = content.match(PARAMS_PATTERN)) {
				match = match[0];
				var find = match.substring(1, match.length - 1);
				find = find.toLowerCase();
				if (params[find] != undefined) {
					var change = params[find];
					content = content.replace(match, change);
				}

				count++;

				if (count == 100) {
					break;
				}
			}
		}

		return content;
	},

	toLowerKeys: function (obj) {
		return Object.keys(obj).reduce((accumulator, key) => {
			accumulator[key.toLowerCase()] = obj[key];
			return accumulator;
		}, {});
	},

	get_url_extension: function (url) {
		return url.split(/[#?]/)[0].split('.').pop().trim();
	},

	/**
	 * ext2mime - Convert file extension to MIME type
	 *
	 * @param {string} url - File URL or path
	 * @returns {string} - MIME type (e.g., "image/jpeg", "video/mp4")
	 *
	 * Used when sending media files via Baileys to set correct MIME type
	 * Supports common WhatsApp media formats:
	 * - Images: jpg, jpeg, png, gif, webp
	 * - Videos: mp4
	 * - Audio: mp3, ogg
	 * - Documents: pdf
	 */
	ext2mime: function (url) {
		var mime = Common.get_url_extension(url);
		var mimetypes = {
			"jpg": "image/jpeg",
			"png": "image/png",
			"mp4": "video/mp4",
			"mp3": "audio/mpeg",
			"ogg": "audio/ogg",
			"jpeg": "image/jpeg",
			"pdf": "application/pdf",
			"ogg": "audio/ogg",
			"gif": "image/gif",
			"webp": "image/webp"
		}

		return mimetypes[mime];
	},

	get_file_name: function (url) {
		var filename = url.substring(url.lastIndexOf('/') + 1);
		return decodeURI(filename);
	},

	post_type: function (mime, type) {

		var post_type = "documentMessage";

		if (type == 1) {
			if (
				mime == "image/png" ||
				mime == "image/jpeg" ||
				mime == "image/jpg" ||
				mime == "image/gif"
			) {
				post_type = "imageMessage";
			} else if (
				mime == "video/mp4" ||
				mime == "video/3gpp" ||
				mime == "video/gif"
			) {
				post_type = "videoMessage";
			} else if (
				mime == "audio/mpeg" ||
				mime == "audio/ogg"
			) {
				post_type = "audioMessage";
			}

		} else {
			var post_type = "documentMessage";

			if (
				mime == "png" ||
				mime == "jpeg" ||
				mime == "jpg" ||
				mime == "gif"

			) {
				post_type = "imageMessage";
			} else if (
				mime == "mp4" ||
				mime == "3gpp"
			) {
				post_type = "videoMessage";
			} else if (
				mime == "mp3" ||
				mime == "ogg"
			) {
				post_type = "audioMessage";
			}
		}

		return post_type;
	},

	/**
	 * Message Type Formatting Utilities
	 * Helper functions for creating properly formatted message objects
	 * Compatible with Baileys v6.5.0
	 */

	/**
	 * Format Location Message
	 * @param {number} latitude - Location latitude
	 * @param {number} longitude - Location longitude
	 * @param {string} name - Location name (optional)
	 * @returns {Object} - Formatted location message object
	 */
	formatLocationMessage: function(latitude, longitude, name = '') {
		return {
			location: {
				degreesLatitude: latitude,
				degreesLongitude: longitude,
				name: name
			}
		};
	},

	/**
	 * Format Contact vCard
	 * @param {string} name - Contact name
	 * @param {string} number - Contact phone number (without + symbol)
	 * @returns {Object} - Formatted contact message object
	 */
	formatContactMessage: function(name, number) {
		const vcard =
			'BEGIN:VCARD\n' +
			'VERSION:3.0\n' +
			`FN:${name}\n` +
			`TEL;type=CELL;type=VOICE;waid=${number}:+${number}\n` +
			'END:VCARD';

		return {
			contacts: {
				displayName: name,
				contacts: [{ vcard }]
			}
		};
	},

	/**
	 * Format Poll Message
	 * @param {string} question - Poll question
	 * @param {Array<string>} options - Array of poll options
	 * @param {number} selectableCount - Number of selectable options (default: 1)
	 * @returns {Object} - Formatted poll message object
	 */
	formatPollMessage: function(question, options, selectableCount = 1) {
		return {
			poll: {
				name: question,
				values: options,
				selectableCount: selectableCount
			}
		};
	},

	/**
	 * Format Simple Button Message
	 *
	 * ✅ UPDATED: Returns format compatible with baileys_helper sendButtons function.
	 *
	 * @param {string} text - Message text
	 * @param {Array<Object>} buttons - Array of button objects [{buttonId, displayText}, ...]
	 * @param {string} footer - Footer text (optional)
	 * @returns {Object} - Formatted button message object for baileys_helper
	 */
	formatSimpleButtonMessage: function(text, buttons, footer = '') {
		const convertedButtons = buttons.map(btn => ({
			id: btn.buttonId || btn.id,
			text: btn.displayText || btn.text
		}));

		return {
			text: text,
			footer: footer,
			buttons: convertedButtons
		};
	},

	/**
	 * Format Template Button Message
	 *
	 * ✅ UPDATED: Returns format compatible with baileys_helper sendInteractiveMessage function.
	 *
	 * @param {string} text - Message text
	 * @param {Array<Object>} templateButtons - Array of template button objects
	 * @param {string} footer - Footer text (optional)
	 * @returns {Object} - Formatted template button message object for baileys_helper
	 */
	formatTemplateButtonMessage: function(text, templateButtons, footer = '') {
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

		return {
			text: text,
			footer: footer,
			interactiveButtons: interactiveButtons
		};
	},

	/**
	 * Format List Message
	 *
	 * ✅ UPDATED: Returns format compatible with baileys_helper sendInteractiveMessage function.
	 *
	 * @param {string} title - List title
	 * @param {string} text - Message text/description
	 * @param {string} buttonText - Button text to open list
	 * @param {Array<Object>} sections - Array of section objects
	 * @param {string} footer - Footer text (optional)
	 * @returns {Object} - Formatted list message object for baileys_helper
	 */
	formatListMessage: function(title, text, buttonText, sections, footer = '') {
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

		return {
			text: text,
			title: title,
			footer: footer,
			interactiveButtons: interactiveButtons
		};
	},

	/**
	 * Create Quick Reply Button
	 *
	 * ✅ UPDATED: Returns format compatible with baileys_helper native flow buttons.
	 *
	 * @param {number} index - Button index (for compatibility, not used in native flow)
	 * @param {string} displayText - Button display text
	 * @param {string} id - Button ID
	 * @returns {Object} - Quick reply button object for baileys_helper
	 */
	createQuickReplyButton: function(index, displayText, id) {
		return {
			index: index,
			quickReplyButton: {
				displayText: displayText,
				id: id
			}
		};
	},

	/**
	 * Create URL Button
	 *
	 * ✅ UPDATED: Returns format compatible with baileys_helper native flow buttons.
	 *
	 * @param {number} index - Button index (for compatibility, not used in native flow)
	 * @param {string} displayText - Button display text
	 * @param {string} url - Button URL
	 * @returns {Object} - URL button object for baileys_helper
	 */
	createUrlButton: function(index, displayText, url) {
		return {
			index: index,
			urlButton: {
				displayText: displayText,
				url: url
			}
		};
	},

	/**
	 * Create Call Button
	 *
	 * ✅ UPDATED: Returns format compatible with baileys_helper native flow buttons.
	 *
	 * @param {number} index - Button index (for compatibility, not used in native flow)
	 * @param {string} displayText - Button display text
	 * @param {string} phoneNumber - Phone number to call
	 * @returns {Object} - Call button object for baileys_helper
	 */
	createCallButton: function(index, displayText, phoneNumber) {
		return {
			index: index,
			callButton: {
				displayText: displayText,
				phoneNumber: phoneNumber
			}
		};
	}
}
module.exports = Common;
db_connect.on('error', function(err) { console.error('MySQL Pool Error:', err); });
