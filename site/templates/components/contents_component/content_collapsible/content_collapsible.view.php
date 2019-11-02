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
			<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
        } ?>
		<div class="collapsible-element" id="<?= $this->id; ?>">
			<?php
            foreach ($this->tabs as $tab) {
                ?>
				<div class="card">
					<div class="card-header bg-dark" id="heading-<?= $tab->id; ?>">
						<a data-toggle="collapse" data-target="#<?= $tab->id; ?>" data-parent="#<?= $this->id; ?>" href="#<?= $tab->id; ?>" aria-controls="<?= $tab->id; ?>" data-expanded="false">
							<h2 class="collapsible-label">
								<?= $tab->title; ?>
							</h2>
						</a>
					</div>

					<div id="<?= $tab->id; ?>" class="collapse" role="tabpanel" aria-labelledby="heading-<?= $tab->id; ?>" data-parent="#<?= $this->id; ?>">
						<div class="card-block">
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