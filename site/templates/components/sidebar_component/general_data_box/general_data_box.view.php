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
				$indicator = '&rsaquo;&nbsp; ';
				if($dataitem->depth && $dataitem->depth > 0){
					$indicator = '<i class="icon ion-ios-arrow-round-forward"></i>&nbsp; ';
				}
                if (isset($dataitem->link)) {
                    ?>
					<a class="data-item-link" href="<?= $dataitem->link; ?>" <?= isset($dataitem->linktitle) ? 'title="' . $dataitem->linktitle . '"' : ''; ?>>
						<?= $indicator . $dataitem->label; ?>
					</a>
					<?php
                } else {
                    echo $indicator . $dataitem->label;
                } ?>
			</div>
			<?php
        } ?>

	</div>
	<?php
}
?>