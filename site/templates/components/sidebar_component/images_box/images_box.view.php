<?php

namespace ProcessWire;

if ($this->galleriesPage) {
    ?>
	<div class="box images_box">
		<?php
        if ($this->title) {
            ?>
			<h4 class="title"><?= $this->title; ?></h4>
			<?php
        }

		foreach ($this->childComponents as $gallery) {
			echo $gallery;
		} ?>

		<a href="<?= $this->galleriesPage->url; ?>" class="btn btn-light btn-sm"><?= __('All Galleries') ?></a>
	</div>
	<?php
}
?>