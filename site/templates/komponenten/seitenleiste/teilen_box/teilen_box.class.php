<?php
namespace ProcessWire;

class TeilenBox extends TwackComponent {

	protected $viaText = '';
	protected $shareUrl = '';
	protected $shareTitel = '';

	public function __construct($args) {
		parent::__construct($args);

		$this->viaText = wire('pages')->get('/')->httpUrl;

		$this->titel = 'Weitersagen!';
		if (isset($args['titel']) && !empty($args['titel'])) {
			$this->titel = str_replace(array("\n", "\r"), '', $args['titel']);
		}

		$this->shareUrl = urlencode($this->page->httpUrl);
		$this->shareTitel = urlencode("'{$this->page->title}'");

		$this->socialLinks = new WireArray();
		$this->addSocialLink(
			'ion-logo-twitter',
			"https://twitter.com/intent/tweet?text={$this->shareTitel}-&amp;url={$this->shareUrl}&amp;via={$this->viaText}",
			"'{$this->page->title}' bei Twitter teilen"
		);

		$this->addSocialLink(
			'ion-logo-facebook',
			"https://www.facebook.com/sharer/sharer.php?u={$this->shareUrl}",
			"'{$this->page->title}' bei Facebook teilen"
		);

		// $this->addSocialLink(
		// 	'ion-logo-googleplus',
		// 	'https://plus.google.com/share?url='.$this->shareUrl,
		// 	'"'.$this->page->title.'" bei GooglePlus teilen'
		// 	);

		// $this->addSocialLink(
		// 	'ion-logo-linkedin',
		// 	'https://www.linkedin.com/shareArticle?mini=true&url='.$this->shareUrl.'&amp;title='.$this->shareTitel,
		// 	'"'.$this->page->title.'" bei LinkedIn teilen'
		// 	);

		$this->addSocialLink(
			'ion-logo-whatsapp',
			'whatsapp://send?text='.urlencode("Huhu! Ich habe hier eine tolle Seite gefunden: {$this->shareTitel} ") . $this->shareUrl,
			"'{$this->page->title}' per WhatsApp empfehlen"
		);

		//'whatsapp://send?text='.$crunchifyTitle . ' ' . $crunchifyURL;
	}

	public function addSocialLink($ionIcon, $link = '#', $title = '', $onclick = false) {
		$newLink = new WireData();

		$newLink->icon = $ionIcon;
		$newLink->link = $link;
		$newLink->title = $title;
		$newLink->onclick = $onclick;

		$this->socialLinks->add($newLink);
	}
}
