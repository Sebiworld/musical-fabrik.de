import lazysizes from 'lazysizes';

// import a plugin
import 'lazysizes/plugins/parent-fit/ls.parent-fit';
import 'lazysizes/plugins/blur-up/ls.blur-up';
lazysizes.cfg.blurupMode = 'auto';
// import 'lazysizes/plugins/bgset/ls.bgset';

// polyfills
import 'lazysizes/plugins/respimg/ls.respimg';

if (!('object-fit' in document.createElement('a').style)) {
	require('lazysizes/plugins/object-fit/ls.object-fit');
}