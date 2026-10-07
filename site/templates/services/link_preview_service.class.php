<?php
namespace ProcessWire;

/**
 * Link previews for services that run no JavaScript (messengers, social
 * networks): a small HTML document with the title, description, image and
 * URL of a frontend page.
 *
 * The page is resolved from its frontend path and must be public
 * (PublicVisibilityService, the same rule as the sitemap). Paths that are not
 * public are answered like unknown paths, with the defaults of the site only.
 */
class LinkPreviewService extends TwackComponent {
	/** Longest accepted frontend path. */
	const MAX_PATH_LENGTH = 1024;

	/**
	 * Most path segments of an accepted frontend path. The deepest page has
	 * far fewer; the lookup joins one table per segment and MySQL allows 61.
	 */
	const MAX_PATH_SEGMENTS = 20;

	/** Performance route of the frontend: `<project path>vorstellungen/<id>/`. */
	const PERFORMANCE_PATH = '#^/(?:[^/]+/)+vorstellungen/(\d+)/$#';

	/** Cache lifetime of a preview in seconds. */
	const MAX_AGE = 600;

	const LOCALE = 'de_DE';

	protected $seoService;

	protected $visibility;

	public function __construct($args) {
		parent::__construct($args);
		$this->seoService = $this->getService('SeoService');
		$this->visibility = $this->getService('PublicVisibilityService');
	}

	/**
	 * Sends the status and headers of a link preview and returns its HTML.
	 * @param mixed $path frontend path, e.g. `/projekte/annie/`
	 * @return string
	 */
	public function renderResponse($path) {
		$preview = $this->getPreview($path);
		$found = $preview !== null;
		if (!$found) {
			$preview = $this->getDefaultPreview();
		}

		http_response_code($found ? 200 : 404);
		header('Content-Type: text/html; charset=utf-8');
		// The same frontend URL serves the app to browsers and this preview to
		// link preview services, so shared caches must not keep it.
		header('Cache-Control: private, max-age=' . self::MAX_AGE);
		header('Vary: User-Agent');
		header('X-Robots-Tag: noindex');
		// The session start sends headers that forbid caching, and a session
		// cookie that a preview does not need.
		header_remove('Pragma');
		header_remove('Expires');
		header_remove('Set-Cookie');

		return $this->renderHtml($preview);
	}

	/**
	 * Returns the preview data of a frontend path as seen by a guest, or null
	 * if the path is unknown or not visible for guests.
	 * @param mixed $path
	 * @return array{title: string, description: string, canonical: string, image: string|null, image_width: int|null, image_height: int|null}|null
	 */
	public function getPreview($path) {
		return $this->visibility->asGuest(function () use ($path) {
			$page = $this->findPage($path);

			return $page instanceof Page ? $this->toPreview($this->seoService->getSeoAjax($page)) : null;
		});
	}

	/**
	 * Preview data of the site itself, used for unknown paths.
	 * @return array
	 */
	public function getDefaultPreview() {
		return $this->toPreview($this->seoService->getDefaultSeoAjax());
	}

	/**
	 * Finds the page or performance of a frontend path that the current user
	 * may see.
	 * @param mixed $path
	 * @return Page|null
	 */
	protected function findPage($path) {
		$path = $this->normalizePath($path);
		if ($path === null) {
			return null;
		}

		if (preg_match(self::PERFORMANCE_PATH, $path, $matches)) {
			$page = $this->getService('PerformancesService')->getPublicPerformancePage((int) $matches[1]);

			return $page instanceof Page && $this->visibility->isPublicPage($page) ? $page : null;
		}

		try {
			$page = $this->wire('pages')->getByPath($path, [
				'allowUrl' => false,
				'allowPartial' => false,
				'allowUrlSegments' => false
			]);
		} catch (\PDOException $e) {
			// A path the database cannot look up belongs to no page.
			return null;
		}

		// getByPath() sanitizes the path, so a different path could lead to an existing page.
		if (!($page instanceof Page) || !$page->id || $page->path !== $path) {
			return null;
		}

		return $this->visibility->isPublicPage($page) ? $page : null;
	}

	/**
	 * Path without query and fragment, decoded, in lower case, with leading
	 * and trailing slash. Null for paths that cannot belong to a page.
	 * @param mixed $path
	 * @return string|null
	 */
	protected function normalizePath($path) {
		if (!is_string($path) || $path === '' || strlen($path) > self::MAX_PATH_LENGTH) {
			return null;
		}

		$path = preg_replace('/[?#].*$/s', '', $path);
		$path = rawurldecode($path);
		if (!mb_check_encoding($path, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/', $path)) {
			return null;
		}

		$segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));
		if (count($segments) > self::MAX_PATH_SEGMENTS) {
			return null;
		}
		foreach ($segments as $segment) {
			if ($segment === '.' || $segment === '..') {
				return null;
			}
		}

		return mb_strtolower(empty($segments) ? '/' : '/' . implode('/', $segments) . '/', 'UTF-8');
	}

	/**
	 * @param array $seo `seo` object of SeoService
	 * @return array
	 */
	protected function toPreview(array $seo) {
		$size = $this->seoService->getPreviewImageSize($seo['image']);

		return [
			'title' => (string) $seo['title'],
			'description' => (string) $seo['description'],
			'canonical' => (string) $seo['canonical'],
			'image' => $seo['image'],
			'image_width' => $size ? $size['width'] : null,
			'image_height' => $size ? $size['height'] : null
		];
	}

	/**
	 * @param array $preview
	 * @return string
	 */
	public function renderHtml(array $preview) {
		$siteName = (string) $this->seoService->getDefaultSeoAjax()['title'];
		$title = $this->escape($preview['title']);
		$description = $this->escape($preview['description']);
		$url = $this->escape($preview['canonical']);

		$meta = [
			'<meta charset="utf-8">',
			'<title>' . $title . '</title>',
			'<meta name="description" content="' . $description . '">',
			'<link rel="canonical" href="' . $url . '">',
			'<meta property="og:type" content="website">',
			'<meta property="og:site_name" content="' . $this->escape($siteName) . '">',
			'<meta property="og:locale" content="' . self::LOCALE . '">',
			'<meta property="og:title" content="' . $title . '">',
			'<meta property="og:description" content="' . $description . '">',
			'<meta property="og:url" content="' . $url . '">'
		];

		if (is_string($preview['image']) && $preview['image'] !== '') {
			$meta[] = '<meta property="og:image" content="' . $this->escape($preview['image']) . '">';
			if ($preview['image_width'] && $preview['image_height']) {
				$meta[] = '<meta property="og:image:width" content="' . (int) $preview['image_width'] . '">';
				$meta[] = '<meta property="og:image:height" content="' . (int) $preview['image_height'] . '">';
			}
		}

		$meta[] = '<meta name="twitter:card" content="summary_large_image">';
		$meta[] = '<meta name="twitter:title" content="' . $title . '">';
		$meta[] = '<meta name="twitter:description" content="' . $description . '">';
		if (is_string($preview['image']) && $preview['image'] !== '') {
			$meta[] = '<meta name="twitter:image" content="' . $this->escape($preview['image']) . '">';
		}

		return '<!doctype html>' . "\n"
			. '<html lang="de">' . "\n"
			. '<head>' . "\n" . implode("\n", $meta) . "\n" . '</head>' . "\n"
			. '<body>' . "\n"
			. '<h1>' . $title . '</h1>' . "\n"
			. ($description !== '' ? '<p>' . $description . '</p>' . "\n" : '')
			. '<p><a href="' . $url . '">' . $url . '</a></p>' . "\n"
			. '</body>' . "\n"
			. '</html>' . "\n";
	}

	protected function escape($value) {
		return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
	}
}
