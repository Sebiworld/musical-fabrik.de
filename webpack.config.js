const webpack = require("webpack");
const path = require("path");
const merge = require("webpack-merge");
const glob = require("glob");

const parts = require("./webpack.parts");

const PATHS = {
	entwicklung: path.join(__dirname, "./site/templates/entwicklung/"),
	build: path.join(__dirname, "./site/templates/assets/"),
	public: "/site/templates/assets/",
};

const ENTRIES = {
	// js: ["babel-polyfill", ...glob.sync(PATHS.entwicklung + "/js/*.js")],
	js: [
		"babel-polyfill",
		"jquery",
		"lodash",
		...glob.sync(PATHS.entwicklung + "/js/*.js"),
	],
	scss: [
		...glob.sync(PATHS.entwicklung + "/scss/*.scss"),
		...glob.sync(PATHS.entwicklung + "/scss/komponenten/*.scss"),
		...glob.sync(PATHS.entwicklung + "/scss/seiten/*.scss"),
	],
};

const commonConfig = merge([
	{
		output: {
			// Needed for code splitting to work in nested paths
			publicPath: PATHS.public,
			filename: "[name].min.js",
			chunkFilename: "js/chunk.[id]-[chunkhash].min.js",
			path: path.resolve(PATHS.build),
		},
	},
	parts.loadJavaScript({ exclude: /node_modules/ }),
	parts.setFreeVariable("HELLO", "hello from config"),
]);

const productionConfig = merge([
	{
		performance: {
			hints: "warning", // "error" or false are valid too
			maxEntrypointSize: 150000, // in bytes, default 250k
			maxAssetSize: 450000, // in bytes
		},
	},
	{
		recordsPath: path.join(__dirname, "records.json"),
	},
	parts.clean(PATHS.build + '/js/'),
	parts.clean(PATHS.build + '/css/'),
	parts.clean(PATHS.build + '/img/'),
	parts.clean(PATHS.build + '/fonts/'),
	// parts.minifyJavaScript(),
	// parts.minifyCSS({
	// 	options: {
	// 		discardComments: {
	// 			removeAll: true,
	// 		},
	// 		// Run cssnano in safe mode to avoid
	// 		// potentially unsafe transformations.
	// 		safe: true,
	// 	},
	// }),
	parts.extractCSS({
		options: {
			filename: "[name].min.css",
			// chunkFilename: "css/[id].css",
			allChunks: true
		},
		sassIncludePaths: [PATHS.entwicklung + "/scss/"]
	}),
	// parts.purifyCSS({
	// 	paths: ENTRIES.scss,
	// }),
	parts.loadImages({
		options: {
			name: "img/[name].[hash:8].[ext]",
			publicPath: "/site/templates/assets/",
		},
	}),
	parts.loadFonts({
		options: {
			name: "fonts/[name].[hash:8].[ext]",
			publicPath: "/site/templates/assets/",
		},
	}),
	parts.generateSourceMaps({ type: "source-map" }),
	// {
	// 	optimization: {
	// 		splitChunks: {
	// 			chunks: "initial",
	// 		},
	// 		runtimeChunk: {
	// 			name: "manifest",
	// 		},
	// 	},
	// },
	// parts.attachRevision(),
]);

const developmentConfig = merge([
	// parts.devServer({
	// 	// Customize host/port here if needed
	// 	host: process.env.HOST,
	// 	port: process.env.PORT,
	// }),
	parts.extractCSS({
		options: {
			filename: "[name].min.css",
			// chunkFilename: "css/[id].css",
			allChunks: true
		},
		sassIncludePaths: [PATHS.entwicklung + "/scss/"],
	}),
	parts.loadImages({
		options: {
			name: "img/[name].[hash:8].[ext]",
			publicPath: "/site/templates/assets/",
		},
	}),
	parts.loadFonts({
		options: {
			name: "fonts/[name].[hash:8].[ext]",
			publicPath: "/site/templates/assets/",
		},
	}),
	parts.liveReload(),
]);

module.exports = mode => {
	let entries = {};

	// let polyfillInjected = false;
	for (let filetype in ENTRIES) {
		for (let index in ENTRIES[filetype]) {
			let entry = ENTRIES[filetype][index];

			if (typeof entry !== "string") {
				continue;
			}

			let key = entry;
			if (typeof entry === "string") {
				const extension = path.extname(entry);
				let zielordner = 'js';
				if(extension === '.scss' || extension === '.sass' || extension === '.css'){
					zielordner = 'css';
				}
				key = zielordner + '/' + path.basename(entry, extension);
				// if (!polyfillInjected && (extension === ".js" || extension === ".jsx")) {
				// // 	entry = ["babel-polyfill", entry];
				// // 	polyfillInjected = true;
				// }
			}

			entries[key] = entry;
		}
	}

	const config = mode === "production" ? productionConfig : developmentConfig;

	return merge([commonConfig, config, { mode }].concat({ entry: entries }));
};
