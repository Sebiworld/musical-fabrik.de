<?php

namespace ProcessWire;

?>

<article class="default_page">
	<?php
    if (!$this->locked) {
        if ($this->mainImage) {
            ?>
			<a class="main_image no-underline" data-open-imagelightbox="<?= $this->mainImage->url; ?>" href="<?= $this->mainImage->url; ?>" target="_blank">
				<?php
				if ($this->page->template->hasField('dont_crop_main_image') && $this->page->dont_crop_main_image) {
					// The image should not be cropped?>
					<div>
						<?php
							echo $this->imageService->getPictureHtml(array(
								'image'     => $this->mainImage,
								'alt'       => sprintf(__('Main-image of %1$s'), $this->title),
								'loadAsync' => true,
								'default'   => array(
									'width' => 800
								),
								'media' => array(
									'(max-width: 500px)' => array(
										'width' => 500
									)
								)
							)); ?>
					</div>
					<?php
				} else {
					// Bring the image to 2-to-1 aspect ratio:?>
					<div class="aspect-ratio ar-2-1">
						<?php
						echo $this->imageService->getPictureHtml(array(
							'image'          => $this->mainImage,
							'alt'            => sprintf(__('Main-image of %1$s'), $this->title),
							'pictureclasses' => array('ar-content'),
							'loadAsync'      => true,
							'default'        => array(
								'width'  => 800,
								'height' => 400
							),
							'media' => array(
								'(max-width: 500px)' => array(
									'width'  => 500,
									'height' => 250
								)
							)
						)); ?>
					</div>
					<?php
				}

				if ($this->mainImage->caption && !empty($this->mainImage->caption . '')) {
					echo '<div class="image-caption">' . $this->mainImage->caption . '</div>';
				} ?>
			</a>
			<?php
			} ?>

		<div class="contents-container">
			<?php
			if ($this->publishTimeString && $this->page->template->name !== 'default_page') {
				if ($this->authors) {
					?>
					<div class="meta">
						<?= sprintf(__('Published on %1$s from %2$s%3$s%4$s'), $this->publishTimeString, '<strong>', implode(' & ', $this->authors), '</strong>'); ?>
					</div>
					<?php
				} else {
					?>
					<div class="meta">
						<?= sprintf(__('Published on %1$s'), $this->publishTimeString); ?>
					</div>
					<?php
				}
			} ?>

			<h1 class="title <?= $this->hideTitle ? 'sr-only' : ''; ?>">
				<?= $this->title; ?>
			</h1>

			<?= $this->intro ? '<div class="intro">' . $this->intro . '</div>' : ''; ?>

			<?php
			if ($this->childComponents) {
				foreach ($this->childComponents as $component) {
					try {
						echo $component;
					} catch (\Exception $e) {
						Twack::devEcho($e->getMessage());
					}
				}
			} ?>

			<?= $this->tags; ?>

			<?= $this->comments; ?>

		</div>

		<?php
		if ($this->page->template->name === 'article') {
			?>
			<script type="application/ld+json">
				{
					"@context": "http://schema.org",
					"@type": "Article",
					"headline": "<?= $this->title; ?>",
					<?php
					if ($this->mainImage) {
						?>
						"image": "<?= $this->mainImage->httpUrl; ?>",
						<?php
					} ?>

					"datePublished": "<?= date(DATE_ATOM, wire('page')->created); ?>",
					"dateCreated": "<?= date(DATE_ATOM, wire('page')->created); ?>",
					"dateModified": "<?= date(DATE_ATOM, wire('page')->modified); ?>",
					"description": "<?= htmlentities($this->page->intro); ?>",

					"author": 
					<?php
					if ($this->authors && !empty($this->authors)) {
						echo '[';
						$firstFlag = true;
						foreach ($this->authors as $author) {
							if (!$firstFlag) {
								echo ',';
							} ?>
							{
								"@type": "Person",
								"name": "<?= htmlentities($author); ?>"
							}
							<?php
							$firstFlag = false;
						}
						echo '],';
					} else {
						?>
						{
							"@type": "Person",
							"name": "Musical-Fabrik e. V."
						},
						<?php
					} ?>

					"publisher": { 
						"@type": "Organization",
						"name": "Musical-Fabrik",
						"legalName": "Musical-Fabrik e. V.",
						"url": "https://www.musical-fabrik.de",
						"logo": {
							"@type": "ImageObject",
							"url": "https://www.musical-fabrik.de/site/templates/assets/static_img/logo_optimized.jpg",
							"width": "254",
							"height": "60"
						}
					},
					"url": "<?= $this->page->httpUrl; ?>",
					"mainEntityOfPage": {
						"@type": "WebPage",
						"@id": "https://google.com/article"
					}
				}
			</script>
			<?php
		} elseif ($this->page->template->name === 'project' || $this->page->template->name === 'project_voice_company') {
			?>
			<script type="application/ld+json">
			{
				"@context": "http://schema.org",
				"@type": "WebPage",
				"name": "<?= htmlentities($this->page->title); ?> (eine Produktion der Musical-Fabrik e. V.)",
				"description": "<?= htmlentities($this->page->intro); ?>",
				"publisher": { 
					"@type": "Organization",
					"name": "Musical-Fabrik",
					"legalName": "Musical-Fabrik e. V.",
					"url": "https://www.musical-fabrik.de",
					"logo": {
						"@type": "ImageObject",
						"url": "https://www.musical-fabrik.de/site/templates/assets/static_img/logo_optimized.jpg",
						"width": "254",
						"height": "60"
					}
				}
			}
			</script>
			<?php
		}
    }else{
		?>
		<div class="contents-container">
			<?php
			if ($this->publishTimeString && $this->page->template->name !== 'default_page') {
				if ($this->authors) {
					?>
					<div class="meta">
						<?= sprintf(__('Published on %1$s from %2$s%3$s%4$s'), $this->publishTimeString, '<strong>', implode(' & ', $this->authors), '</strong>'); ?>
					</div>
					<?php
				} else {
					?>
					<div class="meta">
						<?= sprintf(__('Published on %1$s'), $this->publishTimeString); ?>
					</div>
					<?php
				}
			} ?>

			<h1 class="title <?= $this->hideTitle ? 'sr-only' : ''; ?>">
				<?= $this->title; ?>
			</h1>
		</div>

		<form method="post" class="card" style="max-width: 100%; width: 550px;">
			<div class="row no-gutters">
    			<div class="col col-sm-4 d-none d-sm-flex">
					<svg class="card-img-top" viewBox="0 0 48 48" width="100%" xmlns="http://www.w3.org/2000/svg"><path d="m33 8c2.762 0 5 2.238 5 5s-2.238 5-5 5-5-2.238-5-5 2.238-5 5-5" fill="#ffb74d"/><path d="m28 9.001-3 2.999h13v-2.999z" fill="#263238"/><g fill="#3f51b5"><path d="m40 41.999c2 0 2-2 2-2v-15.999c0-2.761-2.238-5-5-5h-6c-2.762 0-5 2.239-5 5v17.999z"/><path d="m16.998 41c-.351 0-.705-.092-1.027-.285-.947-.568-1.254-1.797-.686-2.744l8.715-13.972c3-5 6-5 8-5v3l-13.285 18.03c-.375.625-1.037.971-1.717.971zm10.002-35.999a1 1 0 1 0 0 2 1 1 0 1 0 0-2z"/><path d="m27 5.001s-1 0-1 1 2 2.999 2 2.999h10s0 .002 0-2c0-1-1-1-1-1s-5 0-10-.999z"/></g><path d="m26 35h11v2.999h-11z" fill="#e8eaf6"/><path d="m37.332 19.033c-.112-.008-.219-.033-.332-.033h-1.871l-9.129 15.975v1.025h2z" fill="#e8eaf6"/><path d="m26 26.999-7.732 13.531c.17-.141.327-.301.447-.501l7.285-9.886zm12 13-1-16s-.998 13.146-.999 16.073c0 .773.134 1.405.36 1.927h3.639c-1.104 0-2-.895-2-2z" fill="#1a237e"/><path d="m6 22h3v1h-3z" fill="#37474f"/><path d="m8 38v3c0 .553.447 1 1 1h13c.553 0 1-.447 1-1v-3zm0-15h-2c-.553 0-1 .447-1 1v13c0 .553.447 1 1 1h2z" fill="#ff8f00"/><path d="m8 38h15v2h-15z" fill="#d67c05"/></svg>
				</div>
				<div class="col col-12 col-sm-8">
					<div class="card-body">
						<h5 class="card-title">Passwort benötigt</h5>
						<div class="card-text mb-3">Die Inhalte dieser Seite sind geschützt. Bitte geben Sie das Passwort ein, um fortzufahren.</div>
						<div class="form-group required">
							<label for="password-c" class="form-control-label">Passwort</label>
							<div class="input-group">
								<input name="pw_input" id="password-c" required="required" size="25" maxlength="100" type="password" class="form-control "><span class="input-group-addon icon-placeholder"></span>
							</div>
						</div>
						<button class="btn btn-project-primary btn-form-send" type="submit">prüfen</button>
					</div>
				</div>
			</div>
		</form>
		<?php
	}
	?>
</article>