const webpack = require("webpack");
const path = require("path");
const glob = require("glob");
const pkg = require('./package.json');
const envVars = require('./bin/environment/prod.json');

const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CompressionPlugin = require('compression-webpack-plugin');
const LiveReloadPlugin = require('webpack-livereload-plugin');
const CleanCSS = require('clean-css');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const LodashModuleReplacementPlugin = require('lodash-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

const PATHS = {
	source: path.join(__dirname, "./site/templates/src/"),
	build: path.join(__dirname, "./site/templates/assets/"),
	public: "/site/templates/assets/",
};

const ENTRIES = {
	js: [
		...glob.sync(PATHS.source + "/js/*.js"),
	],
	scss: [
		...glob.sync(PATHS.source + "/scss/vendors/*.scss"),
		...glob.sync(PATHS.source + "/scss/*.scss"),
		...glob.sync(PATHS.source + "/scss/components/*.scss"),
		...glob.sync(PATHS.source + "/scss/pages/*.scss"),
	]
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

					let key = path.basename(entry, path.extname(entry));

					if (typeof entries[key] !== "object") {
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
			filename: "js/[name]-[chunkhash]" + (env.browser_env && env.browser_env !== 'modern' ? '.' + env.browser_env : '') + ".min.js",
			chunkFilename: "js/chunk-[name]-[chunkhash]" + (env.browser_env && env.browser_env !== 'modern' ? '.' + env.browser_env : '') + ".min.js",
		},
		module: {
			rules: [
				{
					test: /\.vue$/,
					loader: 'vue-loader'
				},
				{
					test: /\.(js|jsx)$/,
					exclude: /node_modules\/(?!(dom7|bootstrap|swiper)\/).*/,
					use: {
						loader: "babel-loader",
						options: {
							presets: [
								[
									'@babel/preset-env', {
										modules: false,
										useBuiltIns: 'usage',
										corejs: 3,
										targets: {
											browsers: (env.browser_env === 'modern' ? pkg.browserslist.modernBrowsers : pkg.browserslist.legacyBrowsers),
										},
										debug: false
									}
								],
							],
							plugins: [
								'@babel/plugin-syntax-dynamic-import',
								[
									"@babel/plugin-transform-runtime", {
										// "regenerator": true
									}
								]
							],
						}
					}
				},
				{
					test: /\.(sass|scss|css)$/,

					use: [
						{
							loader: MiniCssExtractPlugin.loader,
							options: {
								// publicPath: PATHS.public
							}
						},
						{
							loader: "css-loader",
							options: {
								sourceMap: !isProduction,
								importLoaders: 4,
							},
						},
						{
							loader: "postcss-loader",
							options: {
								postcssOptions: {
									ident: "postcss",
									sourceMap: !isProduction,
									plugins: [
										["postcss-discard-comments", {
											removeAll: true,
										}],
										"postcss-preset-env",
										"autoprefixer",
										"postcss-short"
									],
								}
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
								implementation: require('node-sass'),
								sourceMap: true,
								sassOptions: {
									includePaths: [PATHS.source + "scss/"],
									errLogToConsole: true,
									sass_option_push_import_extension: [".css"],
								}
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
					test: /\.(svg)$/,
					use: [{
						loader: "file-loader",
						options: {
							name: "[name].[chunkhash].[ext]",
							outputPath: "img/",
							emitFile: true,
							useRelativePath: false
							// publicPath: PATHS.public,
						},
					}
					],
				},
				{
					test: /\.(gif|jpe?g|png)$/,
					use: [{
						loader: "file-loader",
						options: {
							name: "[name].[chunkhash].[ext]",
							outputPath: "img/",
							emitFile: true,
							useRelativePath: false
							// publicPath: PATHS.public,
						},
					},
					{
						loader: 'image-webpack-loader',
						options: {
							mozjpeg: {
								progressive: true,
								quality: [0.65]
							},
							// optipng.enabled: false will disable optipng
							optipng: {
								enabled: false,
							},
							pngquant: {
								quality: [0.65, 0.90],
								speed: 4
							},
							gifsicle: {
								interlaced: false,
							},
							// the webp option will enable WEBP
							webp: {
								quality: [0.75]
							}
						}
					}
					],
				},
				{
					test: /\.(woff|ttf|eot|otf)(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
					use: [{
						loader: "file-loader",
						options: {
							name: "[name].[chunkhash].[ext]",
							outputPath: "fonts/",
							emitFile: true,
							useRelativePath: false
						},
					}],
				}
			],
		},
		resolve: {
			symlinks: true
		},
		mode: isProduction ? 'production' : 'development',
		devtool: isProduction ? 'hidden-nosources-source-map' : 'eval-cheap-module-source-map',
		optimization: {
			minimize: isProduction,
			usedExports: true,
			// runtimeChunk: 'single',
			minimizer: [
				new CssMinimizerPlugin({
					minimizerOptions: {
						preset: [
							'default',
							{
								discardComments: { removeAll: true },
							},
						],
					}
				}),
				//  test?, include?, exclude?, terserOptions?, extractComments?, parallel?, minify?
				new TerserPlugin({
					// cache: true,
					parallel: true,
					// sourceMap: !isProduction,
					terserOptions: {
						output: {
							comments: isProduction ? false : true,
						},
						safari10: true
					}
				})
			],
		},
		plugins: [
			new webpack.DefinePlugin(envVars),
			new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /de/),
			new LodashModuleReplacementPlugin({
				'collections': true,
				'paths': true,
				'shorthands': true
			}),
			new WebpackAssetsManifest({
				merge: true,
				customize(entry, original, manifest, asset) {
					if (env.browser_env !== 'modern' && env.browser_env !== undefined) {
						entry.key = env.browser_env + '/' + entry.key;
					}
					return entry;
				},
				writeToDisk: true,
				output: 'manifest.json'
			}),
			(env.clear !== 'false' && isProduction ?
				new CleanWebpackPlugin({
					cleanOnceBeforeBuildPatterns: [
						"./img/*",
						"./criticalcss/*",
						"./css/*",
						"./js/*",
						'./fonts/*'
					]
				}) : function () { }
			),
			new webpack.ProgressPlugin(),
			new MiniCssExtractPlugin({
				filename: "css/[name]-[chunkhash].min.css",
				chunkFilename: "css/[id]-[chunkhash].min.css",
			}),
			// new OptimizeCSSAssetsPlugin({
			// 	cssProcessor: CleanCSS,
			// 	cssProcessorPluginOptions: {
			// 		format: isProduction ? 'beautify' : 'keep-breaks',
			// 		sourceMap: !isProduction,
			// 		level: 2
			// 	},
			// 	canPrint: true
			// }),
			new CompressionPlugin(),
			(isProduction ?
				new BundleAnalyzerPlugin(
					{
						analyzerMode: 'static',
						reportFilename: 'report-' + env.browser_env + '.html',
					}
				) : new LiveReloadPlugin()
			)
		],
	};
};
