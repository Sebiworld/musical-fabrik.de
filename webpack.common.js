const webpack = require("webpack");
const path = require("path");
const glob = require("glob");
const UglifyJsPlugin = require("uglifyjs-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const CleanWebpackPlugin = require('clean-webpack-plugin');

const PATHS = {
	entwicklung: path.join(__dirname, "./site/templates/entwicklung/"),
	build: path.join(__dirname, "./site/templates/assets/"),
	public: "/site/templates/assets/",
};

const ENTRIES = {
	js: [
		"@babel/polyfill",
		"jquery",
		...glob.sync(PATHS.entwicklung + "/js/*.js"),
	],
	scss: [
		...glob.sync(PATHS.entwicklung + "/scss/*.scss"),
		...glob.sync(PATHS.entwicklung + "/scss/komponenten/*.scss"),
		...glob.sync(PATHS.entwicklung + "/scss/seiten/*.scss"),
	],
};

module.exports = {
	entry: () => {
		// Pro Ausgangsdatei den Ausgabe-Dateinamen ermitteln (Soll der gleiche Dateiname sein)
		let entries = {};
		for (let filetype in ENTRIES) {
			for (let index in ENTRIES[filetype]) {
				let entry = ENTRIES[filetype][index];
				if (typeof entry !== "string") continue;

				let key = entry;
				if (typeof entry === "string") {
					key = path.basename(entry, path.extname(entry));
				}

				entries[key] = entry;
			}
		}

		return entries;
	},
	output: {
		path: PATHS.build,
		publicPath: PATHS.public,
		filename: "js/[name].min.js?v=[hash:8]",
		chunkFilename: "js/chunk.[id]-[chunkhash].min.js",
	},
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader",
				},
			},
			{
				test: require.resolve("progressively"),
				use: "imports-loader?this=>window",
			},
			{
				test: require.resolve("chart.js"),
				use: "imports-loader?this=>window",
			},
			{
				// Exposes jQuery for use outside Webpack build
				test: require.resolve("jquery"),
				use: [
					{
						loader: "expose-loader",
						options: "jQuery",
					},
					{
						loader: "expose-loader",
						options: "$",
					},
				],
			},
			{
				test: /\.(sass|scss|css)$/,

				use: [
					{
						loader: MiniCssExtractPlugin.loader,
						options: {
				          	publicPath: PATHS.public
				        }
					},
					{
						loader: "css-loader",
						options: {
							sourceMap: process.env.NODE_ENV !== "production" ? true : false,
							minimize: process.env.NODE_ENV !== "production" ? false : true,
							alias: {
								"../fonts/bootstrap": "bootstrap-sass/assets/fonts/bootstrap",
							},
							importLoaders: 4,
						},
					},
					{
						loader: "postcss-loader",
						options: {
							ident: "postcss",
							sourceMap: process.env.NODE_ENV !== 'production' ? true : false,
							plugins: loader => [
								require("postcss-discard-comments")({
									removeAll: true,
								}),
								require("postcss-cssnext")(),
								require("postcss-short"),
								// require("postcss-inline-svg"),
								require("cssnano")({
									autoprefixer: false,
									safe: true,
								}),
							],
						},
					},
					{
						loader: "resolve-url-loader",
						options: {
							debug: false,
							sourceMap: process.env.NODE_ENV !== "production" ? true : false,
							absolute: false
						},
					},
					{
						loader: "sass-loader",
						options: {
							includePaths: [PATHS.entwicklung + "scss/"],
							sourceMap: true,
							sourceMapContents: false,
							errLogToConsole: process.env.NODE_ENV !== "production" ? true : false,
							sass_option_push_import_extension: [".css"],
						},
					},
				],
			},
			{
				test: /\.(sass|scss)$/,
				enforce: "pre",
				loader: "import-glob-loader",
			},
			{
				test: /\.(gif|jpe?g|png|svg)$/,
				use: {
					loader: "file-loader",
					options: {
						name: "[name].[hash:8].[ext]",
						outputPath: "img/",
						emitFile: true,
						useRelativePath: false
						// publicPath: PATHS.public,
					},
				},
			},
			{
				test: /\.(woff|ttf|eot|otf)(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
				use: {
					loader: "file-loader",
					options: {
						name: "[name].[hash:8].[ext]",
						outputPath: "fonts/",
						emitFile: true,
						useRelativePath: false
						// publicPath: PATHS.public,
					},
				},
			},
		],
	},
	optimization: {
		minimize: process.env.NODE_ENV === "production",
		minimizer: [
			new OptimizeCSSAssetsPlugin({}),
			new UglifyJsPlugin({
				sourceMap: false
			})
		],
	},
	plugins: [
		new CleanWebpackPlugin([
			PATHS.build + '/js/',
			PATHS.build + '/css/',
			PATHS.build + '/fonts/',
			PATHS.build + '/img/'
		]),
		new webpack.ProgressPlugin(),
		new MiniCssExtractPlugin({
			filename: "css/[name].min.css?v=[hash:8]",
			chunkFilename: "css/[id].css",
		}),
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
		}),
	],
};
