<?php
namespace ProcessWire;

if ($this->audiodateien && !empty($this->audiodateien)) {
	?>
	<div class="inhalte-dateien inhalte-audiodateien<?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
		<?php
		if (!empty($this->titel)) {
			$headingDepth = 2;
			if ($this->page->depth && intval($this->page->depth)) {
				$headingDepth = $headingDepth + intval($this->page->depth);
			}
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
			foreach ($this->audiodateien as $datei) {
				$dateiname = $datei->basename;
				if (!empty($datei->description)) {
					$dateiname = $datei->description;
				}
				?>

				<li class="media datei audiodatei">
					<i class="align-self-center icon ion-music-note"></i>
					<div class="media-body">
						<h<?= $headingDepth + 1; ?> class="dateiname"><?= $dateiname; ?></h<?= $headingDepth + 1; ?>>
						<small class="datei-meta"><?= $datei->filesizeStr; ?></small>

						<div class="audioplayer-wrapper">
							<audio controls preload="none" class="audioplayer"><source src="<?= $datei->url; ?>" type="<?= mime_content_type($datei->filename); ?>"/>
							</audio>
						</div>
					</div>
				</li>

				<?php
			}
			?>
		</ul>
	</div>
	<?php
}
