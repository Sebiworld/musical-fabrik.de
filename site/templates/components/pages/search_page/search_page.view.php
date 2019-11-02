<?php
namespace ProcessWire;

?>

<form action="<?= $this->searchPage->url; ?>" method="GET">
	<div class="input-group">
		<input type="text" class="form-control" placeholder="<?= __('Search for...') ?>" name="q" value="<?= $this->query; ?>">
		<span class="input-group-btn">
			<button class="btn btn-primary" type="submit"><?= __('Search') ?></button>
		</span>
	</div>
</form>

<p class="lead">
	<?= sprintf('%1$s results found', $this->results->count); ?>
</p>

<div class="list-group without-border">
	<?php
	foreach ($this->results as $result) {
		?>
		<a href="<?= $result->url; ?>" class="list-group-item list-group-item-action flex-column align-items-start">
			<div class="d-flex w-100 justify-content-between">
				<h5><?= $result->title; ?></h5>
				<small><?= $result->datetime_from; ?></small>
			</div>
			<?php
			if ($result->intro) {
				?>
				<p>
					<?= $result->intro; ?>
				</p>
				<?php
			}
			if ($result->authors_readable) {
				?>
				<small><?= sprintf(__('By %1$s'), $result->authors_readable); ?></small>
				<?php
			}
			?>
		</a>
		<?php
	}
	?>
</div>