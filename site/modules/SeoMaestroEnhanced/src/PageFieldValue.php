<?php

namespace SeoMaestroEnhanced;

use ProcessWire\Field;
use ProcessWire\Page;
use ProcessWire\SeoMaestroEnhanced;
use ProcessWire\WireData;
use ProcessWire\WireException;

/**
 * The page field value returned by fields of type "FieldtypeSeoMaestroEnhanced".
 */
class PageFieldValue extends WireData
{
    /**
     * @var \ProcessWire\Page
     */
    private $page;

    /**
     * @var \ProcessWire\Field
     */
    private $field;

    /**
     * @var \Processwire\SeoMaestroEnhanced
     */
    private $seoMaestroEnhanced;

    /**
     * @var array
     */
    private $seoData = [];

    /**
     * @param \ProcessWire\Page $page
     * @param \ProcessWire\Field $field
     * @param \ProcessWire\SeoMaestroEnhanced $seoMaestroEnhanced
     * @param array $data
     */
    public function __construct(Page $page, Field $field, SeoMaestroEnhanced $seoMaestroEnhanced, array $data = [])
    {
        parent::__construct();

        $this->page = $page;
        $this->field = $field;
        $this->seoMaestroEnhanced = $seoMaestroEnhanced;
        $this->setDefaultData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if ($key === 'og') {
            return $this->getSeoData('opengraph');
        }

        if ($this->isSeoGroup($key)) {
            return $this->getSeoData($key);
        }

        return parent::get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        static $regex = null;

        // Make sure to only allow keys of valid seo data, e.g. meta_title.
        if ($regex === null) {
            $seoFields = implode('|', array_keys($this->field->type->getSeoData()));
            $languageIds = $this->wire('languages') ? $this->wire('languages')->getAll()->implode('|', 'id') : '';
            $regex = sprintf('/^(%s)(%s){0,1}$/', $seoFields, $languageIds);
        }

        if (!preg_match($regex, $key)) {
            throw new WireException(sprintf('"%s" is not a valid key for a SeoMaestroEnhanced page value', $key));
        }

        return parent::set($key, $value);
    }

    /**
     * Render meta tags of all groups.
     *
     * @return string
     */
    public function render()
    {
        $tags = array_map(function ($group) {
            return $this->getSeoData($group)->render();
        }, $this->field->type->getSeoGroups());

        $tags += $this->seoMaestroEnhanced->renderMetatags($this->getCommonMetatags());

        $webmasterToolsTags = $this->getWebmasterToolsMetatags();
        if (count($webmasterToolsTags)) {
            $tags += $this->seoMaestroEnhanced->renderMetatags($webmasterToolsTags);
        }

        $tags = array_filter($tags, function ($tag) {
            return $tag !== '';
        });

        return implode("\n", $tags);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * @return \ProcessWire\Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return \ProcessWire\Field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Build common metatags not configured via fieldtype.
     *
     * @return array
     */
    private function getCommonMetatags()
    {
        $baseUrl = $this->seoMaestroEnhanced->get('baseUrl');
        $defaultLang = $this->seoMaestroEnhanced->get('defaultLanguage') ?: 'en';
        $hasLanguageSupportPageNames = $this->wire('modules')->isInstalled('LanguageSupportPageNames');

        $tags = ['meta_generator' => '<meta name="generator" content="ProcessWire">'];

        if ($hasLanguageSupportPageNames) {
            foreach ($this->wire('languages') ?: [] as $language) {
                if (!$this->page->viewable($language)) {
                    continue;
                }

                $code = $language->isDefault() ? $defaultLang : $language->name;
                $url = $baseUrl ? $baseUrl . \ProcessWire\AppApi::getUrlRelativeToRoot($this->page->localUrl($language)) : \ProcessWire\AppApi::getHttpUrlRelativeToRoot($this->page->localHttpUrl($language));

                $tags["link_rel_{$code}"] = sprintf('<link rel="alternate" href="%s" hreflang="%s">', $url, $code);

                if ($language->isDefault()) {
                    $tags['link_rel_default'] = sprintf('<link rel="alternate" href="%s" hreflang="x-default">', $url);
                }
            }
        }

        return $tags;
    }

    /**
     * Return the meta tags from the webmaster tools section.
     *
     * @return array
     */
    private function getWebmasterToolsMetatags()
    {
        $tags = [];
        $field = $this->getFieldInCurrentContext();

        if ($field->get('webmaster_tools_google_code')) {
            $tags['webmaster_tools_google_code'] = sprintf(
                '<meta name="google-site-verification" content="%s">',
                $this->wire('sanitizer')->entities($field->get('webmaster_tools_google_code'))
            );
        }

        if ($field->get('webmaster_tools_bing_code')) {
            $tags['webmaster_tools_bing_code'] = sprintf(
                '<meta name="msvalidate.01" content="%s">',
                $this->wire('sanitizer')->entities($field->get('webmaster_tools_bing_code'))
            );
        }

        return $tags;
    }

    private function isSeoGroup($name)
    {
        return in_array($name, $this->field->type->getSeoGroups());
    }

    /**
     * @param string $group
     *
     * @return \SeoMaestroEnhanced\SeoDataInterface
     */
    private function getSeoData($group)
    {
        if (!isset($this->seoData[$group])) {
            $class = sprintf('\\SeoMaestroEnhanced\\%sSeoData', ucfirst($group));
            $data = $this->getDataOfGroup($group);
            $seoData = $this->wire(new $class($this, $this->seoMaestroEnhanced, $data));
            $this->seoData[$group] = $seoData;
        }

        return $this->seoData[$group];
    }

    /**
     * @param string $group
     *
     * @return array
     */
    private function getDataOfGroup($group)
    {
        $data = [];

        foreach ($this->data as $key => $value) {
            list($_group, $name) = explode('_', $key);
            if ($_group === $group) {
                $data[$name] = $value;
            }
        }

        return $data;
    }

    private function setDefaultData(array $data)
    {
        $defaults = [];
        foreach (array_keys($this->field->type->getSeoData()) as $name) {
            $defaults[$name] = 'inherit';
        }

        $this->data = array_merge($defaults, $data);
    }

    /**
     * Get the field in the context of the page's template.
     *
     * @return \ProcessWire\Field
     */
    private function getFieldInCurrentContext()
    {
        return $this->page
            ->get('template')
            ->get('fieldgroup')
            ->getField($this->field, true);
    }
}
