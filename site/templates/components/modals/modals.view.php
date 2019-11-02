<?php
namespace ProcessWire;

if ($this->modalcomponents) {
	foreach ($this->modalcomponents as $component) {
		echo $component;
	}
}
