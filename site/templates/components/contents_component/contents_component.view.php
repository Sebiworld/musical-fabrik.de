<?php
namespace ProcessWire;

if (wireCount($this->childComponents) > 0) {
	?>
	<div class="contents_component">
		<div class="content-block">
			<?php
			$firstFlag = true;
			$subelementActiveFlag = false;
			foreach ($this->childComponents as $contentComponent) {
				$page = $contentComponent->getPage();
				if ($page->depth == 0) {
					if ($subelementActiveFlag) {
						echo "</div>";
					}
					$subelementActiveFlag = false;

					if (!$firstFlag) {
						// Each top level element gets its own content block.
						echo "</div>";
						echo "<div class=\"content-block\">";
					}
				}
				$firstFlag = false;


				if ($page->depth > 0) {
					if (!$subelementActiveFlag) {
						echo "<div class=\"row\">";
					}
					$subelementActiveFlag = true;

					// The width of the bootstrap columns is determined from the grid_width field at the RepeaterMatrix element:
					$bootstrapClasses = 'col-12';
					if ($page->template->hasField('grid_width') && $page->grid_width && is_object($page->grid_width->first()) && $page->grid_width->first()->id) {
						$id = $page->grid_width->first()->id;
						if ($id == 2) {
							// half
							$bootstrapClasses = 'col-12 col-md-6';
						} elseif ($id == 3) {
							// One third
							$bootstrapClasses = 'col-12 col-md-6 col-lg-4';
						} elseif ($id == 4) {
							// Two thirds
							$bootstrapClasses = 'col-12 col-md-6 col-lg-8';
						} elseif ($id == 5) {
							// A quarter
							$bootstrapClasses = 'col-12 col-md-6 col-lg-3';
						} elseif ($id == 6) {
							// Two quarters
							$bootstrapClasses = 'col-12 col-md-6 col-lg-6';
						} elseif ($id == 7) {
							// Three quarters
							$bootstrapClasses = 'col-12 col-md-6 col-lg-9';
						}
					}

					echo "<div class=\"inhalt-sub-block {$bootstrapClasses}\">";
					echo $contentComponent;
					echo "</div>";
				} else {
					echo $contentComponent;
				}
			}

			if ($subelementActiveFlag) {
				echo "</div>";
			}
			?>
		</div>
	</div>
<?php
}
