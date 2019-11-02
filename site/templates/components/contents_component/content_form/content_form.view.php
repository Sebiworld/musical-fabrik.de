<?php

namespace ProcessWire;

?>

<div class="content_form <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
	<?php
    if (!empty($this->title)) {
        $headingDepth = 2;
        if ($this->page->depth && intval($this->page->depth)) {
            $headingDepth = $headingDepth + intval($this->page->depth);
        } ?>
		<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
			<?= $this->title; ?>
		</h<?= $headingDepth; ?>>
		<?php
    }
    ?>
	<?= $this->form; ?>
</div>