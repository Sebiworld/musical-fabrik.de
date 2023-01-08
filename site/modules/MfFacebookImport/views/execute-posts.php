<?php
namespace ProcessWire;

if (isset($locked) && $locked === true) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	return;
}
if (!wire('user')->hasPermission(MfFacebookImport::managePermission)) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('You don\'t have the needed permissions to access this function. Please contact a Superuser.') . '</p>';
	return;
}
?>

<div class="alert-container">
	<?php
	foreach ($alerts as $alert) {
		$styles = 'background: #cffbfc; border: 1px solid #087990;';
		if ($alert['type'] === 'error') {
			$styles = 'background: #f8d7da; border: 1px solid #b02a37;';
		} else if ($alert['type'] === 'warning') {
			$styles = 'background: #fff3cd; border: 1px solid #997404;';
		} else if ($alert['type'] === 'success') {
			$styles = 'background: #d1e7dd; border: 1px solid #146c43;';
		} ?>
	<div class="alert"
		style="padding: 8px 16px; margin-bottom: 16px; width: 600px; max-width: 100%; <?= $styles; ?>">
		<?= $alert['message']; ?>
	</div>
	<?php
	}
?>
</div>

<?php

$table = $modules->get('MarkupAdminDataTable');
$table->setEncodeEntities(false);

$table->headerRow([
	$this->_('Id'),
	$this->_('Date'),
	$this->_('Created by'),
	$this->_('Message'),
	$this->_('Status'),
	$this->_('Actions')
]);

if (!empty($posts)) {
	foreach ($posts as $post) {
		if (empty($post['id'])) {
			continue;
		}

		$actions = [];

		$modificationHash = md5(rand());

		$statusIcon = '';
		$existingPage = $this->pages->findOne('include=all,external_id=' . $post['id']);
		if ($existingPage instanceof NullPage || empty($existingPage->id)) {
			// Not imported
			$statusIcon = '<i class="fa fa-circle-o" title="' . $this->_('Not imported') . '"></i>';
			$actions[] = '<a href="' . $this->wire('page')->url . 'posts/?import=' . $post['id'] . '&mid=' . $modificationHash . '"><i class="fa fa-download"></i></a>';
		} else if ($existingPage->external_modification_hash && $existingPage->external_modification_hash !== md5(json_encode($post))) {
			// Imported but updatable
			$statusIcon = '<i class="fa fa-exclamation-circle" title="' . $this->_('Imported, but updatable') . '"></i>';
			$actions[] = '<a href="' . $this->wire('page')->url . 'posts/?import=' . $post['id'] . '&mid=' . $modificationHash . '"><i class="fa fa-download"></i></a>';

			$actions[] = '<a href="' . $existingPage->editUrl . '" target="_blank"><i class="fa fa-pencil-square-o"></i></a>';
		} else {
			// Imported
			$statusIcon = '<i class="fa fa-check-circle" title="' . $this->_('Imported successfully') . '"></i>';
			$actions[] = '<a href="' . $this->wire('page')->url . 'posts/?import=' . $post['id'] . '&mid=' . $modificationHash . '"><i class="fa fa-download"></i></a>';

			$actions[] = '<a href="' . $existingPage->editUrl . '" target="_blank"><i class="fa fa-pencil-square-o"></i></a>';
		}

		if ($post['permalink_url']) {
			$actions[] = '<a href="' . $post['permalink_url'] . '" target="_blank"><i class="fa fa-external-link"></i></a>';
		}

		$shortId = explode('_', $post['id'])[1] ?? explode('_', $post['id'])[0];

		$row = [
			'<span title="' . $post['id'] . '">' . $shortId . '</span>' ?? '',
			formatDate($post['created_time'] ?? '') . '<br>' . formatDate($post['updated_time'] ?? ''),
			'<span style="white-space: nowrap;">' . ($post['from']['name'] ?? '') . '</span>',
			$post['message'] ?? '',
			$statusIcon,
			implode(' &nbsp; ', $actions)
		];

		$table->row($row);
	}

	$tableOutput = $table->render();
}

function formatDate($timestamp) {
	if (empty($timestamp)) {
		return '';
	}
	return '<span style="white-space: nowrap;">' . date('Y-m-d H:i:s', wire('datetime')->strtotime($timestamp)) . '</span>';
}

if (empty($tableOutput)) {
	$tableOutput = '<p>No posts found</p>';
}

echo $tableOutput;

// Build form:
$form = $this->wire('modules')->get('InputfieldForm');
$form->method = 'POST';
$form->action = $this->wire('page')->url . 'posts/';

$field = $this->modules->get('InputfieldSubmit');
$field->text = 'Refresh';
$field->value = time();
$field->name = 'action-refresh';
$field->href = $this->wire('page')->url . 'posts/';
$field->header = true;
$field->icon = 'refresh';
$form->add($field);

echo $form->render();
?>

<p style='padding-top: 20px;'>
	<a href='<?= $this->wire('page')->url; ?>'>
		<i
			class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
	</a>
	&nbsp;
	<button class="ui-button ui-widget ui-corner-all ui-state-default mfimport-collapse-btn">
		<?= $this->_('Show Raw Response'); ?>
	</button>
</p>


<div class="mfimport-collapsible" style="display: none">
	<h3><?= $this->_('Raw Output: '); ?></h3>
	<pre><?= json_encode($response, JSON_PRETTY_PRINT) ?></pre>
</div>