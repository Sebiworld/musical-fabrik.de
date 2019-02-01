<?php
namespace ProcessWire;

if ($this->inhalte_vorhanden) {
	?>
	<div class="inhalte-aktuelles aktuelles-inhalte <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->page->depth ? 'data-tiefe="' . $this->page->depth . '"' : ''; ?>>
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
		
		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				echo $component;
			}
		}
		?>
	</div>
	<?php
}
?>
