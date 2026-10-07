<?php
namespace ProcessWire;

/**
 * Resolves the search engine and link preview data (`seo`) of a page or a
 * performance for the public API.
 *
 * Source is the SeoMaestro field `seo`. Pages without that field (e.g.
 * performances) are resolved from their own data. The fields `seo_title`
 * and `seo_description` of the configuration page are the site wide
 * defaults. Access has to be checked by the caller.
 */
class SeoService extends TwackComponent {
	/** Name of the SeoMaestro field. */
	const SEO_FIELD = 'seo';

	/** Width in pixels of preview images (Open Graph recommends 1200). */
	const IMAGE_WIDTH = 1200;

	/** Path segment of a performance below its project page in the frontend. */
	const PERFORMANCE_PATH_SEGMENT = 'vorstellungen';

	/** Time zone of the performance dates in titles. */
	const PERFORMANCE_TIMEZONE = 'Europe/Berlin';

	const WEEKDAYS = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

	const MONTHS = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

	protected $configurationService;

	/** Width and height of the preview images resolved in this request, by URL. */
	protected $previewImageSizes = [];

	public function __construct($args) {
		parent::__construct($args);
		$this->configurationService = $this->getService('ConfigurationService');
	}

	/**
	 * Returns the `seo` object of a page or of a performance (time_period).
	 * @param Page $page
	 * @return array{title: string, description: string, canonical: string, image: string|null, noindex: bool}
	 */
	public function getSeoAjax(Page $page) {
		if ($page->template->name === 'time_period') {
			return $this->getPerformanceSeoAjax($page);
		}

		$seo = $this->getSeoFieldValue($page);

		return [
			'title' => $this->getPageTitle($page, $seo),
			'description' => $this->getPageDescription($page, $seo),
			'canonical' => $this->getCanonicalUrl($this->getCanonicalPath($page, $seo)),
			'image' => $this->getPageImageUrl($page, $seo),
			'noindex' => $this->isNoindex($seo) || !$this->isPublic($page)
		];
	}

	/**
	 * Returns the `seo` object of the site itself, built only from the
	 * defaults of the configuration page, with the home page as canonical URL.
	 * @return array{title: string, description: string, canonical: string, image: string|null, noindex: bool}
	 */
	public function getDefaultSeoAjax() {
		return [
			'title' => $this->getDefaultTitle(),
			'description' => $this->getDefaultDescription(),
			'canonical' => $this->getCanonicalUrl('/'),
			'image' => $this->getDefaultImageUrl(),
			'noindex' => $this->isNoindex(null)
		];
	}

	/**
	 * Width and height of a preview image that was resolved in this request.
	 * @param string|null $url `image` of an `seo` object
	 * @return array{width: int, height: int}|null Null if unknown, e.g. for a maintained absolute URL
	 */
	public function getPreviewImageSize($url) {
		return is_string($url) && isset($this->previewImageSizes[$url]) ? $this->previewImageSizes[$url] : null;
	}

	/**
	 * Performances have no SeoMaestro field: title, description, image and
	 * noindex come from the performance and its project, the canonical URL
	 * is the performance route of the frontend below the project page.
	 * @param Page $period time_period page
	 * @return array
	 */
	protected function getPerformanceSeoAjax(Page $period) {
		$projectPage = $this->getService('ProjectService')->getProjectPage($period);
		$hasProject = $projectPage instanceof Page && $projectPage->id;
		$projectSeo = $hasProject ? $this->getSeoFieldValue($projectPage) : null;

		$name = $this->toPlainText($period->title);
		$date = $this->formatPerformanceDate($period);
		if ($name !== '' && $date !== '') {
			$name .= ' am ' . $date;
		}
		if ($hasProject) {
			$projectTitle = $this->toPlainText($projectPage->title);
			$name = $name === '' ? $projectTitle : $name . ' – ' . $projectTitle;
		}

		$title = $name === '' ? $this->getDefaultTitle() : $this->formatTitle($name);

		$description = $period->template->hasField('description_text') ? $this->toPlainText($period->getUnformatted('description_text')) : '';
		if ($description === '' && $hasProject) {
			$description = $this->getPageDescription($projectPage, $projectSeo);
		}
		if ($description === '') {
			$description = $this->getDefaultDescription();
		}

		return [
			'title' => $title,
			'description' => $description,
			'canonical' => $this->getPerformanceUrl($period),
			'image' => $hasProject ? $this->getPageImageUrl($projectPage, $projectSeo) : $this->getDefaultImageUrl(),
			'noindex' => $this->isNoindex($projectSeo) || !$this->isPublic($period)
		];
	}

	/**
	 * Absolute frontend URL of a performance: the performance route below
	 * its project page, e.g. https://www.musical-fabrik.de/projekte/annie/vorstellungen/11695/
	 * @param Page $period time_period page
	 * @return string
	 */
	public function getPerformanceUrl(Page $period) {
		$projectPage = $this->getService('ProjectService')->getProjectPage($period);
		$path = $projectPage instanceof Page && $projectPage->id
			? $this->getPagePath($projectPage) . self::PERFORMANCE_PATH_SEGMENT . '/' . $period->id . '/'
			: $this->getPagePath($period);

		return $this->getCanonicalUrl($path);
	}

	/**
	 * Sitemap entries of all public performances (PublicVisibilityService,
	 * the same rule as the link preview), past ones included. Visibility is
	 * checked as guest, also when the sitemap is generated by a logged in user. Servers with
	 * $config->noindex leave every page out of the sitemap, so they get no
	 * performances either. Performances of a project that SeoMaestro leaves
	 * out of the sitemap (noindex or not included) are left out, too.
	 * @return \SeoMaestro\SitemapItem[]
	 */
	public function getPerformanceSitemapItems() {
		if ($this->wire('config')->noindex === true) {
			return [];
		}

		$visibility = $this->getService('PublicVisibilityService');

		return $visibility->asGuest(function () use ($visibility) {
			$items = [];
			$projectIncluded = [];
			foreach ($this->getService('PerformancesService')->getPublicPerformancePages() as $period) {
				if (!$visibility->isPublicPage($period)) {
					continue;
				}

				$projectPage = $this->getService('ProjectService')->getProjectPage($period);
				$projectId = $projectPage instanceof Page ? $projectPage->id : 0;
				if (!isset($projectIncluded[$projectId])) {
					$projectIncluded[$projectId] = $projectId === 0 || $this->isInSitemap($projectPage);
				}
				if (!$projectIncluded[$projectId]) {
					continue;
				}

				$items[] = (new \SeoMaestro\SitemapItem())
					->set('loc', $this->getPerformanceUrl($period))
					->set('lastmod', date('c', $period->modified));
			}

			return $items;
		});
	}

	/**
	 * Pages that SeoMaestro would list in the sitemap (viewable for guests)
	 * although they are not public (PublicVisibilityService, the same rule as
	 * the link preview), e.g. pages below an unpublished parent.
	 * @return PageArray
	 */
	public function getNotPublicSitemapPages() {
		$templates = [];
		foreach ($this->wire('templates') as $template) {
			if ($template->name !== 'admin' && $template->fields->find('type=FieldtypeSeoMaestro')->count()) {
				$templates[] = $template->name;
			}
		}

		if (empty($templates)) {
			return new PageArray();
		}

		// The candidates of SeoMaestro's own sitemap.
		return $this->getService('PublicVisibilityService')->findViewableButNotPublic('template=' . implode('|', $templates) . ', include=hidden');
	}

	/**
	 * True if the SeoMaestro settings of the page put it into the sitemap:
	 * `sitemap_include` on and `robots_noIndex` off, where `inherit` takes the
	 * value of the field like SeoMaestro does. Servers with $config->noindex
	 * are handled by the caller.
	 * @param Page $page
	 * @return bool
	 */
	protected function isInSitemap(Page $page) {
		$seo = $this->getSeoFieldValue($page);
		if (!$seo) {
			return true;
		}

		return (bool) $this->getSeoSetting($seo, 'sitemap_include') && !$this->getSeoSetting($seo, 'robots_noIndex');
	}

	/**
	 * Stored value of a SeoMaestro setting such as `sitemap_include`, or the
	 * value of the field for `inherit`.
	 * @param \SeoMaestro\PageFieldValue $seo
	 * @param string $key
	 * @return int
	 */
	protected function getSeoSetting($seo, $key) {
		$value = $seo->get($key);
		if ($value === null || $value === '' || $value === 'inherit') {
			$field = $this->wire('fields')->get(self::SEO_FIELD);
			$value = $field instanceof Field ? $field->get($key) : null;
		}

		return (int) $value;
	}

	/**
	 * Beginning of a performance in German, e.g. "Sa., 14. November 2026".
	 * @param Page $period time_period page
	 * @return string Empty without beginning
	 */
	protected function formatPerformanceDate(Page $period) {
		$timestamp = (int) $period->getUnformatted('datetime_from');
		if ($timestamp <= 0) {
			return '';
		}

		$date = (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone(self::PERFORMANCE_TIMEZONE));

		return self::WEEKDAYS[(int) $date->format('N') - 1] . '., ' . $date->format('j') . '. '
			. self::MONTHS[(int) $date->format('n') - 1] . ' ' . $date->format('Y');
	}

	/**
	 * @param Page $page
	 * @return \SeoMaestro\PageFieldValue|null
	 */
	protected function getSeoFieldValue(Page $page) {
		if (!$page->template->hasField(self::SEO_FIELD)) {
			return null;
		}

		$value = $page->get(self::SEO_FIELD);

		return $value instanceof \SeoMaestro\PageFieldValue ? $value : null;
	}

	protected function getPageTitle(Page $page, $seo) {
		// The home page carries only the site name, unless it has its own title.
		if ($page->id === $this->wire('config')->rootPageID && !$this->hasMaintainedValue($seo, 'meta_title')) {
			$title = $this->getDefaultTitle();
			if ($title !== '') {
				return $title;
			}
		}

		if ($seo) {
			$title = $this->toPlainText($seo->meta->title);
			if ($title !== '') {
				return $title;
			}
		}

		$title = $this->toPlainText($page->title);

		return $title === '' ? $this->getDefaultTitle() : $this->formatTitle($title);
	}

	/**
	 * True if the page itself has a value for the key (e.g. `meta_title`),
	 * not only the default of the field.
	 * @param \SeoMaestro\PageFieldValue|null $seo
	 * @param string $key
	 * @return bool
	 */
	protected function hasMaintainedValue($seo, $key) {
		if (!$seo) {
			return false;
		}

		$value = $seo->get($key);

		return is_string($value) && trim($value) !== '' && $value !== 'inherit';
	}

	protected function getPageDescription(Page $page, $seo) {
		if ($seo) {
			$description = $this->toPlainText($seo->meta->description);
			if ($description !== '') {
				return $description;
			}
		}

		return $this->getDefaultDescription();
	}

	/**
	 * Appends the site name like SeoMaestro does, with the title format of
	 * the field `seo`.
	 * @param string $title
	 * @return string
	 */
	protected function formatTitle($title) {
		$field = $this->wire('fields')->get(self::SEO_FIELD);
		$format = $field instanceof Field ? (string) $field->get('meta_title_format') : '';
		if ($format === '' || strpos($format, '{meta_title}') === false) {
			return $title;
		}

		return $this->toPlainText(str_replace('{meta_title}', $title, $format));
	}

	protected function getDefaultTitle() {
		$configPage = $this->configurationService->getConfigurationPage();
		if ($configPage instanceof Page && $configPage->template->hasField('seo_title')) {
			return $this->toPlainText($configPage->seo_title);
		}

		return '';
	}

	protected function getDefaultDescription() {
		$configPage = $this->configurationService->getConfigurationPage();
		if ($configPage instanceof Page && $configPage->template->hasField('seo_description')) {
			return $this->toPlainText($configPage->seo_description);
		}

		return '';
	}

	/**
	 * Path of the page in the frontend: the canonical URL maintained in
	 * SeoMaestro, or the page URL without the installation directory of the
	 * backend.
	 * @param Page $page
	 * @param \SeoMaestro\PageFieldValue|null $seo
	 * @return string
	 */
	protected function getCanonicalPath(Page $page, $seo) {
		if ($seo) {
			// Only a value maintained on the page itself, not a field default.
			$maintained = $seo->get('meta_canonicalUrl');
			if (is_string($maintained) && $maintained !== '' && $maintained !== 'inherit') {
				$path = preg_match('#^https?://#i', $maintained) ? (string) parse_url($maintained, PHP_URL_PATH) : $maintained;
				if ($path !== '') {
					return $path;
				}
			}
		}

		return $this->getPagePath($page);
	}

	/**
	 * @param Page $page
	 * @return string Path relative to the frontend root, with leading and trailing slash
	 */
	protected function getPagePath(Page $page) {
		return $this->withoutBackendRoot($page->url);
	}

	protected function withoutBackendRoot($path) {
		$root = $this->wire('config')->urls->root;
		if ($root !== '/' && strpos($path, $root) === 0) {
			$path = '/' . substr($path, strlen($root));
		}

		return $path;
	}

	/**
	 * Absolute frontend URL of a path, without query and fragment and with a
	 * trailing slash. The host is the base URL of the SeoMaestro module
	 * configuration, else the host of this installation.
	 * @param string $path
	 * @return string
	 */
	protected function getCanonicalUrl($path) {
		$path = preg_replace('/[?#].*$/s', '', (string) $path);
		$path = $this->withoutBackendRoot('/' . ltrim($path, '/'));
		$path = rtrim($path, '/') . '/';

		return $this->getFrontendBaseUrl() . $path;
	}

	protected function getFrontendBaseUrl() {
		$baseUrl = rtrim((string) $this->wire('modules')->getConfig('SeoMaestro', 'baseUrl'), '/');
		if ($baseUrl !== '') {
			return $baseUrl;
		}

		$config = $this->wire('config');

		return ($config->https ? 'https' : 'http') . '://' . $config->httpHost;
	}

	/**
	 * Preview image of a page: the image SeoMaestro is configured to use
	 * (a `{field}` placeholder or an absolute URL), else the main image of the
	 * configuration page.
	 * @param Page $page
	 * @param \SeoMaestro\PageFieldValue|null $seo
	 * @return string|null
	 */
	protected function getPageImageUrl(Page $page, $seo) {
		$fieldName = 'main_image';

		if ($seo) {
			$value = (string) $seo->opengraph->getUnformatted('image');
			if (preg_match('/^\{([a-z0-9_]+)\}$/i', $value, $matches)) {
				$fieldName = $matches[1];
			} elseif (preg_match('#^https?://#i', $value)) {
				return $value;
			} else {
				// A relative path (like the default of the field) is no usable preview image.
				$fieldName = '';
			}
		}

		$image = $fieldName === '' ? null : $this->getFirstImage($page, $fieldName);
		if ($image instanceof Pageimage) {
			return $this->getPreviewImageUrl($image);
		}

		return $this->getDefaultImageUrl();
	}

	protected function getDefaultImageUrl() {
		$configPage = $this->configurationService->getConfigurationPage();
		$image = $configPage instanceof Page ? $this->getFirstImage($configPage, 'main_image') : null;

		return $image instanceof Pageimage ? $this->getPreviewImageUrl($image) : null;
	}

	/**
	 * @param Page $page
	 * @param string $fieldName
	 * @return Pageimage|null
	 */
	protected function getFirstImage(Page $page, $fieldName) {
		if (!$page->template->hasField($fieldName)) {
			return null;
		}

		$value = $page->getUnformatted($fieldName);
		if ($value instanceof Pageimages) {
			$value = $value->first();
		}

		// SVG is no supported preview image format.
		if (!($value instanceof Pageimage) || strtolower($value->ext) === 'svg') {
			return null;
		}

		return $value;
	}

	/**
	 * Absolute URL of the image, scaled down to IMAGE_WIDTH if it is wider.
	 * @param Pageimage $image
	 * @return string
	 */
	protected function getPreviewImageUrl(Pageimage $image) {
		if ($image->width > self::IMAGE_WIDTH) {
			$image = $image->width(self::IMAGE_WIDTH);
		}

		$url = $image->httpUrl();
		$this->previewImageSizes[$url] = ['width' => (int) $image->width, 'height' => (int) $image->height];

		return $url;
	}

	/**
	 * True if the configuration sets `noindex` (e.g. on test servers) or the
	 * page itself is marked noindex in SeoMaestro. The default of the field
	 * is not used.
	 * @param \SeoMaestro\PageFieldValue|null $seo
	 * @return bool
	 */
	protected function isNoindex($seo) {
		if ($this->wire('config')->noindex === true) {
			return true;
		}

		if (!$seo) {
			return false;
		}

		$value = $seo->get('robots_noIndex');

		return $value !== 'inherit' && $value !== null && (int) $value === 1;
	}

	/**
	 * Pages that are not public (e.g. locked by a password or below an
	 * unpublished parent) are noindex, by the same rule as the sitemap and
	 * the link preview.
	 * @param Page $page
	 * @return bool
	 */
	protected function isPublic(Page $page) {
		return $this->getService('PublicVisibilityService')->isPublicPage($page);
	}

	/**
	 * Plain text without tags and entities, with single spaces.
	 * @param mixed $value
	 * @return string
	 */
	protected function toPlainText($value) {
		// Block elements separate words, inline elements do not.
		$text = preg_replace('#</?(br|p|div|li|ul|ol|h[1-6]|blockquote|tr|td|th)\b[^>]*>#i', ' ', (string) $value);
		$text = strip_tags($text);

		// SeoMaestro encodes values, and a hook may encode them once more.
		for ($i = 0; $i < 3; $i++) {
			$decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($decoded === $text) {
				break;
			}
			$text = $decoded;
		}

		$text = strip_tags($text);
		$text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);

		return trim($text);
	}
}
