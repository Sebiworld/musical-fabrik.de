<?php
namespace ProcessWire;

?>
<section class="container-fluid section section_form <?= $this->page->highlight ? 'highlight' : ''; ?> <?= !empty($this->page->classes.'') ? $this->page->classes : ''; ?>" <?= $this->sectionId ? 'id="'.$this->sectionId.'"' : ''; ?>>

	<?php
	if (!empty($this->title)) {
		?>
		<h2 class="section-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
			<?= $this->title; ?>
		</h2>
		<?php
	}
	?>

	<?= $this->page->intro ? '<div class="intro">'.$this->page->intro.'</div>' : ''; ?>
	<?= $this->contents; ?>

	<?= $this->form; ?>

	<?= $this->page->freetext; ?>
</section>