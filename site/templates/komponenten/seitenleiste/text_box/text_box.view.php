<?php namespace ProcessWire; ?>
<div class="box text-box">
	<?php
	if($this->titel){
		?>
		<h4 class="titel"><?= $this->titel; ?></h4>
		<?php
	}
	?>
	<div class="text">
		<?= $this->text; ?>
	</div>
</div>