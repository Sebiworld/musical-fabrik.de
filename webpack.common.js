const webpack = require("webpack");
const path = require("path");
const glob = require("glob");
const UglifyJsPlugin = require("uglifyjs-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const CleanWebpackPlugin = require('clean-webpack-plugin');
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const CompressionPlugin = require('compression-webpack-plugin');

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
	...glob.sync(PATHS.entwicklung + "/scss/vendors/*.scss"),
	...glob.sync(PATHS.entwicklung + "/scss/*.scss"),
	...glob.sync(PATHS.entwicklung + "/scss/komponenten/*.scss"),
	...glob.sync(PATHS.entwicklung + "/scss/seiten/*.scss"),
	],
};

module.exports = (env, options) => {
	const isProduction = options.mode === 'production';
	return {
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

					if(typeof entries[key] !== "object"){
						entries[key] = [];
					}
					entries[key].push(entry);
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
				exclude: /node_modules\/(?!(dom7|ssr-window|swiper)\/).*/,
				loader: "babel-loader"
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
							sourceMap: !isProduction,
							minimize: isProduction,
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
							sourceMap: !isProduction,
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
									sourceMap: !isProduction,
									absolute: false
								},
							},
							{
								loader: "sass-loader",
								options: {
									includePaths: [PATHS.entwicklung + "scss/"],
									sourceMap: true,
									sourceMapContents: false,
									errLogToConsole: true,
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
			mode: isProduction ? 'production' : 'development',
			devtool: isProduction ? '' : 'source-map',
			optimization: {
				minimize: isProduction,
				minimizer: [
				new OptimizeCSSAssetsPlugin({}),
				new UglifyJsPlugin({
					sourceMap: false,
					parallel: true,
					uglifyOptions: {
					    warnings: false,
					    parse: {},
					    compress: {},
					    mangle: true,
					    output: null,
					    toplevel: false,
					    nameCache: null,
					    ie8: false,
					    keep_fnames: false,
					    output: {
					    	comments: false
					    }
					  }
				})
				],
				// splitChunks: {
				// 	chunks: 'async',
				// 	minSize: 30000,
				// 	maxSize: 0,
				// 	minChunks: 1,
				// 	maxAsyncRequests: 5,
				// 	maxInitialRequests: 3,
				// 	automaticNameDelimiter: '~',
				// 	name: true,
				// 	cacheGroups: {
				// 		vendors: {
				// 			test: /[\\/]node_modules[\\/]/,
				// 			priority: -10
				// 		},
				// 		default: {
				// 			minChunks: 2,
				// 			priority: -20,
				// 			reuseExistingChunk: true
				// 		}
				// 	}
				// }
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
			new OptimizeCssAssetsPlugin({
		      	cssProcessorPluginOptions: {
		      	  preset: ['default', { discardComments: { removeAll: true } }],
		      	},
		      	canPrint: true
		    }),
		    new CompressionPlugin()
			],
		};
	};
