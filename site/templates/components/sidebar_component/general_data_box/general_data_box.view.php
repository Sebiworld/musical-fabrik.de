<?php

namespace ProcessWire;

if ($this->dataCollection && count($this->dataCollection) > 0) {
    ?>

	<div class="box general_data_box">
		<?php
        if ($this->title) {
            ?>
			<h4 class="title"><?= $this->title; ?></h4>
			<?php
		} 
		
        foreach ($this->dataCollection as $dataitem) {
            ?>
			<div class="data-item depth-<?= $dataitem->depth; ?>">
				<?php
                if (isset($dataitem->link)) {
                    ?>
					<a class="data-item-link" href="<?= $dataitem->link; ?>" <?= isset($dataitem->linktitle) ? 'title="' . $dataitem->linktitle . '"' : ''; ?>>
						<?= $dataitem->label; ?>
					</a>
					<?php
                } else {
                    echo $dataitem->label;
                } ?>
			</div>
			<?php
        } ?>

	</div>
	<?php
}
?>