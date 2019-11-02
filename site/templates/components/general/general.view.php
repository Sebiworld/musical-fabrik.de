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
	<?php
    if ($this->metas) {
        foreach ($this->metas->getArray() as $meta) {
            echo $meta . " \n";
        }
    }
    ?>

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

	<script type="application/ld+json">
		{
			"@context": "http://schema.org",
			"@type": "Organization",
			"url": "https://www.musical-fabrik.de",
			"name": "<?= $configPage->short_text; ?>",
			<?= $configPage->logo_square ? '"logo": "' . $configPage->logo_square->httpUrl . '",' : ''; ?>
			<?php
            if (($configPage->phone_number && !empty($configPage->phone_number)) || ($configPage->emailaddress && !empty($configPage->emailaddress))) {
                ?> "contactPoint": {
					"@type": "ContactPoint",
					<?= $configPage->phone_number ? '"telephone": "' . $configPage->phone_number . '",' : ''; ?>
					<?= $configPage->emailaddress ? '"email": "' . $configPage->emailaddress . '",' : ''; ?> "contactType": "Customer service"
				}
			<?php
            }
        ?>
		}
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

	<?= $this->component->getGlobalComponent('modals'); ?>
	<?= $this->component->getInlineStyles(); ?>

	<script src="<?= wire('config')->urls->templates; ?>assets/js/<?= Twack::getManifestFilename('polyfills.js'); ?>"></script>

	<?php
    // All scripts are added to the component using the addScript() method:
    foreach (wire('config')->scripts as $file) {
        if (strpos($file, '.legacy.') !== false) {
            echo "\n\t<script async nomodule src='$file'></script>";
        } else {
            echo "\n\t<script async type='module' src='$file'></script>";
        }
    }
    ?>
</body>

</html>