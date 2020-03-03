<?php
namespace ProcessWire;

/*
Basic output for a role (heading, group picture, introductory text, content)
*/

?>
<div class="card page_card <?= !empty($this->classes . '') ? $this->classes : ''; ?>" <?= !empty($this->attributeString . '') ? $this->attributeString : ''; ?>>
    <?php
    if ($this->childComponents) {
        foreach ($this->childComponents as $component) {
            echo $component->render('html');
        }
    }
    ?>
</div>
