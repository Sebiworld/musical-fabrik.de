<?php
namespace ProcessWire;

class ConfigApi {
	public static function getConfiguration($data) {
		$output = [
		];

		$configPage = wire('pages')->get('template.name=configuration');
		if ($configPage instanceof Page && $configPage->id) {
			if ($configPage->template->hasField('short_text')) {
				$output['site_name'] = $configPage->short_text;
				$output['author'] = $configPage->short_text;
			}

			if ($configPage->template->hasField('main_image')) {
				$output['main_image'] = AppApi::getAjaxOf($configPage->main_image);
			}

			if ($configPage->template->hasField('logo_square')) {
				$output['logo_square'] = AppApi::getAjaxOf($configPage->logo_square);
			}

			if ($configPage->template->hasField('images')) {
				$output['placeholder_images'] = AppApi::getAjaxOf($configPage->images);
			}

			if ($configPage->template->hasField('seo_title')) {
				$output['seo_title'] = $configPage->seo_title;
			}

			if ($configPage->template->hasField('seo_description')) {
				$output['seo_description'] = $configPage->seo_description;
			}
		}

		if (wire('modules')->isInstalled('MfAuth')) {
			$mfAuthModule = wire('modules')->get('MfAuth');

			$output['activate_login'] = (bool)$mfAuthModule->activate_login;
			$output['activate_registration'] = (bool)$mfAuthModule->activate_registration;
		}

		if (!empty(wire('config')->apiConfig) && is_array(wire('config')->apiConfig)) {
			foreach (wire('config')->apiConfig as $key => $value) {
				$output[$key] = $value;
			}
		}

		$output['hash'] = md5(json_encode($output));
		if (!empty($data->hash) && $output['hash'] === $data->hash) {
			throw new AppApiException('No new contents', 204, ['errorcode' => 'no_new_contents']);
		}

		return $output;
	}

	private static function getNavItemOutput($navItem) {
		$item = [
			'id' => $navItem->id,
			'title' => $navItem->title,
		];

		if ($navItem->type === 'page_reference') {
			if ($navItem->template->hasField('page_reference') && $navItem->page_reference->id) {
				if (!$navItem->page_reference->viewable()) {
					return null;
				}

				$item['page'] = AppApi::getAjaxOf($navItem->page_reference);
			}

			if ($navItem->template->hasField('section_name') && $navItem->section_name) {
				$item['section'] = $navItem->section_name;
			}
		} elseif ($navItem->type === 'link') {
			$item['link'] = $navItem->link;
		}

		return $item;
	}

	public static function getMenues($data) {
		$output = [
			'main_navigation' => [],
			'secondary_navigation' => [],
			'tertiary_navigation' => [],
			'socialmedia_navigation' => []
		];

		$headerPage = wire('pages')->get('template=header');
		if ($headerPage instanceof Page && $headerPage->id) {
			if ($headerPage->template->hasField('main_navigation') && count($headerPage->main_navigation) > 0) {
				foreach ($headerPage->main_navigation as $navItem) {
					$item = SELF::getNavItemOutput($navItem);

					if (!empty($item)) {
						$output['main_navigation'][] = $item;
					}
				}
			}

			if ($headerPage->template->hasField('secondary_navigation') && count($headerPage->main_navigation) > 0) {
				foreach ($headerPage->secondary_navigation as $navItem) {
					$item = SELF::getNavItemOutput($navItem);

					if (!empty($item)) {
						$output['secondary_navigation'][] = $item;
					}
				}
			}
		}

		$footerPage = wire('pages')->get('template=footer');
		if ($footerPage instanceof Page && $footerPage->id) {
			if ($footerPage->template->hasField('main_navigation') && count($headerPage->main_navigation) > 0) {
				foreach ($footerPage->main_navigation as $navItem) {
					$item = SELF::getNavItemOutput($navItem);

					if (!empty($item)) {
						$output['tertiary_navigation'][] = $item;
					}
				}
			}

			if ($footerPage->template->hasField('socialmedia_links') && count($footerPage->socialmedia_links) > 0) {
				foreach ($footerPage->socialmedia_links as $navItem) {
					$output['socialmedia_navigation'][] = [
						'id' => $navItem->id,
						'title' => $navItem->title,
						'ionicon' => $navItem->ionicon,
						'link' => $navItem->link
					];
				}
			}
		}

		$output['hash'] = md5(json_encode($output));
		if (!empty($data->hash) && $output['hash'] === $data->hash) {
			throw new AppApiException('No new contents', 204, ['errorcode' => 'no_new_contents']);
		}

		return $output;
	}
}
