<?php
namespace ProcessWire;

if ($this->modalkomponenten) {
	foreach ($this->modalkomponenten as $komponente) {
		echo $komponente;
	}
}
