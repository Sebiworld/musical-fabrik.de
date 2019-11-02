<?php
namespace ProcessWire;

?>

<div class="modal fade" id="<?= $this->id ?>" tabindex="-1" role="dialog" aria-labelledby="<?= $this->id ?>Label">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="<?= __('Close'); ?>"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="<?= $this->id ?>Label"><?= __('Request for "%1$s"', $this->page->title); ?></h4>
			</div>
			<div class="modal-body">

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-link btn-close-modal" data-dismiss="modal"><?= __('Dismiss'); ?></button>
				<button class="btn btn-success btn-send-modal" type="submit"><?= __('Send request'); ?></button>
			</div>
		</div>
	</div>
</div>