<?php
namespace ProcessWire;

if (!wire('user')->hasPermission(MfCalendar::managePermission)) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('You don\'t have the needed permissions to access this function. Please contact a Superuser.') . '</p>';
	return;
}

if (isset($locked) && $locked === true) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	if (!empty($message)) {
		echo $message;
	}
	return;
}

if (isset($subarea) && $subarea === 'timespan') {
	include_once 'execute-event-timespan.php';
	return;
}

if (!isset($event) || !$event instanceof CalendarEvent) {
	$event = new CalendarEvent();
}

$currentUrl = $event->isNew() ? $this->wire('page')->url . 'event/new' : $this->wire('page')->url . 'event/edit/' . $event->getID();

if (!$event->isNew()) {
	$timespansTable = $modules->get('MarkupAdminDataTable');
	$timespansTable->setEncodeEntities(false);

	$timespansTable->headerRow([
		$this->_('Title'),
		$this->_('Time from'),
		$this->_('Time until'),
		$this->_('Description'),
		$this->_('Status'),
		$this->_('Location'),
		$this->_('Actions')
	]);

	$timespans = $event->getTimespans();

	if ($timespans instanceof WireArray && $timespans->count > 0) {
		foreach ($timespans as $timespan) {
			$row = [
				'<a href="' . $currentUrl . 'timespan/edit/' . $timespan->getID() . '">' . $timespan->getTitle() . '</a>',
				wire('datetime')->date('', $timespan->getTimeFrom()),
				wire('datetime')->date('', $timespan->getTimeUntil()),
				$timespan->getDescription(),
				$timespan->getStatus(),
				$timespan->getLocation(),
				'<a href="' . $currentUrl . 'timespan/delete/' . $timespan->getID() . '"><i class="fa fa-trash"></i></a>'
			];

			$timespansTable->row($row);
		}

		$timespansTableOutput = $timespansTable->render();
	}

	if (empty($timespansTableOutput)) {
		$timespansTableOutput = '<p><i>' . $this->_('There are no timespans set for this event.') . '</i></p>';
	}

	$button = $this->modules->get('InputfieldButton');
	$button->value = $this->_('Add new Timespan');
	$button->icon = 'plus';
	$button->setSmall(true);
	$button->attr('href', $currentUrl . '/timespan/new/');
	$timespansTableOutput .= $button->render();
} else {
	$timespansTableOutput = '<p><i>' . $this->_('You can add timespans after saving the event the first time.') . '</i></p>';
}

// Build form:
$form = $this->modules->get('InputfieldForm');
$form->method = 'POST';
$form->action = $currentUrl;

// Title
$field = $this->modules->get('InputfieldText');
$field->label = $this->_('Title');
$field->attr('id+name', 'form_title');
$field->columnWidth = '50%';
$field->required = 1;
$field->value = $event->getTitle();
$field->collapsed = Inputfield::collapsedNever;
$form->add($field);

// Status
$field = $this->modules->get('InputfieldSelect');
$field->label = $this->_('Status');
$field->options = $statusOptions;
$field->attr('id+name', 'form_status');
$field->columnWidth = '50%';
$field->required = 1;
$field->value = $event->getStatus();
$field->collapsed = Inputfield::collapsedNever;
$form->add($field);

// Description:
$field = $this->modules->get('InputfieldTextarea');
$field->label = $this->_('Description');
$field->attr('id+name', 'form_description');
$field->columnWidth = '100%';
$field->required = 0;
$field->value = $event->getDescription();
$field->collapsed = Inputfield::collapsedNever;
$form->add($field);

// Timespans:
$field = $this->modules->get('InputfieldMarkup');
$field->attr('id+name', 'form_timespans');
$field->label = $this->_('Timespans');
$field->value = $timespansTableOutput;
$field->columnWidth = 100;
$form->add($field);

// Submit-Buttons:
$submitButton = $this->modules->get('InputfieldButton');
$submitButton->type = 'submit';
$submitButton->value = 'save';
$submitButton->icon = 'floppy-o';
$submitButton->name = 'action-save';
$submitButton->header = false;
$form->add($submitButton);

if (!$event->isNew()) {
	$button = $this->modules->get('InputfieldButton');
	$button->href = $this->page->url . 'event/delete/' . $event->getID();
	$button->value = 'delete';
	$button->icon = 'trash-o';
	$button->name = 'action-delete';
	$button->secondary = true;
	$form->add($button);
}

if (wire('input')->post('action-save')) {
	// form submitted
	$form->processInput(wire('input')->post);
	$errors = $form->getErrors();
	$messages = [];

	if (count($errors)) {
		// The submitted form-data has errors
		foreach ($errors as $error) {
			$this->session->error($error);
		}
	} else {
		// The submitted form has no errors. We can save the event.

		try {
			$doRedirect = $event->isNew();

			$event->setModifiedUser(wire('user'));
			$event->setTitle($form->get('form_title')->attr('value'));
			$event->setStatus($form->get('form_status')->attr('value'));
			$event->setDescription($form->get('form_description')->attr('value'));

			if (!$event->save()) {
				throw new \Exception('The event could not be saved.');
			}

			$this->notices->add(new NoticeMessage($event->isNew() ? $this->_('The event was successfully created.') : $this->_('The changes to your event were saved.')));

			if ($doRedirect) {
				$this->session->redirect($this->wire('page')->url . 'event/edit/' . $event->getID());
			}
		} catch (\Exception $e) {
			$this->session->error($e->getMessage());
		}
	}
}

if (!$event->isNew()) {
	$field = $this->modules->get('InputfieldMarkup');
	$field->label = $this->_('ID');
	$field->columnWidth = '20%';
	$field->value = $event->getID();
	$field->collapsed = Inputfield::collapsedNever;
	$form->prepend($field);
}

// Created- and Modified-Output is added after submission-handling, because only then the modified-date will have the correct time:
$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Modified');
$field->columnWidth = '40%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $event->getModified()), $event->getModifiedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Created');
$field->columnWidth = '40%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $event->getCreated()), $event->getCreatedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

// Output errors:
if (isset($messages['errors']) && is_array($messages['errors'])) {
	?>
<div class="NoticeError" style="padding: 5px 10px;">
	<strong><?= $this->_('The form has errors: '); ?></strong><br />
	<?php
		$firstFlag = true;
	foreach ($messages['errors'] as $error) {
		if ($firstFlag) {
			echo $error;
			$firstFlag = false;
			continue;
		}
		echo '<br/>' . $error;
	} ?>
</div>
<?php
}
?>

<?= $form->render(); ?>

<p style='padding-top: 20px;'>
	<a href='<?= $this->wire('page')->url; ?>'>
		<i
			class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
	</a>
</p>