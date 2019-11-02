<?php
namespace ProcessWire;

if ($this->childComponents && count($this->childComponents) > 0) {
	?>
	<section class="container-fluid section section_articles_carousel <?= $this->page->highlight ? 'highlight' : ''; ?> <?= !empty($this->page->classes.'') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="'.$this->sectionId.'"' : ''; ?>>
		<?php
		if (!empty($this->title)) {
			?>
			<h2 class="section-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->title; ?>
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