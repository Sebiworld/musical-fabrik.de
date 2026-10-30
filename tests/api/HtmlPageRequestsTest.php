<?php

declare(strict_types=1);
namespace Tests\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use ProcessWire\Page;
use Tests\Support\ApiTestCase;
use Tests\Support\Fixtures;

use function ProcessWire\wire;

/**
 * Page URLs outside the API endpoint: the backend renders no pages there and
 * answers every page request with the same 404 as an unknown URL, with or
 * without trailing slash, for guests and for a logged-in superuser alike. The
 * admin, the link preview, the sitemap, secured page files and the API stay
 * reachable. "View" in the admin leads to the frontend. Creates its own pages
 * and a superuser account and removes them afterwards.
 */
final class HtmlPageRequestsTest extends ApiTestCase {
	private const UNKNOWN_PATH = '/test-fixture-html-does-not-exist/';

	/** @var array<string, Page>|null */
	private static ?array $pages = null;

	/** @var array<string, Page>|null Pages of Fixtures::createFileTree(). */
	private static ?array $files = null;

	/** Cookie file of a logged-in admin session. */
	private static ?string $adminCookies = null;

	protected function setUp(): void {
		parent::setUp();

		if (self::$pages === null) {
			self::$pages = Fixtures::createHtmlRequestTree(bin2hex(random_bytes(8)));
		}
	}

	public static function tearDownAfterClass(): void {
		try {
			if (self::$pages !== null) {
				self::$pages = null;
				Fixtures::deleteHtmlRequestTree();
			}
		} finally {
			try {
				if (self::$files !== null) {
					self::$files = null;
					Fixtures::deleteFileTree();
				}
			} finally {
				try {
					// Always: the account may exist although the login was not completed.
					Fixtures::deleteHtmlAdmin();
				} finally {
					if (self::$adminCookies !== null) {
						@unlink(self::$adminCookies);
						self::$adminCookies = null;
					}
					parent::tearDownAfterClass();
				}
			}
		}
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function pagePaths(): array {
		return [
			'home page' => ['/'],
			'project' => [Fixtures::PROJECT_PATH],
			'password locked page' => ['/' . Fixtures::HTML_PREFIX . 'locked/'],
			'page with release time in the future' => ['/' . Fixtures::HTML_PREFIX . 'not-released/'],
			'unpublished page' => [Fixtures::UNPUBLISHED_PATH],
		];
	}

	#[DataProvider('pagePaths')]
	public function testPageIsNotFoundForGuests(string $path): void {
		$this->assertNotFoundLikeAnUnknownPath($path, null);
	}

	#[DataProvider('pagePaths')]
	public function testPageIsNotFoundWithAnAdminSession(string $path): void {
		$this->assertNotFoundLikeAnUnknownPath($path, $this->adminCookies());
	}

	public function testAjaxHeaderDoesNotMakeAPageRequestAnApiRequest(): void {
		$this->assertNotFoundLikeAnUnknownPath(Fixtures::CONTENT_PATH, null, ['X-Requested-With: XMLHttpRequest']);
	}

	public function testNotFoundPageItselfAnswersWithTheSame404(): void {
		$path = wire('pages')->get((int) wire('config')->http404PageID)->path;
		$this->assertNotFoundLikeAnUnknownPath($path, null);
		$this->assertNotFoundLikeAnUnknownPath($path, $this->adminCookies());
	}

	/**
	 * Without trailing slash ProcessWire would redirect an existing page (also
	 * one a guest may not see) to the URL with slash, but answer an unknown
	 * URL with 404, which tells whether a page exists.
	 *
	 * @return array<string, array{string}>
	 */
	public static function pathsWithoutSlash(): array {
		return [
			'visible page' => [rtrim(Fixtures::CONTENT_PATH, '/')],
			'project' => [rtrim(Fixtures::PROJECT_PATH, '/')],
			'password locked page' => ['/' . Fixtures::HTML_PREFIX . 'locked'],
			'page with release time in the future' => ['/' . Fixtures::HTML_PREFIX . 'not-released'],
			'unpublished page' => [rtrim(Fixtures::UNPUBLISHED_PATH, '/')],
		];
	}

	#[DataProvider('pathsWithoutSlash')]
	public function testPathWithoutSlashIsIndistinguishableFromAnUnknownPathForGuests(string $path): void {
		$this->assertIndistinguishableFromUnknownPath($path, null);
	}

	#[DataProvider('pathsWithoutSlash')]
	public function testPathWithoutSlashIsIndistinguishableFromAnUnknownPathWithAnAdminSession(string $path): void {
		$this->assertIndistinguishableFromUnknownPath($path, $this->adminCookies());
	}

	/**
	 * The vote action of the comments field answers ajax requests on a page
	 * URL before the page is rendered. It must not run outside the API.
	 */
	public function testCommentVoteOnAPageUrlIsNotFoundAndStoresNoVote(): void {
		$article = self::$pages['article'];
		$commentIds = Fixtures::htmlArticleCommentIds();
		self::assertCount(1, $commentIds);
		$before = Fixtures::commentVoteCount($commentIds);

		$query = '?' . http_build_query([
			'comment_success' => 'upvote',
			'comment_id' => $commentIds[0],
			'field_id' => wire('fields')->get('comments')->id,
		]);
		$headers = ['X-Requested-With: XMLHttpRequest'];
		$response = $this->siteRequest($article->path . $query, null, $headers);
		$unknown = $this->siteRequest(self::UNKNOWN_PATH . $query, null, $headers);

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame($unknown['raw'], $response['raw']);
		self::assertSame($before, Fixtures::commentVoteCount($commentIds), 'A vote was stored.');
	}

	public function testSecuredPageFileKeepsItsAccessRules(): void {
		self::$files ??= Fixtures::createFileTree();
		$path = wire('config')->urls->files . self::$files['unpublished']->id . '/' . Fixtures::SMALL_IMAGE;

		$guest = $this->siteRequest($path);
		$admin = $this->siteRequest($path, $this->adminCookies());

		self::assertSame(404, $guest['status']);
		self::assertSame(200, $admin['status']);
		self::assertSame('image/jpeg', $admin['headers']['content-type'] ?? null);
	}

	public function testAdminShowsTheLoginFormToGuests(): void {
		$response = $this->siteRequest('/redaktion/');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertStringContainsString('name="login_pass"', $response['raw']);
	}

	public function testAdminIsReachableWithAnAdminSession(): void {
		$response = $this->siteRequest('/redaktion/page/', $this->adminCookies());

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertStringNotContainsString('name="login_pass"', $response['raw']);
		self::assertStringContainsString('ProcessPageList', $response['raw']);
	}

	public function testPageListViewActionLeadsToTheFrontend(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);
		$home = wire('pages')->get('/');
		$base = $this->frontendBaseUrl();

		self::assertSame($base . Fixtures::PROJECT_PATH, $this->pageListViewUrl($project->parent->id, $project->id));
		self::assertSame($base . '/', $this->pageListViewUrl(0, $home->id));
	}

	public function testPageEditViewLinksLeadToTheFrontend(): void {
		$project = Fixtures::page(Fixtures::PROJECT_PATH);
		$base = $this->frontendBaseUrl();
		$edit = $this->siteRequest('/redaktion/page/edit/?id=' . $project->id, $this->adminCookies());

		self::assertSame(200, $edit['status'], $edit['raw']);
		self::assertSame([$base . Fixtures::PROJECT_PATH], $this->hrefsOf($edit['raw'], "#<a\\b[^>]*\\bid='_ProcessPageEditView'[^>]*>#"));
		$dropdown = $this->hrefsOf($edit['raw'], "#<li class='page-view-action-[a-z]+'><a\\b[^>]*>#");
		self::assertCount(4, $dropdown);
		self::assertSame([$base . Fixtures::PROJECT_PATH], array_values(array_unique($dropdown)));
		self::assertStringNotContainsString("href='" . $project->httpUrl . "'", $edit['raw']);
	}

	public function testSaveAndViewRedirectsToTheFrontend(): void {
		$article = self::$pages['article'];
		$edit = $this->siteRequest('/redaktion/page/edit/?id=' . $article->id, $this->adminCookies());
		self::assertSame(200, $edit['status'], $edit['raw']);

		preg_match_all('#<input\\b[^>]*>#i', $edit['raw'], $inputs);
		$fields = [];
		foreach ($inputs[0] as $input) {
			if (!preg_match('#\\bname=[\'"]([^\'"\\[]+)[\'"]#', $input, $name) || preg_match('#\\btype=[\'"](checkbox|radio|file|submit)[\'"]#i', $input)) {
				continue;
			}
			$fields[$name[1]] = preg_match('#\\bvalue=[\'"]([^\'"]*)[\'"]#', $input, $value) ? html_entity_decode($value[1], ENT_QUOTES) : '';
		}
		$fields['submit_save'] = '1';
		$fields['_after_submit_action'] = 'view';

		$saved = $this->siteRequest('/redaktion/page/edit/?id=' . $article->id, $this->adminCookies(), [], http_build_query($fields));

		self::assertContains($saved['status'], [301, 302, 303], $saved['raw']);
		self::assertSame($this->frontendBaseUrl() . '/aktuelles/' . Fixtures::HTML_PREFIX . 'article/', $saved['headers']['location'] ?? null);
	}

	public function testAdminTreePagesKeepTheirOwnViewLink(): void {
		$adminRoot = wire('pages')->get((int) wire('config')->adminRootPageID);
		$edit = $this->siteRequest('/redaktion/page/edit/?id=' . $adminRoot->id, $this->adminCookies());

		self::assertSame(200, $edit['status'], $edit['raw']);
		foreach ($this->hrefsOf($edit['raw'], "#<a\\b[^>]*\\bid='_ProcessPageEditView'[^>]*>#") as $href) {
			self::assertStringNotContainsString($this->frontendBaseUrl(), $href);
		}
	}

	private function frontendBaseUrl(): string {
		$base = rtrim((string) wire('modules')->getConfig('SeoMaestro', 'baseUrl'), '/');
		self::assertNotSame('', $base, 'The test expects the SeoMaestro base URL to be configured.');

		return $base;
	}

	/**
	 * URL of the "View" action that the page list offers for a page, read
	 * from the JSON the page tree loads when it opens the parent.
	 */
	private function pageListViewUrl(int $parentId, int $pageId): string {
		$list = $this->siteRequest('/redaktion/page/list/?id=' . $parentId . '&render=JSON&start=0&limit=200&open=' . $pageId . '&mode=&getActions=1', $this->adminCookies(), ['X-Requested-With: XMLHttpRequest']);
		self::assertSame(200, $list['status'], $list['raw']);
		$json = json_decode($list['raw'], true);
		self::assertIsArray($json, $list['raw']);

		$children = $parentId === 0 ? [$json['page'] ?? []] : ($json['children'] ?? []);
		foreach ($children as $child) {
			if (($child['id'] ?? null) === $pageId) {
				foreach ($child['actions'] ?? [] as $action) {
					if (($action['cn'] ?? null) === 'View') {
						return (string) $action['url'];
					}
				}
				self::fail('The page list offers no View action for page ' . $pageId);
			}
		}
		self::fail('Page ' . $pageId . ' is not in the page list answer: ' . substr($list['raw'], 0, 500));
	}

	/**
	 * @return string[] href values of the anchors that match the pattern
	 */
	private function hrefsOf(string $html, string $anchorPattern): array {
		preg_match_all($anchorPattern, $html, $anchors);
		$hrefs = [];
		foreach ($anchors[0] as $anchor) {
			if (preg_match("#\\bhref='([^']*)'#", $anchor, $href)) {
				$hrefs[] = html_entity_decode($href[1], ENT_QUOTES);
			}
		}

		return $hrefs;
	}

	public function testLinkPreviewIsReachable(): void {
		$response = $this->siteRequest('/link-preview/?path=' . rawurlencode('/'));

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertMatchesRegularExpression('#<title>[^<]+</title>#', $response['raw']);
	}

	public function testSitemapIsReachable(): void {
		$response = $this->siteRequest('/sitemap.xml');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertStringStartsWith('<?xml', $response['raw']);
		self::assertStringContainsString('<urlset', $response['raw']);
	}

	public function testApiStillAnswersPageRequestsWithJson(): void {
		$response = $this->apiRequest('GET', 'tpage/test-fixture-content');

		self::assertSame(200, $response['status'], $response['raw']);
		self::assertSame('Test Fixture Content', $response['json']['title']);
		self::assertSame(Fixtures::page(Fixtures::CONTENT_PATH)->id, $response['json']['id']);
	}

	/**
	 * @param string[] $headers
	 */
	private function assertNotFoundLikeAnUnknownPath(string $path, ?string $cookies, array $headers = []): void {
		$page = wire('pages')->get('path=' . $path . ', include=all');
		self::assertGreaterThan(0, $page->id, 'Page not found in the test database: ' . $path);

		$response = $this->siteRequest($path, $cookies, $headers);
		$unknown = $this->siteRequest(self::UNKNOWN_PATH, $cookies, $headers);

		self::assertSame(404, $response['status'], $response['raw']);
		self::assertSame(404, $unknown['status'], $unknown['raw']);
		self::assertSame($unknown['raw'], $response['raw']);
		self::assertStringNotContainsString((string) $page->title, $response['raw']);
		self::assertStringNotContainsString('<', $response['raw']);
	}

	private function assertIndistinguishableFromUnknownPath(string $path, ?string $cookies): void {
		$page = wire('pages')->get('path=' . $path . '/, include=all');
		self::assertGreaterThan(0, $page->id, 'Page not found in the test database: ' . $path);

		$response = $this->siteRequest($path, $cookies);
		$unknown = $this->siteRequest(rtrim(self::UNKNOWN_PATH, '/'), $cookies);

		self::assertSame(404, $unknown['status'], $unknown['raw']);
		self::assertIndistinguishable($unknown, $response, $path);
	}

	/**
	 * Logs a new superuser in through the admin login form once and returns
	 * the cookie file of that session.
	 */
	private function adminCookies(): string {
		if (self::$adminCookies !== null) {
			return self::$adminCookies;
		}

		$password = bin2hex(random_bytes(16));
		Fixtures::createHtmlAdmin($password);
		self::$adminCookies = (string) tempnam(sys_get_temp_dir(), 'mf-test-admin-cookies-');

		$form = $this->siteRequest('/redaktion/', self::$adminCookies);
		self::assertSame(200, $form['status'], $form['raw']);
		preg_match_all('#<input\b[^>]*\btype=[\'"]hidden[\'"][^>]*>#i', $form['raw'], $inputs);
		$fields = [];
		foreach ($inputs[0] as $input) {
			if (preg_match('#\bname=[\'"]([^\'"]+)[\'"]#', $input, $name) && preg_match('#\bvalue=[\'"]([^\'"]*)[\'"]#', $input, $value)) {
				$fields[$name[1]] = html_entity_decode($value[1], ENT_QUOTES);
			}
		}
		$fields['login_name'] = Fixtures::HTML_ADMIN_NAME;
		$fields['login_pass'] = $password;
		$fields['login_submit'] = '1';

		$this->siteRequest('/redaktion/', self::$adminCookies, [], http_build_query($fields));

		$admin = $this->siteRequest('/redaktion/page/', self::$adminCookies);
		self::assertStringNotContainsString('name="login_pass"', $admin['raw'], 'The admin login of the test account failed.');

		return self::$adminCookies;
	}

	/**
	 * Request below the site root (outside the API endpoint), without API key.
	 * With $cookies, cookies are read from and written to that file. A POST is
	 * sent when $body is given.
	 *
	 * @param string[] $headers
	 * @return array{status: int, raw: string, headers: array<string, string>}
	 */
	private function siteRequest(string $path, ?string $cookies = null, array $headers = [], ?string $body = null): array {
		$apiUrl = getenv('MF_TEST_API_URL') ?: 'https://127.0.0.1:8001/api/';
		$siteUrl = preg_replace('#/api/?$#', '', rtrim($apiUrl, '/'));

		$responseHeaders = [];
		$ch = curl_init($siteUrl . $path);
		curl_setopt_array($ch, [
			CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
				$parts = explode(':', $line, 2);
				if (count($parts) === 2) {
					$responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
				}

				return strlen($line);
			},
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => $headers,
		]);
		if ($cookies !== null) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
		}
		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		}
		$raw = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return ['status' => $status, 'raw' => (string) $raw, 'headers' => $responseHeaders];
	}
}
