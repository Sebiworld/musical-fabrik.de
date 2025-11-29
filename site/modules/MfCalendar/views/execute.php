<?php
namespace ProcessWire;

if (isset($locked) && $locked === true) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	return;
}
if (!wire('user')->hasPermission(MfCalendar::managePermission)) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('You don\'t have the needed permissions to access this function. Please contact a Superuser.') . '</p>';
	return;
}

$table = $modules->get('MarkupAdminDataTable');
$table->setEncodeEntities(false);

$table->headerRow([
	$this->_('Title'),
	$this->_('Time From'),
	$this->_('Time Until'),
	$this->_('Timespans'),
	$this->_('Status'),
	$this->_('Actions')
]);

if ($events instanceof WireArray && $events->count > 0) {
	foreach ($events as $event) {
		$status = $event->getStatus();
		$statusItem = $stati->findOne('name=' . $event->getStatus());
		if (!empty($statusItem->getTitle())) {
			$status = $statusItem->getTitle() . ' (' . $statusItem->getName() . ')';
		}

		$row = [
			'<a href="' . $this->wire('page')->url . 'event/edit/' . $event->getID() . '">' . $event->getTitle() . '</a>',
			'',
			'',
			'',
			$status,
			'<a href="' . $this->wire('page')->url . 'event/delete/' . $event->getID() . '"><i class="fa fa-trash"></i></a>',
		];

		$table->row($row);
	}

	$tableOutput = $table->render();
}

if (empty($tableOutput)) {
	$tableOutput = '<p><i>' . $this->_('There are no events yet.') . '</i><br/><u><a href="' . $this->wire('page')->url . 'event/new/">' . $this->_('Create the first event!') . '</a></u></p>';
}

echo $tableOutput;

// Build form:
$form = $this->wire('modules')->get('InputfieldForm');
$form->method = 'POST';
$form->action = $this->wire('page')->url;

$field = $this->modules->get('InputfieldButton');
$field->type = 'button';
$field->value = 'Refresh';
$field->name = 'action-refresh';
$field->href = $this->wire('page')->url;
$field->header = true;
$field->icon = 'refresh';
$form->add($field);

$field = $this->modules->get('InputfieldButton');
$field->type = 'button';
$field->value = 'Add';
$field->name = 'action-add';
$field->href = $this->wire('page')->url . 'event/new/';
$field->icon = 'plus-circle';
// $field->header    = true;
$field->secondary = false;
$form->add($field);

echo $form->render();
