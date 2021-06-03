<?php

namespace ProcessWire;

if ($this->tabs && count($this->tabs) > 0) {
    ?>
	<div class="content_collapsible <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
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
        } ?>
		<div class="accordion" id="<?= $this->id; ?>">
			<?php
            foreach ($this->tabs as $tab) {
                ?>
				<div class="accordion-item">
					<h2 class="accordion-header" id="heading-<?= $tab->id; ?>">
						<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $tab->id; ?>" aria-expanded="false" aria-controls="<?= $tab->id; ?>">
							<?= $tab->title; ?>
						</button>
					</h2>

					<div id="<?= $tab->id; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $tab->id; ?>" data-bs-parent="#<?= $this->id; ?>">
      			<div class="accordion-body">
							<?= $tab->content; ?>
						</div>
					</div>
				</div>
				<?php
            } ?>
		</div>
	</div>
	<?php
}
?>