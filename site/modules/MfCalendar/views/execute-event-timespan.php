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

if (!isset($event) || !$event instanceof CalendarEvent || $event->isNew()) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('No parent event found.') . '</p>';
	return;
}

if (!isset($timespan) || !$timespan instanceof CalendarTimespan) {
	$timespan = new CalendarTimespan([], $event);
}

$eventUrl = $this->wire('page')->url . 'event/edit/' . $event->getID();
$currentUrl = $event->isNew() ? $eventUrl . 'timespan/new' : $eventUrl . 'timespan/edit/' . $timespan->getID();

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
$field->value = $timespan->getTitle();
$field->collapsed = Inputfield::collapsedNever;
$form->add($field);

// Status
$field = $this->modules->get('InputfieldSelect');
$field->label = $this->_('Status');
$field->options = $statusOptions;
$field->attr('id+name', 'form_status');
$field->columnWidth = '50%';
$field->required = 1;
$field->value = $timespan->getStatus();
$field->collapsed = Inputfield::collapsedNever;
$form->add($field);

// Description:
$field = $this->modules->get('InputfieldTextarea');
$field->label = $this->_('Description');
$field->attr('id+name', 'form_description');
$field->columnWidth = '100%';
$field->required = 0;
$field->value = $timespan->getDescription();
$field->collapsed = Inputfield::collapsedNever;
$form->add($field);

// Submit-Buttons:
$submitButton = $this->modules->get('InputfieldButton');
$submitButton->type = 'submit';
$submitButton->value = 'save';
$submitButton->icon = 'floppy-o';
$submitButton->name = 'action-save';
$submitButton->header = false;
$form->add($submitButton);

if (!$timespan->isNew()) {
	$button = $this->modules->get('InputfieldButton');
	$button->href = $eventUrl . 'timespan/delete/' . $timespan->getID();
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
		// The submitted form has no errors. We can save the timespan.

		try {
			$doRedirect = $timespan->isNew();

			$timespan->setModifiedUser(wire('user'));
			$timespan->setTitle($form->get('form_title')->attr('value'));
			$timespan->setStatus($form->get('form_status')->attr('value'));
			$timespan->setDescription($form->get('form_description')->attr('value'));

			if (!$timespan->save()) {
				throw new \Exception('The timespan could not be saved.');
			}

			$this->notices->add(new NoticeMessage($timespan->isNew() ? $this->_('The timespan was successfully created.') : $this->_('The changes to your timespan were saved.')));

			if ($doRedirect) {
				$this->session->redirect($eventUrl . 'timespan/edit/' . $timespan->getID());
			}
		} catch (\Exception $e) {
			$this->session->error($e->getMessage());
		}
	}
}

if (!$timespan->isNew()) {
	$field = $this->modules->get('InputfieldMarkup');
	$field->label = $this->_('ID');
	$field->columnWidth = '20%';
	$field->value = $timespan->getID();
	$field->collapsed = Inputfield::collapsedNever;
	$form->prepend($field);
}

// Created- and Modified-Output is added after submission-handling, because only then the modified-date will have the correct time:
$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Modified');
$field->columnWidth = '40%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $timespan->getModified()), $timespan->getModifiedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Created');
$field->columnWidth = '40%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $timespan->getCreated()), $timespan->getCreatedUserLink());
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
  <a href='<?= $eventUrl; ?>'>
    <i
      class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
  </a>
</p>