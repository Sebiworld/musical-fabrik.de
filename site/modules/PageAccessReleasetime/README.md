# PageAccessReleasetime
Enables you to set a start- and end-time for the release of pages. Prevents unreleased pages from being displayed.

ProcessWire-Module: [http://modules.processwire.com/modules/page-access-releasetime/](http://modules.processwire.com/modules/page-access-releasetime/)

Support-Forum: [https://processwire.com/talk/topic/20852-module-page-access-releasetime/](https://processwire.com/talk/topic/20852-module-page-access-releasetime/)

Github-Repo: [https://github.com/Sebiworld/PageAccessReleasetime](https://github.com/Sebiworld/PageAccessReleasetime)

## Usage
PageAccessReleasetime can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

After that, you will find checkboxes for activating the releasetime-fields at the settings-tab of each page. You don't need to add the fields to your templates manually.

Check e.g. the checkbox "Activate Releasetime from?" and fill in a date in the future. The page will not be accessable for your users until the given date is reached.

If you have `$config->pagefileSecure = true`, the module will protect files of unreleased pages as well.

## How it works
This module hooks into `Page::viewable` and `Page::listable` to prevent users to access unreleased pages:

```php
public function hookPageViewable($event) {
	$page = $event->object;
	$viewable = $event->return;

	if($viewable){
		// If the page would be viewable, additionally check Releasetime and User-Permission
		$viewable = $this->canUserSee($page);
	}
	$event->return = $viewable;
}

public function hookPageListable($event) {
	$page = $event->object;
	$listable = $event->return;

	if($listable){
		// If the page would be listable, additionally check Releasetime and User-Permission
		$listable = $this->canUserSee($page);
	}
	$event->return = $listable;
}

```

To prevent access to the files of unreleased pages, we hook into `Page::isPublic` and `ProcessPageView::sendFile`.

The site/assets/files/ directory of pages, which `isPublic()` returns false, will get a '-' as prefix. This indicates ProcessWire (with activated `$config->pagefileSecure`) to check the file's permissions via PHP before delivering it to the client.

```php
public function hookPageIsPublic($e) {
	$page = $e->object;
	if($e->return && $this->isReleaseTimeSet($page)) {
		$e->return = false;
	}
}
```

The check wether a not-public file should be accessable happens in `ProcessPageView::sendFile`. We throw an 404 Exception if the current user must not see the file.

```php
public function hookProcessPageViewSendFile($e) {
	$page = $e->arguments[0];
	if(!$this->canUserSee($page)) {
		throw new Wire404Exception('File not found');
	}
}

```
Additionally we hook into `ProcessPageEdit::buildForm` to add the PageAccessReleasetime fields to each page and move them to the settings tab.

## Limitations
In the current version, releasetime-protected pages will appear in `wire('pages')->find()` queries. If you want to display a list of pages, where pages could be releasetime-protected, you should double-check with `$page->viewable()` or `$page->listable()` (for lists) wether the page can be accessed. `$page->viewable()` returns false, if the page is not released yet.

To filter unreleased pages, add the `PageAccessReleasetime::selector` to your selector:

```php
$onlyReleasedPages = wire('pages')->find('template.name=news, ' . PageAccessReleasetime::selector);
```

If you have an idea how unreleased pages can be filtered out of ProcessWire selector queries, feel free to write an issue, comment or make a pull request!

## Versioning
We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/Sebiworld/PageAccessReleasetime/tags).

## License
This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE.md](LICENSE.md) file for details.
