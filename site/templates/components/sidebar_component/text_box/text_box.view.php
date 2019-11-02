<?php

namespace ProcessWire;

?>
<div class="box text_box">
	<?php
    if ($this->title) {
        ?>
		<h4 class="title"><?= $this->title; ?></h4>
		<?php
    }
    ?>
	<div class="text">
		<?= $this->text; ?>
	</div>
</div>