<?php
namespace ProcessWire;

?>
<div class="seitenleiste <?= $this->klassen ? implode(' ', $this->klassen) : ''; ?>">
	<?php
	foreach ($this->childComponents as $component) {
		echo $component;
	}
	?>
</div>