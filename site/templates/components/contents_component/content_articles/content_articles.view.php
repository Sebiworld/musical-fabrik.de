<?php

namespace ProcessWire;

if ($this->contentAvailable) {
    ?>
	<div class="content_articles results-wrapper <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
		<?php
        if (!empty($this->title)) {
            $headingDepth = 2;
            if ($this->page->depth && intval($this->page->depth)) {
                $headingDepth = $headingDepth + intval($this->page->depth);
            } ?>
			<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'visually-hidden visually-hidden-focusable' : ''; ?>">
				<?= $this->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
        }

    if ($this->childComponents) {
        foreach ($this->childComponents as $component) {
            echo $component;
        }
    } ?>
	</div>
	<?php
}
?>
