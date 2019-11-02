<?php

namespace ProcessWire;

if ($this->outputs && count($this->outputs) > 0) {
    ?>
	<div class="dev_output">
		<span class="badge badge-warning">DEV-Output:</span>
		<?php
        foreach ($this->outputs as $output) {
            if (!isset($output->arguments) || empty($output->arguments)) {
                continue;
            }
            echo '<pre>';
            echo '<strong>';
            echo 'DEV-Echo';
            if (isset($output->filename) && !empty($output->filename)) {
                echo ' in ' . $output->filename;
            }

            if (isset($output->functionname) && !empty($output->functionname)) {
                echo ', Functioncall ' . $output->functionname . '()';
            }

            if (isset($output->line) && !empty($output->line)) {
                echo ', in line ' . $output->line;
            }
            echo ':</strong><br/>';

            foreach ($output->arguments as $argument) {
                var_dump($argument);
            }
            echo '</pre>';
            echo '<hr/>';
        } ?>
	</div>
	<?php
}
?>