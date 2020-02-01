/* jshint -W024 */
(async () => {
	const cookieconsent = await import("cookieconsent");

	if (window.location.hostname.indexOf(".local") < 0) {
		var _paq = window._paq || [];
		/* tracker methods like "setCustomDimension" should be called before "trackPageView" */
		_paq.push(['trackPageView']);
		_paq.push(['enableLinkTracking']);
		(function() {
			var u="//statistik.musical-fabrik.de/";
			_paq.push(['setTrackerUrl', u+'matomo.php']);
			_paq.push(['setSiteId', '1']);
			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
			g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
		})();
	}

	window.cookieconsent.initialise({
		container: document.getElementById("content"),
		palette: {
			popup: {
				background: "#000",
			},
			button: {
				background: "#fd8e00",
			},
		},
		revokable: true,
		position: "bottom-left",
		// type: "opt-in",
		content: {
			header: "Auf dieser Website werden Cookies benutzt.",
			message:
				"Um unsere Webseite für Sie optimal zu gestalten und fortlaufend verbessern zu können, verwenden wir Cookies. Durch die weitere Nutzung der Webseite stimmen Sie der Verwendung von Cookies zu.",
			dismiss: "Ok",
			allow: "Das ist ok.",
			deny: "Ich möchte das nicht.",
			link: "Mehr dazu",
			href: "/datenschutz",
			close: "&#x274c;",
		},
		revokeBtn: '<div class="cc-revoke {{classes}}">Cookies</div>'
	});
})();
