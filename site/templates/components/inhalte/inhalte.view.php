<?php
namespace ProcessWire;

if (wireCount($this->childComponents) > 0) {
	?>
	<div class="inhalte">
		<div class="inhalt-block">
			<?php
			$firstFlag = true;
			$unterElementeFlag = false;
			foreach ($this->childComponents as $inhaltskomponente) {
				$seite = $inhaltskomponente->getPage();
				if ($seite->depth == 0) {
					if ($unterElementeFlag) {
						echo "</div>";
					}
					$unterElementeFlag = false;

					if (!$firstFlag) {
						// Jedes Element der obersten Ebene bekommt einen eigenen inhalt-block.
						echo "</div>";
						echo "<div class=\"inhalt-block\">";
					}
				}
				$firstFlag = false;


				if ($seite->depth > 0) {
					if (!$unterElementeFlag) {
						echo "<div class=\"row\">";
					}
					$unterElementeFlag = true;

					// Aus dem Feld grid_breite am RepeaterMatrix-Element wird die Breite der Bootstrap-Columns ermittelt:
					$bootstrapKlassen = 'col-12';
					if ($seite->template->hasField('grid_breite') && $seite->grid_breite && is_object($seite->grid_breite->first()) && $seite->grid_breite->first()->id) {
						$id = $seite->grid_breite->first()->id;
						if ($id == 2) {
							// Hälfte
							$bootstrapKlassen = 'col-12 col-md-6';
						} elseif ($id == 3) {
							// Ein Drittel
							$bootstrapKlassen = 'col-12 col-md-6 col-lg-4';
						} elseif ($id == 4) {
							// Zwei Drittel
							$bootstrapKlassen = 'col-12 col-md-6 col-lg-8';
						} elseif ($id == 5) {
							// Ein Viertel
							$bootstrapKlassen = 'col-12 col-md-6 col-lg-3';
						} elseif ($id == 6) {
							// Zwei Viertel
							$bootstrapKlassen = 'col-12 col-md-6 col-lg-6';
						} elseif ($id == 7) {
							// Drei Viertel
							$bootstrapKlassen = 'col-12 col-md-6 col-lg-9';
						}
					}

					echo "<div class=\"inhalt-unterblock {$bootstrapKlassen}\">";
					echo $inhaltskomponente;
					echo "</div>";
				} else {
					echo $inhaltskomponente;
				}
			}

			if ($unterElementeFlag) {
				echo "</div>";
			}
			?>
		</div>
	</div>
<?php
}
