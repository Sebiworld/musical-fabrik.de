<?php
namespace ProcessWire;

?>

<div class="standardseite">
	<div class="container-fluid">
		<div class="row">
			<article class="inhaltsbereich <?= $this->component->getGlobalComponent('seitenleiste')->hasComponents(true) ? 'col-sm-8 order-sm-2 hat-seitenbereich' : 'col-sm-12' ?>">
				<?php
				if ($this->titelbild) {
					?>
					<div class="titelbild">
						<?php
						if ($this->page->template->hasField('titelbild_ganz_anzeigen') && $this->page->titelbild_ganz_anzeigen) {
							// Das Bild soll nicht beschnitten werden
							?>
							<div>
								<?php
								if ($this->titelbild->ext == 'svg') {
									echo $this->bildService->getBildTag(array(
										'bild' => $this->titelbild,
										'ausgabeAls' => 'image',
										'classes' => 'progressive',
										'benutzeProgressively' => false,
										'normal' => 'original',
										'vollbild-modal' => 'original'
									));
								} else {
									echo $this->bildService->getBildTag(array(
										'bild' => $this->titelbild,
										'ausgabeAls' => 'image',
										'classes' => 'progressive',
										'normal' => array(
											'height' => 800
										),
										'sm' => array(
											'height' => 400
										),
										'vollbild-modal' => array(
											'width' => 1400
										)
									));
								}
								?>
							</div>
							<?php
						} else {
							// Bild auf 2-zu-1-Seitenverhältnis bringen:
							?>
							<div class="seitenverhaeltnis sv-2-1">
								<?php
								if ($this->titelbild->ext == 'svg') {
									echo $this->bildService->getBildTag(array(
										'bild' => $this->titelbild,
										'ausgabeAls' => 'bg-image',
										'classes' => 'bg-bild sv-inhalt progressive',
										'benutzeProgressively' => false,
										'normal' => 'original',
										'vollbild-modal' => 'original'
									));
								} else {
									echo $this->bildService->getBildTag(array(
										'bild' => $this->titelbild,
										'ausgabeAls' => 'bg-image',
										'classes' => 'bg-bild sv-inhalt progressive',
										'normal' => array(
											'height' => 800
										),
										'sm' => array(
											'height' => 400
										),
										'vollbild-modal' => array(
											'width' => 1400
										)
									));
								}
								?>
							</div>
							<?php
						}

						if ($this->titelbild->caption && !empty($this->titelbild->caption.'')) {
							echo '<div class="bild-caption">'.$this->titelbild->caption.'</div>';
						}
						?>
					</div>
					<?php
				}
				?>

				<div class="inhalte-container">
					<?php
					if ($this->datum) {
						?>
						<div class="meta">
							Veröffentlicht am <strong><?= $this->datum; ?></strong>
							<?= $this->autoren ? ' von <strong>'.implode(' & ', $this->autoren) . '</strong>' : ''; ?>
						</div>
						<?php
					}
					?>

					<h1 class="titel"><?= $this->titel; ?></h1>

					<?= $this->einleitung ? '<div class="einleitung">'.$this->einleitung.'</div>' : ''; ?>

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

					<?= $this->schlagwoerter; ?>

					<?= $this->kommentare; ?>

				</div>

				<?php
				if ($this->page->template->name == 'beitrag') {
					?>
					<script type="application/ld+json">
						{
							"@context": "http://schema.org",
							"@type": "NewsArticle",
							"mainEntityOfPage": {
								"@type": "WebPage",
								"@id": "https://google.com/article"
							},
							"headline": "<?= $this->titel; ?>",
							<?php
							if ($this->titelbild) {
								?>
								"image": [
								"<?= $this->titelbild->httpUrl; ?>"
								],
								<?php
							}
							?>

							"datePublished": "<?= date(DATE_ATOM, wire('page')->created); ?>",
							"dateModified": "<?= date(DATE_ATOM, wire('page')->created); ?>",

							<?php
							if ($this->autoren && !empty($this->autoren)) {
								echo '"author" : [';
								$firstFlag = true;
								foreach ($this->autoren as $autor) {
									if (!$firstFlag) {
										echo ",";
									}
									?>
									{
										"@type": "Person",
										"name": "<?= $autor; ?>"
									}
									<?php
									$firstFlag = false;
								}
								echo '],';
							}
							?>

							"publisher": {
								"@type": "Organization",
								"name": "<?= $this->konfigurationsseite->kurztext; ?>",
								"logo": {
									"@type": "ImageObject"
									<?= $this->konfigurationsseite->logo_quadrat ? ',"url": "' . $this->konfigurationsseite->logo_quadrat->httpUrl . '"' : ''; ?>
								}
							}
							<?= $this->konfigurationsseite->einleitung ? ',"description": "' . $this->konfigurationsseite->einleitung . '",' : ''; ?>
						}
					</script>
					<?php
				}
				?>
			</article>

			<?php
			if ($this->component->getGlobalComponent('seitenleiste')->hasComponents(true)) {
				?>
				<div class="col-sm-4 order-sm-1 seitenbereich">
					<?= $this->component->getGlobalComponent('seitenleiste'); ?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>