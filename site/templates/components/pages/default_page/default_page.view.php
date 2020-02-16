<?php
namespace ProcessWire;

?>

<div class="default_page">
	<div class="container-fluid">
		<div class="row">
			<article class="contents-area <?= $this->component->getGlobalComponent('sidebar')->hasComponents(true) ? 'col-sm-8 order-sm-2 has-sidebar' : 'col-sm-12' ?>">
				<?php
				if ($this->mainImage) {
					?>
					<a class="main_image" data-open-imagelightbox="<?= $this->mainImage->url; ?>" href="<?= $this->mainImage->url; ?>" target="_blank">
						<?php
						if ($this->page->template->hasField('dont_crop_main_image') && $this->page->dont_crop_main_image) {
							// The image should not be cropped
							?>
							<div>
								<?php
									echo $this->imageService->getPictureHtml(array(
										'image' => $this->mainImage,
										'alt' => sprintf(__('Main-image of %1$s'), $this->title),
										'loadAsync' => true,
										'default' => array(
											'width' => 800
										),
										'media' => array(
											'(max-width: 500px)' => array(
												'width' => 500
											)
										)
									));
								?>
							</div>
							<?php
						} else {
							// Bring the image to 2-to-1 aspect ratio:
							?>
							<div class="aspect-ratio ar-2-1">
								<?php
								echo $this->imageService->getPictureHtml(array(
									'image' => $this->mainImage,
									'alt' => sprintf(__('Main-image of %1$s'), $this->title),
									'pictureclasses' => array('ar-content'),
									'loadAsync' => true,
									'default' => array(
										'width' => 800,
										'height' => 400
									),
									'media' => array(
										'(max-width: 500px)' => array(
											'width' => 500,
											'height' => 250
										)
									)
								));
								?>
							</div>
							<?php
						}

						if ($this->mainImage->caption && !empty($this->mainImage->caption.'')) {
							echo '<div class="image-caption">'.$this->mainImage->caption.'</div>';
						}
						?>
					</a>
					<?php
				}
				?>

				<div class="contents-container">
					<?php
					if ($this->publishTimeString && $this->authors) {
						?>
						<div class="meta">
							<?= sprintf(__('Published on %1$s from %2$s%3$s%4$s'), $this->publishTimeString, '<strong>', implode(' & ', $this->authors), '</strong>'); ?>
						</div>
						<?php
					}else if ($this->publishTimeString) {
						?>
						<div class="meta">
							<?= sprintf(__('Published on %1$s'), $this->publishTimeString); ?>
						</div>
						<?php
					}
					?>

					<h1 class="title"><?= $this->title; ?></h1>

					<?= $this->intro ? '<div class="intro">'.$this->intro.'</div>' : ''; ?>

					<?php
					if ($this->childComponents) {
						foreach ($this->childComponents as $component) {
							try {
								echo $component;
							} catch (\Exception $e) {
								Twack::devEcho($e->getMessage());
							}
						}
					}
					?>

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
							}
							?>

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
										echo ",";
									}
									?>
									{
										"@type": "Person",
										"name": "<?= htmlentities($author); ?>"
									}
									<?php
									$firstFlag = false;
								}
								echo '],';
							}else{
								?>
								{
									"@type": "Person",
									"name": "Musical-Fabrik e. V."
								},
								<?php
							}
							?>

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
				}else if($this->page->template->name === 'project' || $this->page->template->name === 'project_voice_company'){
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
				?>
			</article>

			<?php
			if ($this->component->getGlobalComponent('sidebar')->hasComponents(true)) {
				?>
				<div class="col-sm-4 order-sm-1 sidebar-wrapper">
					<?= $this->component->getGlobalComponent('sidebar'); ?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>