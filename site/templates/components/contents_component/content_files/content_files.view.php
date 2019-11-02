<?php

namespace ProcessWire;

if ($this->files && !empty($this->files)) {
    $headingDepth = 2;
    if ($this->page->depth && intval($this->page->depth)) {
        $headingDepth = $headingDepth + intval($this->page->depth);
    } ?>
	<div class="content_files <?= !empty($this->page->classes . '') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
		<?php
        if (!empty($this->title)) {
            ?>
			<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'sr-only sr-only-focusable' : ''; ?>">
				<?= $this->title; ?>
			</h<?= $headingDepth; ?>>
			<?php
        } ?>

		<?= $this->description ? '<div class="block-description">' . $this->description . '</div>' : ''; ?>

		<ul class="list-unstyled files">
			<?php
            foreach ($this->files as $file) {
                $filename = $file->basename;
                if (!empty($file->description)) {
                    $filename = $file->description;
                }

                $typ = mime_content_type($file->filename);
                if (substr($typ, 0, 5) === 'audio') {
                    // Audio file, show player:?>
					<li class="media file audiofile">
						<a href="<?= $file->url; ?>" target="_blank" class="icon-wrapper" data-audioplayer-action="play" rel="noopener">
							<i class="icon ion-ios-musical-note"></i>
						</a>
						<div class="media-body">
							<h<?= $headingDepth + 1; ?> class="filename"><?= $filename; ?></h<?= $headingDepth + 1; ?>>
							<small class="file-meta"><?= $file->filesizeStr; ?></small>

							<div class="audioplayer-wrapper">
								<audio controls preload="none" class="audioplayer"><source src="<?= $file->url; ?>" type="<?= $typ; ?>"/>
								</audio>
							</div>
						</div>
					</li>
					<?php
                } else {
                    // Normal file download
                    ?>
					<li class="media file">
						<a href="<?= $file->url; ?>" target="_blank" class="icon-wrapper" rel="noopener">
							<i class="icon ion-ios-cloud-download"></i>
						</a>
						<div class="media-body">
							<a href="<?= $file->url; ?>" target="_blank" rel="noopener">
								<h<?= $headingDepth + 1; ?> class="filename"><?= $filename; ?></h<?= $headingDepth + 1; ?>>
								<small class="file-meta"><?= $file->filesizeStr; ?></small>
							</a>
						</div>
					</li>
					<?php
                }
            } ?>
		</ul>
	</div>
	<?php
}
