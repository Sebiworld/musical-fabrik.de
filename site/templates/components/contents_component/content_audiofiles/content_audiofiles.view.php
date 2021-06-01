<?php

namespace ProcessWire;

if ($this->audiofiles && !empty($this->audiofiles)) {
    ?>
	<div class="content_files content_audiofiles<?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
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
		<?= $this->description ? '<div class="block-description">' . $this->description . '</div>' : ''; ?>

		<ul class="list-unstyled dateien">
			<?php
            foreach ($this->audiofiles as $file) {
                $filename = $file->basename;
                if (!empty($file->description)) {
                    $filename = $file->description;
                } ?>

				<li class="media file audiofile">
					<i class="align-self-center icon ion-music-note"></i>
					<div class="media-body">
						<h<?= $headingDepth + 1; ?> class="filename"><?= $filename; ?></h<?= $headingDepth + 1; ?>>
						<small class="file-meta"><?= $file->filesizeStr; ?></small>

						<div class="audioplayer-wrapper">
							<audio controls preload="none" class="audioplayer"><source src="<?= $file->url; ?>" type="<?= mime_content_type($file->filename); ?>"/>
							</audio>
						</div>
					</div>
				</li>

				<?php
            } ?>
		</ul>
	</div>
	<?php
}
