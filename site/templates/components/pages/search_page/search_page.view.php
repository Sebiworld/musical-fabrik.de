<?php
namespace ProcessWire;
?>
<div class="search_page results-wrapper">
	<?php

	// Output filter, if available:
	if ($this->filters) {
		echo $this->filters;
	}
	
	?>
	<div class="results-container" data-request-url="<?= $this->requestUrl; ?>">
		<?php
		if ($this->totalNumber) {
			?>
			<i class="total-number"><?= sprintf(_n("One result found", "%d results found", $this->totalNumber), $this->totalNumber); ?></i>
			<?php
		}
		?>
	
		<?php
		if ($this->childComponents && count($this->childComponents) > 0) {
			?>
			<div class="masonry-grid">
				<div class="masonry-grid-sizer"></div>
				<?php
				foreach ($this->childComponents as $result) {
					?>
					<div class="masonry-grid-item">
						<?= $result; ?>
					</div>
					<?php
				} ?>
			</div>
	
			<?php
		} else {
			?>
			<div class="masonry-grid">
				<div class="masonry-grid-sizer"></div>
			</div>
	
			<div class="alert alert-info no-results" role="alert">
				<strong><?= __('No results found'); ?></strong><br/>
				<?= __('Expand the filter settings to get more results.'); ?>
			</div>
			<?php
		}
		?>

		<div class="btn-group-wrapper">
			<div class="btn-group" role="group">
				<button type="button" class="btn btn-project-primary <?= !$this->moreAvailable ? 'd-none' : ''; ?>" data-action="load-more" data-offset="<?= $this->lastElementIndex + 1; ?>"><?= __('Load more...'); ?></button>
			</div>
		</div>
	</div>
</div>