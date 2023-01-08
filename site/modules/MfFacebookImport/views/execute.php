<?php
namespace ProcessWire;

if (wire('user')->hasPermission(MfFacebookImport::managePermission)) {
	?>
<dl class="uk-description-list uk-description-list-divider" style="margin-bottom: 50px;">
	<h2><small><?= $this->_('What would you like to do?'); ?></small>
	</h2>
	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="./posts/">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-plug ui-priority-secondary"></i>
			<?= $this->_('See facebook posts'); ?>
		</a>
	</dt>
	<dd></dd>

	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label"
			href="<?= $configUrl; ?>">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-gear ui-priority-secondary"></i>
			<?= $this->_('Configure Module'); ?>
		</a>
	</dt>
	<dd></dd>
</dl>

<?php
if (wire('user')->hasPermission('logs-view') && isset($existingLogs[MfFacebookImport::logName]['modified'])) {
	?>
<dt>
	<a style="display: flex; align-items: center; text-decoration: none;" class="label"
		href="<?= wire('config')->urls->admin ?>setup/logs/view/<?= MfFacebookImport::logName; ?>/">
		<i style="margin-right: 10px; text-decoration: none;" class="fa fa-2x fa-fw fa-code ui-priority-secondary"></i>
		<?= $this->_('Import Log'); ?>
	</a>
</dt>
<dd style="margin-top: 12px;">
		<i><?= $this->_('Last entry: '); ?><?= wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $existingLogs[MfFacebookImport::logName]['modified']); ?></i>
	</dd>
<?php
}
}
?>

<dl class="uk-description-list uk-description-list-divider">
	<h2><small><?= $this->_('Links: '); ?></small>
	</h2>

	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label" target="_blank"
			href="https://developers.facebook.com/docs/graph-api/">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-book ui-priority-secondary"></i>
			<?= $this->_('Facebook Graph Api Documentation'); ?>
		</a>
	</dt>
	<dd style="margin-top: 12px;">
	</dd>
</dl>