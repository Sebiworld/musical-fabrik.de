const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const PurifyCSSPlugin = require("purifycss-webpack");
const CleanWebpackPlugin = require("clean-webpack-plugin");
const webpack = require("webpack");
const UglifyWebpackPlugin = require("uglifyjs-webpack-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");
const cssnano = require("cssnano");
const LiveReloadPlugin = require("webpack-livereload-plugin");

exports.minifyCSS = ({ options }) => ({
	plugins: [
		new OptimizeCSSAssetsPlugin({
			cssProcessor: cssnano,
			cssProcessorOptions: options,
			canPrint: false,
		}),
	],
});

exports.minifyJavaScript = () => ({
	optimization: {
		minimizer: [new UglifyWebpackPlugin({ sourceMap: true })],
	},
});

exports.attachRevision = () => ({
	plugins: [
		new webpack.BannerPlugin({
			banner: new GitRevisionPlugin().version(),
		}),
	],
});

exports.clean = path => ({
	plugins: [new CleanWebpackPlugin([path])],
});

exports.purifyCSS = ({ paths }) => ({
	plugins: [new PurifyCSSPlugin({ paths })],
});

exports.extractCSS = ({
	options,
	include,
	exclude,
	use = [],
	sassIncludePaths = [],
}) => {
	// Output extracted CSS to a file
	let plugins = [];
	plugins.push(new MiniCssExtractPlugin(options));

	return {
		module: {
			rules: [
				{
					test: /\.(sass|scss|css)$/,
					include,
					exclude,

					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: "css-loader",
							options: {
								sourceMap: process.env.NODE_ENV !== 'production' ? true : false,
								minimize: process.env.NODE_ENV !== 'production'? false : true,
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
									require("postcss-inline-svg"),
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
								sourceMap: process.env.NODE_ENV !== 'production' ? true : false,
							},
						},
						{
							loader: "sass-loader",
							options: {
								includePaths: sassIncludePaths,
								// data: '@import "./globals/index";',
								sourceMap: process.env.NODE_ENV !== 'production' ? true : false,
								errLogToConsole: process.env.NODE_ENV !== 'production' ? true : false,
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
			],
		},
		plugins: plugins,
	};
};

exports.autoprefix = () => ({
	loader: "postcss-loader",
	options: {
		plugins: () => [require("autoprefixer")()],
	},
});

exports.loadImages = ({ include, exclude, options } = {}) => ({
	module: {
		rules: [
			{
				test: /\.(gif|jpe?g|png|svg)$/,
				include,
				exclude,
				use: {
					loader: "file-loader",
					options,
				},
			},
		],
	},
});

exports.loadFonts = ({ include, exclude, options } = {}) => ({
	module: {
		rules: [
			{
				test: /\.(woff|ttf|eot|otf)(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
				include,
				exclude,
				use: {
					loader: "file-loader",
					options,
				},
			},
		],
	},
});

exports.loadJavaScript = ({ include, exclude } = {}) => ({
	module: {
		rules: [
			{
				test: /\.js$/,
				include,
				exclude,
				use: "babel-loader",
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
		],
	},
	plugins: [
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
			_: "lodash",
		})
	],
});

exports.aggressiveSplitting = () => ({
	plugins: [
		new webpack.optimize.AggressiveSplittingPlugin({
			minSize: 10000,
			maxSize: 30000,
			entryChunkMultiplicator: 2,
		}),
	],
});

exports.generateSourceMaps = ({ type }) => ({
	devtool: type,
});

exports.setFreeVariable = (key, value) => {
	const env = {};
	env[key] = JSON.stringify(value);

	return {
		plugins: [new webpack.DefinePlugin(env)],
	};
};

exports.liveReload = () => {
	plugins: [new LiveReloadPlugin()];
};
