<?php

namespace ProcessWire;

class ProjectService extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    $this->projectPage = $this->getProjectPage();
    if (isset($args['projectPage']) && $args['projectPage'] instanceof Page && $args['projectPage']->id) {
      $this->projectPage = $args['projectPage'];
    }

    if (!($this->projectPage instanceof Page) || !$this->projectPage->id) {
      $this->projectPage = wire('pages')->get('/');
    }
  }

  public function getProjectPage($page = false) {
    if (!($page instanceof Page)) {
      $page = $this->page;
    }

    if (!$this->startsWith($page->template->name, 'project') || $page->template->name === 'project_role' || $page->template->name === 'project_roles_container' || $page->template->name === 'project_container') {
      $page = $page->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
    }

    if ($page instanceof Page && $page->id) {
      return $page;
    }
    return new NullPage();
  }

  public function getProjectPageWithFallback($page = false) {
    $page = $this->getProjectPage($page);
    if ($page instanceof Page && $page->id) {
      return $page;
    }
    return wire('pages')->get('/');
  }

  public function isProjectPage($page = false) {
    if (!($page instanceof Page)) {
      $page = $this->projectPage;
    }
    return $page instanceof Page && substr($page->template->name, 0, 7) === 'project' && $page->template->name !== 'project_role' && $page->template->name !== 'project_roles_container' && $page->template->name !== 'projects_container';
  }

  public function getPortraitsContainer() {
    return wire('pages')->findOne('template.name=portraits_container, include=hidden, has_parent=' . $this->projectPage->id);
  }

  public function getSeasonsContainer() {
    return wire('pages')->findOne('template.name=seasons_container, include=hidden, has_parent=' . $this->projectPage->id);
  }

  public function getCastsContainer() {
    return wire('pages')->findOne('template.name=casts_container, include=hidden, has_parent=' . $this->projectPage->id);
  }

  protected function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
  }

  public function getTextColorOver($color) {
    return $this->isColorLight($color) ? '#333' : '#f1f1f1';
  }

  public function isColorLight($color) {
    if (!$color || !is_string($color) || empty($color)) {
      return true;
    }

    $rgb = $this->htmlToRgb($color);
    $hsl = $this->rgbToHsl($rgb);
    if (!$hsl) {
      return true;
    }

    return $hsl->lightness > 200;
  }

  protected function htmlToRgb($htmlCode) {
    if ($htmlCode[0] == '#') {
      $htmlCode = substr($htmlCode, 1);
    }

    if (strlen($htmlCode) == 3) {
      $htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
    }

    $r = hexdec($htmlCode[0] . $htmlCode[1]);
    $g = hexdec($htmlCode[2] . $htmlCode[3]);
    $b = hexdec($htmlCode[4] . $htmlCode[5]);

    return $b + ($g << 0x8) + ($r << 0x10);
  }

  protected function rgbToHsl($RGB) {
    $r = 0xFF & ($RGB >> 0x10);
    $g = 0xFF & ($RGB >> 0x8);
    $b = 0xFF & $RGB;

    $r = ((float)$r) / 255.0;
    $g = ((float)$g) / 255.0;
    $b = ((float)$b) / 255.0;

    $maxC = max($r, $g, $b);
    $minC = min($r, $g, $b);

    $l = ($maxC + $minC) / 2.0;

    if ($maxC == $minC) {
      $s = 0;
      $h = 0;
    } else {
      if ($l < .5) {
        $s = ($maxC - $minC) / ($maxC + $minC);
      } else {
        $s = ($maxC - $minC) / (2.0 - $maxC - $minC);
      }
      if ($r == $maxC) {
        $h = ($g - $b) / ($maxC - $minC);
      }
      if ($g == $maxC) {
        $h = 2.0 + ($b - $r) / ($maxC - $minC);
      }
      if ($b == $maxC) {
        $h = 4.0 + ($r - $g) / ($maxC - $minC);
      }

      $h = $h / 6.0;
    }

    $h = (int)round(255.0 * $h);
    $s = (int)round(255.0 * $s);
    $l = (int)round(255.0 * $l);

    return (object) ['hue' => $h, 'saturation' => $s, 'lightness' => $l];
  }
}
