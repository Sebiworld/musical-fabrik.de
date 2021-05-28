/* jshint -W024 */
global.$ = require("jquery");
global.jQuery = global.$;
(async () => {
	await import(/* webpackChunkName: "processw-comments" */ "./../../../../wire/modules/Fieldtype/FieldtypeComments/comments.js");
})();