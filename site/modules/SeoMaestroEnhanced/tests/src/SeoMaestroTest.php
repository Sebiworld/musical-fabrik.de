<?php

namespace SeoMaestroEnhanced\Test;

use ProcessWire\HookEvent;
use SeoMaestroEnhanced\SitemapManager;

/**
 * Tests for the SeoMaestroEnhanced class.
 *
 * @coversDefaultClass \ProcessWire\SeoMaestroEnhanced
 */
class SeoMaestroEnhancedTest extends FunctionalTestCase
{
    const TEMPLATE_NAME = 'seoTestTemplate';

    const FIELD_NAME = 'seoTestField';

    /**
     * @var \ProcessWire\Template
     */
    private $template;

    /**
     * @var \ProcessWire\Field
     */
    private $field;

    /**
     * @var \ProcessWire\SeoMaestroEnhanced
     */
    private $seoMaestroEnhanced;

    /**
     * @var string
     */
    private $sitemap;

    protected function setUp()
    {
        parent::setUp();

        $this->sitemap = dirname(__DIR__) . '/temp/sitemap.test.xml';
        $this->seoMaestroEnhanced = $this->wire('modules')->get('SeoMaestroEnhanced');
    }

    /**
     * @test
     * @covers ::getSitemapManager
     */
    public function it_should_return_the_sitemap_manager()
    {
        $this->assertInstanceOf(SitemapManager::class, $this->seoMaestroEnhanced->getSitemapManager());
    }

    /**
     * @dataProvider sitemapGenerationDataProvider
     */
    public function test_sitemap_generation($user, $page, array $moduleConfig, $shouldGenerate)
    {
        $this->createTemplateAndField();
        $this->createPage($this->template, '/');

        $this->wire('page', $page);
        $this->wire('users')->setCurrentUser($user);

        $this->seoMaestroEnhanced->setArray($moduleConfig);
        $this->seoMaestroEnhanced->set('sitemapPath', 'site/modules/SeoMaestroEnhanced/tests/temp/sitemap.test.xml');
        $this->seoMaestroEnhanced->ready();

        $this->assertFileNotExists($this->sitemap);

        // Trigger ProcessWire::finished() by calling ProcessPageView::finished().
        $this->wire('process')->finished();

        if ($shouldGenerate) {
            $this->assertFileExists($this->sitemap);
        } else {
            $this->assertFileNotExists($this->sitemap);
        }
    }

    public function test_hook_render_seo_data_value()
    {
        $this->createTemplateAndField();
        $page = $this->createPage($this->template, '/');
        $page->title = 'Seo Maestro';
        $page->save();

        $this->assertEquals('Seo Maestro', $page->get(self::FIELD_NAME)->meta->title);

        $hookId = $this->wire->addHookAfter('SeoMaestroEnhanced::renderSeoDataValue', function (HookEvent $event) {
            $group = $event->arguments(0);
            $name = $event->arguments(1);
            $value = $event->arguments(2);

            if ($group === 'meta' && $name === 'title') {
                $event->return = $value . ' | acme.com';
            }
        });

        $this->assertEquals('Seo Maestro | acme.com', $page->get(self::FIELD_NAME)->meta->title);

        $this->wire->removeHook($hookId);
    }

    public function test_hook_render_metatags()
    {
        $this->createTemplateAndField();
        $page = $this->createPage($this->template, '/');
        $page->title = 'Seo Maestro';
        $page->get(self::FIELD_NAME)->meta->description = 'A meta description';
        $page->save();

        $expected = "<title>Seo Maestro</title>\n<meta name=\"description\" content=\"A meta description\">\n<link rel=\"canonical\" href=\"http://localhost/en/untitled-page/\">";

        $this->assertEquals($expected, $page->get(self::FIELD_NAME)->meta->render());

        $this->wire->addHookAfter('SeoMaestro::renderMetatags', function (HookEvent $event) {
            $tags = $event->arguments(0);
            $group = $event->arguments(1);

            // Remove the description and canonical URL.
            if ($group === 'meta') {
                unset($tags['description']);
                unset($tags['canonicalUrl']);
                $event->return = $tags;
            }
        });

        $this->assertEquals('<title>Seo Maestro</title>', $page->get(self::FIELD_NAME)->meta->render());
    }

    public function sitemapGenerationDataProvider()
    {
        $guestUser = $this->wire('users')->getGuestUser();
        $adminUser = $this->wire('users')->get('admin');
        $homePage = $this->wire('pages')->get('/');
        $adminPage = $this->wire('pages')->get('template=admin');

        return [
            [
                $guestUser,
                $homePage,
                [
                    'sitemapEnable' => 1,
                    'sitemapCache' => 0,
                ],
                false,
            ],
            [
                $adminUser,
                $homePage,
                [
                    'sitemapEnable' => 1,
                    'sitemapCache' => 0,
                ],
                false,
            ],
            [
                $adminUser,
                $adminPage,
                [
                    'sitemapEnable' => 0,
                    'sitemapCache' => 0,
                ],
                false,
            ],
            [
                $adminUser,
                $adminPage,
                [
                    'sitemapEnable' => 1,
                    'sitemapCache' => 0,
                ],
                true,
            ],
        ];
    }

    public function __destruct()
    {
        $this->deleteSitemap();
    }


    protected function tearDown()
    {
        parent::tearDown();

        $this->deleteSitemap();
    }

    private function deleteSitemap()
    {
        if (is_file($this->sitemap)) {
            unlink($this->sitemap);
        }
    }

    private function createTemplateAndField()
    {
        $fieldtype = $this->wire('fieldtypes')->get('FieldtypeSeoMaestroEnhanced');
        $this->field = $this->createField($fieldtype, self::FIELD_NAME);

        $this->template = $this->createTemplate(self::TEMPLATE_NAME, dirname(__DIR__) . '/templates/dummy.php');
        $this->template->fieldgroup->add($this->field);
        $this->template->save();
    }
}
