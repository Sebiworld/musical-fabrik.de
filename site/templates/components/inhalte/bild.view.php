<?php
namespace ProcessWire;

if ($this->page->bild) {
	?>
	<div class="inhalte-einzelbild einzelbild" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
		<?php
		if (!empty($this->page->title)) {
			$headingDepth = 2;
			if ($this->page->depth && intval($this->page->depth)) {
				$headingDepth = $headingDepth + intval($this->page->depth);
			}
			?>
			<h<?= $headingDepth; ?> class="block-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->page->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
		}
		?>

		<div class="<?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>">
			<?php
			if ($this->page->bild->ext == 'svg') {
				echo $this->component->getService('BildService')->getBildTag(array(
					'bild' => $this->page->bild,
					'ausgabeAls' => 'image',
					'benutzeProgressively' => false,
					'normal' => 'original'
				));
			} else {
				echo $this->component->getService('BildService')->getBildTag(array(
					'bild' => $this->page->bild,
					'ausgabeAls' => 'image',
					'normal' => array(
						'width' => 1400
					),
					'sm' => array(
						'width' => 600
					),
					'vollbild-modal' => array(
						'width' => 1400
					)
				));
			}

			if ($this->page->bild->caption && !empty($this->page->bild->caption.'')) {
				echo '<div class="bild-caption">'.$this->page->bild->caption.'</div>';
			}
			?>
		</div>
	</div>
	<?php
}
?>
