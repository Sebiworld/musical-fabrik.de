<?php

namespace ProcessWire;

if ($this->files && count($this->files) > 0) {
    ?>
	<div class="box files_box">
		<?php
        if ($this->title) {
            ?>
			<h4 class="title"><?= $this->title; ?></h4>
			<?php
        } ?>
		<ul class="files">
			<?php
            foreach ($this->files as $file) {
                ?>
				<li>
					<a target="_blank" href="<?= $file->url; ?>" title="<?= __('Download file'); ?>" rel="noopener">
						<h4 class="title"><?= $file->description ? $file->description : $file->name; ?></h4>
					</a>
				</li>
				<?php
            } ?>
		</ul>
	</div>
	<?php
}
?>