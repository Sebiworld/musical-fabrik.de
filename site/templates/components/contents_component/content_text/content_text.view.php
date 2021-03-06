<?php

namespace ProcessWire;

?>
<div class="content_text <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
	<?php
    if (!empty($this->page->title)) {
        $headingDepth = 2;
        if ($this->page->depth && intval($this->page->depth)) {
            $headingDepth = $headingDepth + intval($this->page->depth);
        } ?>
		<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'visually-hidden visually-hidden-focusable' : ''; ?>">
			<?= $this->page->title; ?>
		</h<?= $headingDepth; ?>>
		<?php
    }
    ?>
	<?= $this->page->text; ?>
</div>