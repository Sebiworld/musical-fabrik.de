<?php
namespace ProcessWire;

if ($this->ausgaben && count($this->ausgaben) > 0) {
	?>
	<div class="dev_ausgabe">
		<span class="badge badge-warning">DEV-Ausgaben:</span>
		<?php
		foreach ($this->ausgaben as $ausgabe) {
			if (!isset($ausgabe->arguments) || empty($ausgabe->arguments)) {
				continue;
			}
			echo "<pre>";
			echo "<strong>";
			echo "DEV-Ausgabe";
			if (isset($ausgabe->filename) && !empty($ausgabe->filename)) {
				echo " in " . $ausgabe->filename;
			}

			if (isset($ausgabe->functionname) && !empty($ausgabe->functionname)) {
				echo ", Funktionsaufruf " . $ausgabe->functionname . "()";
			}

			if (isset($ausgabe->line) && !empty($ausgabe->line)) {
				echo ", Zeile " . $ausgabe->line;
			}
			echo ":</strong><br/>";

			foreach ($ausgabe->arguments as $argument) {
				var_dump($argument);
			}
			echo "</pre>";
			echo "<hr/>";
		}
		?>
	</div>
	<?php
}
?>