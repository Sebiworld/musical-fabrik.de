<?php
namespace ProcessWire;

if ($this->dateien && !empty($this->dateien)) {
	$headingDepth = 2;
	if ($this->page->depth && intval($this->page->depth)) {
		$headingDepth = $headingDepth + intval($this->page->depth);
	}
	?>
	<div class="inhalte-dateien <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
		<?php
		if (!empty($this->titel)) {
			?>
			<h<?= $headingDepth; ?> class="block-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->titel; ?>
			</h<?= $headingDepth; ?>>
			<?php
		}
		?>

		<?= $this->beschreibung ? '<div class="block-beschreibung">'.$this->beschreibung	.'</div>' : ''; ?>

		<ul class="list-unstyled dateien">
			<?php
			foreach ($this->dateien as $datei) {
				$dateiname = $datei->basename;
				if (!empty($datei->description)) {
					$dateiname = $datei->description;
				}

				$typ = mime_content_type($datei->filename);
				if (substr($typ, 0, 5) === "audio") {
					// Audio-Datei, Player anzeigen:
					?>
					<li class="media datei audiodatei">
						<a href="<?= $datei->url; ?>" target="_blank" class="icon-wrapper" data-audioplayer-aktion="play" rel="noopener">
							<i class="icon ion-ios-musical-note"></i>
						</a>
						<div class="media-body">
							<h<?= $headingDepth + 1; ?> class="dateiname"><?= $dateiname; ?></h<?= $headingDepth + 1; ?>>
							<small class="datei-meta"><?= $datei->filesizeStr; ?></small>

							<div class="audioplayer-wrapper">
								<audio controls preload="none" class="audioplayer"><source src="<?= $datei->url; ?>" type="<?= $typ; ?>"/>
								</audio>
							</div>
						</div>
					</li>
					<?php
				} else {
					// Normaler Datei-Download
					?>
					<li class="media datei">
						<a href="<?= $datei->url; ?>" target="_blank" class="icon-wrapper" rel="noopener">
							<i class="icon ion-ios-cloud-download"></i>
						</a>
						<div class="media-body">
							<a href="<?= $datei->url; ?>" target="_blank" rel="noopener">
								<h<?= $headingDepth + 1; ?> class="dateiname"><?= $dateiname; ?></h<?= $headingDepth + 1; ?>>
								<small class="datei-meta"><?= $datei->filesizeStr; ?></small>
							</a>
						</div>
					</li>
					<?php
				}
			}
			?>
		</ul>
	</div>
	<?php
}
