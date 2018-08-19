<?php
namespace ProcessWire;

if ($this->childComponents && count($this->childComponents) > 0) {
	?>
	<section class="container-fluid onepage-sektion sektion-aktuelles-carousel <?= $this->page->hervorgehoben ? 'hervorgehoben' : ''; ?> <?= !empty($this->page->klassen.'') ? $this->page->klassen : ''; ?>" <?= $this->sektionID ? 'id="'.$this->sektionID.'"' : ''; ?>>
		<?php
		if (!empty($this->titel)) {
			?>
			<h2 class="sektion-titel <?= $this->page->titel_verstecken ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->titel; ?>
			</h2>
			<?php
		}
		?>

		<?php
		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				echo $component;
			}
		}
		?>

		<?= $this->component->getComponent('carousel'); ?>
	</section>
	<?php
}
?>