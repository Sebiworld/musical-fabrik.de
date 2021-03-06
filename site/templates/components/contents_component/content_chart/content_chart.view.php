<?php
namespace ProcessWire;

?>

<div class="content_chart <?= !empty($this->page->classes.'') ? $this->page->classes : ''; ?>" <?= $this->page->depth ? 'data-depth="' . $this->page->depth . '"' : ''; ?>>
	<?php
	if (!empty($this->title)) {
		$headingDepth = 2;
		if ($this->page->depth && intval($this->page->depth)) {
			$headingDepth = $headingDepth + intval($this->page->depth);
		}
		?>
		<h<?= $headingDepth; ?> class="block-title <?= $this->page->hide_title ? 'visually-hidden visually-hidden-focusable' : ''; ?>">
			<?= $this->title; ?>
		</h<?= $headingDepth; ?>>
		<?php
	}
	?>

	<div class="chart-container" data-page="<?= $this->page->id; ?>" data-chart-id="<?= $this->chart_id; ?>">
		<canvas class="chart"></canvas>
	</div>
</div>
