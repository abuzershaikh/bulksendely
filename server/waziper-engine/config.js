
var config = {
    evo_server: "https://evoapi.exemple.com/",
	debug: false,
	extended_functions: true,
	save_files: true,
	prefix: 'sp',
	frontend: 'http://139.84.138.48:7708',
	redis: '',
	port: 7708,
	default_openai_key: '',
	time_to_reset: 120,
	database: {
		connectionLimit: 50,
		host: "localhost",
		user: "wappbuzz",
		password: "WzJi3HtY9JSpjibRSL",
		database: "wappbuzz",
		charset: "utf8mb4",
		debug: false,
		waitForConnections: true,
		connectTimeout: 60000,
		acquireTimeout: 60000,
		multipleStatements: true
	},
	cors: {
		origin: '*',
		optionsSuccessStatus: 200
	}
}
module.exports = config; 

//Wappbuzz Version: 6.0.5
