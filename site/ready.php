<?php

namespace ProcessWire;

$wire->addHookAfter('InputfieldPage::getSelectablePages', function ($event) {
    if ($event->object->hasField == 'portraits') {
        $projectpage = $event->arguments('page')->closest('template.name^=project, template.name!=project_role, template.name!=project_roles_container, template.name!=projects_container');
        if (!($projectpage instanceof Page) || !$projectpage->id) {
            $projectpage = $event->pages->get('/');
        }
        $event->return = $projectpage->find('template.name=portrait, sort=title');
    }
});

// Add the brand name after the title.
$wire->addHookAfter('SeoMaestro::renderSeoDataValue', function (HookEvent $event) {
    $group = $event->arguments(0);
    $name = $event->arguments(1);
    $value = $event->arguments(2);

    // Insert default values if empty:
    if (empty($value)) {
        if ($name === 'image') {
            $value = '/site/templates/assets/static_img/mf_hero.jpg';
            $event->return = $value;
        } elseif ($group === 'meta' && $name === 'description') {
            $value = 'Der Musical-Fabrik e.V. ist ein gemeinnütziger Verein, der generations-, interessen- und grenzübergreifend Menschen die Gelegenheit gibt, ihr Können auszuleben.';
            $event->return = $value;
        }
    }

    if ($group === 'meta' && $name === 'title') {
        $event->return = htmlspecialchars(strip_tags($value));
    } elseif ($name === 'description') {
        $event->return = htmlspecialchars(trim(str_replace('&nbsp;', ' ', strip_tags($value))));
    }
});