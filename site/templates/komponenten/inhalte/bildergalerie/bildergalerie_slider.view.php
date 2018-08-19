<?php
namespace ProcessWire;

if ($this->bilder && !empty($this->bilder)) {
	?>
	<div class="bildergalerie bilder-slider <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
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
		<?= $this->beschreibung ? '<div>'.$this->beschreibung	.'</div>' : ''; ?>

		<ul class="lightslider">
			<?php
			foreach ($this->bilder as $bild) {
				?>
				<li class="lightslider-item" data-thumb="<?= $bild->height(300)->url; ?>" data-src="<?= $bild->url; ?>" data-responsive="<?= $bild->height(300)->url; ?> 400w, <?= $bild->height(1000)->url; ?> 1000w">
					<img class="bildergalerie-bild <?= $bild->klassen ? $bild->klassen : ''; ?>" src="<?= $bild->height(300)->url; ?>" alt="<?= $bild->description; ?>"/>
				</li>
				<?php
			}
			?>
		</ul>
	</div>
	<?php
}
