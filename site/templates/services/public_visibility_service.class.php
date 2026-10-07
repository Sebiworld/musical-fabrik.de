<?php
namespace ProcessWire;

/**
 * Decides which pages are public: what everything built for everyone (link
 * previews, sitemap) may show. Always checked as guest, also when a logged in
 * user (who may see pages in preparation) triggers it.
 */
class PublicVisibilityService extends TwackComponent {
	/**
	 * True if a guest may see the page in the frontend:
	 * - pages: viewable, not in the trash
	 * - performances (time_period): the rules of the route `performances/{id}`
	 * - and neither the page nor one of its parents is unpublished or locked
	 *   by a password
	 * Release times and permission locks of the page and its parents are part
	 * of viewable(). Hidden pages and parents are public, like in the
	 * frontend, and so are parents without an output of their own.
	 * @param Page $page
	 * @return bool
	 */
	public function isPublicPage(Page $page) {
		return $this->asGuest(function () use ($page) {
			if (!$page->id) {
				return false;
			}

			if ($page->template->name === 'time_period') {
				if (!$this->getService('PerformancesService')->isPublicPerformance($page)) {
					return false;
				}
			} elseif ($page->isTrash() || !$page->viewable()) {
				return false;
			}

			foreach ($page->parents()->add($page) as $item) {
				if ($item->isUnpublished() || $this->isPasswordLocked($item)) {
					return false;
				}
			}

			return true;
		});
	}

	/**
	 * Runs a function with the guest as current user and restores the current
	 * user afterwards, also after an exception.
	 * @param callable $callback
	 * @return mixed return value of the callback
	 */
	public function asGuest(callable $callback) {
		$users = $this->wire('users');
		$currentUser = $this->wire('user');
		$guest = $users->getGuestUser();
		$switchUser = $currentUser->id !== $guest->id;

		if ($switchUser) {
			$users->setCurrentUser($guest);
		}

		try {
			return $callback();
		} finally {
			if ($switchUser) {
				$users->setCurrentUser($currentUser);
			}
		}
	}

	/**
	 * Pages that SeoMaestro would put into the sitemap (viewable for guests)
	 * although they are not public, e.g. below an unpublished parent.
	 * @param string $selector selector of the sitemap candidates
	 * @return PageArray
	 */
	public function findViewableButNotPublic($selector) {
		return $this->asGuest(function () use ($selector) {
			$result = new PageArray();
			foreach ($this->wire('pages')->findMany($selector) as $page) {
				if ($page->viewable() && !$this->isPublicPage($page)) {
					$result->add($page);
				}
			}

			return $result;
		});
	}

	/**
	 * The password lock does not restrict viewable(), the frontend asks for
	 * the password.
	 * @param Page $page
	 * @return bool
	 */
	protected function isPasswordLocked(Page $page) {
		return $page->template->hasField('pageaccess_password')
			&& (!$page->template->hasField('pageaccess_password_activate') || (bool) $page->getUnformatted('pageaccess_password_activate'));
	}
}
