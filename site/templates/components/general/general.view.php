<?php

namespace ProcessWire;

$configPage = $this->configurationService->getConfigurationPage();
?>

<!DOCTYPE html>
<html lang="de">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<?= $this->page->seo; ?>

	<link rel="shortcut icon" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon.ico" />
	<link rel="icon" type="image/x-icon" sizes="16x16 32x32" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon.ico">
	<link rel="apple-touch-icon" sizes="152x152" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-152-precomposed.png">
	<link rel="apple-touch-icon" sizes="144x144" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-144-precomposed.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-120-precomposed.png">
	<link rel="apple-touch-icon" sizes="114x114" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-114-precomposed.png">
	<link rel="apple-touch-icon" sizes="180x180" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-180-precomposed.png">
	<link rel="apple-touch-icon" sizes="72x72" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-72-precomposed.png">
	<link rel="apple-touch-icon" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-57.png">
	<link rel="icon" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-32.png" sizes="32x32">

	<!-- For IE10 Metro -->
	<meta name="msapplication-TileColor" content="#FFFFFF">
	<meta name="msapplication-TileImage" content="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-144.png">
	<meta name="theme-color" content="#ffffff">

	<!-- Chrome for Android -->
	<!-- <link rel="manifest" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/manifest.json"> -->
	<link rel="icon" sizes="192x192" href="<?= wire('config')->urls->templates; ?>assets/static_img/icons/favicon-192.png">

	<?php
    // Alle CSS-Styles werden über die addStyle()-Methode der Komponente hinzugefügt
    foreach (wire('config')->styles as $stylefile) {
        echo "\n\t<link rel='stylesheet' href='$stylefile' /> ";
    }
    ?>

	<script type="text/javascript">
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
	</script>

</head>

<body class="t-<?= $this->page->template->name; ?>">
	<?= $this->component->getGlobalComponent('header'); ?>
	<a href="#top" class="back-to-top btn btn-outline-dark">
		<i class="icon ion-ios-arrow-round-up"></i>
	</a>
	<div class="main-content" id="top">
		<?= $this->component->getGlobalComponent('dev_output'); ?>

		<?php
        if ($this->childComponents) {
            foreach ($this->childComponents as $component) {
                echo $component;
            }
        }
        ?>

		<?= $this->component->getGlobalComponent('footer'); ?>
	</div>

	<div>
		<!--   
			,     ,
			)\___/(
			{(@)v(@)}    wuff wuff
			{|~~~|}
			{/^^^\}
			m-m
		~~~~~~~~~~~~~~~~~~-->
	</div>

	<?= $this->component->getGlobalComponent('modals'); ?>
	<?= $this->component->getInlineStyles(); ?>

	<script src="<?= wire('config')->urls->templates; ?>assets/js/<?= Twack::getManifestFilename('polyfills.js'); ?>"></script>

	<?php
    // All scripts are added to the component using the addScript() method:
    foreach (wire('config')->scripts as $file) {
        if (strpos($file, 'image_service') !== false) {
            if (strpos($file, '.legacy.') !== false) {
				echo "\n\t<script nomodule src='$file'></script>";
				continue;
			}
			echo "\n\t<script type='module' src='$file'></script>";
			continue;
        }
		
        if (strpos($file, '.legacy.') !== false) {
            echo "\n\t<script async nomodule src='$file'></script>";
            continue;
        }
        echo "\n\t<script async type='module' src='$file'></script>";
    }
    ?>
</body>

</html>