<?php

namespace ProcessWire;

?>
<div class="sidebar <?= $this->classes ? implode(' ', $this->classes) : ''; ?>">
	<?php
    foreach ($this->childComponents as $component) {
        echo $component;
    }
    ?>
</div>