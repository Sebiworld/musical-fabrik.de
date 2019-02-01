<?php
namespace ProcessWire;

if ($this->bilder && !empty($this->bilder)) {
	?>
	<div class="bildergalerie bilder-masonry <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
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

		<div class="masonry-grid lightgallery">
			<div class="masonry-grid-sizer"></div>
			<?php
			$counter = 0;
			foreach ($this->bilder as $listenIndex => $bild) {
				?>
				<div class="masonry-grid-item">
					<a class="lightgallery-item" href="<?= $bild->url; ?>">
						<?php
						echo $this->component->getService('BildService')->getBildTag(
							array(
								'bild' => $bild,
								'ausgabeAls' => 'image',
								'benutzeProgressively' => true,
								'classes' => 'bild',
								'normal' => array(
									'width' => 800
								),
								'sm' => array(
									'width' => 500
								)
							)
						);
						?>
					</a>
					<?php
					if ($bild->caption && !empty($bild->caption.'')) {
						echo '<div class="bild-caption">'.$bild->caption.'</div>';
					}
					?>
				</div>
				<?php
				$counter++;
			}
			?>
		</div>
	</div>
	<?php
}
