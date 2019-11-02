<?php
namespace ProcessWire;

?>
<div class="filters_component">
	<div class="form-row">
		<?php
		if ($this->tags) {
			?>
			<div class="col-12 col-md-6">
				<label><?= __('Select keywords:'); ?></label>
				<?= $this->tags; ?>
			</div>
			<?php
		}
		?>

		<div class="col-12 col-md-6">
			<div class="form-group">
				<label for="q"><?= __('Search'); ?></labƒel>
				<input type="text" class="form-control" id="q" name="q" placeholder="<?= __('Enter your query'); ?>" value="<?= $this->q; ?>"/>
			</div>
			<button type="submit" class="btn btn-project-primary" name="search"><?= __('Search'); ?></button>
		</div>
	</div>
</div>