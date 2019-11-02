<?php
namespace ProcessWire;

class SharingBox extends TwackComponent {

	protected $viaText = '';
	protected $shareUrl = '';
	protected $shareTitle = '';

	public function __construct($args) {
		parent::__construct($args);

		$this->viaText = wire('pages')->get('/')->httpUrl;

		$this->title = $this->_('Tell a friend!');
		if (isset($args['title']) && !empty($args['title'])) {
			$this->title = str_replace(array("\n", "\r"), '', $args['title']);
		}

		$this->shareUrl = urlencode($this->page->httpUrl);
		$this->shareTitle = urlencode("'{$this->page->title}'");

		$this->socialLinks = new WireArray();
		$this->addSocialLink(
			'ion-logo-twitter',
			"https://twitter.com/intent/tweet?text={$this->shareTitle}-&amp;url={$this->shareUrl}&amp;via={$this->viaText}",
			sprintf($this->_('Share "%1$s" on Twitter'), $this->page->title)
		);

		$this->addSocialLink(
			'ion-logo-facebook',
			"https://www.facebook.com/sharer/sharer.php?u={$this->shareUrl}",
			sprintf($this->_('Share "%1$s" on Facebook'), $this->page->title)
		);

		// $this->addSocialLink(
		// 	'ion-logo-googleplus',
		// 	'https://plus.google.com/share?url='.$this->shareUrl,
		// 	sprintf($this->_('Share "%1$s" on GooglePlus'), $this->page->title)
		// 	);

		// $this->addSocialLink(
		// 	'ion-logo-linkedin',
		// 	'https://www.linkedin.com/shareArticle?mini=true&url='.$this->shareUrl.'&amp;title='.$this->shareTitle,
		// 	sprintf($this->_('Share "%1$s" on LinkedIn'), $this->page->title)
		// 	);

		$this->addSocialLink(
			'ion-logo-whatsapp',
			'whatsapp://send?text='.urlencode("Huhu! Ich habe hier eine tolle Seite gefunden: {$this->shareTitle} ") . $this->shareUrl,
			sprintf($this->_('Share "%1$s" on WhatsApp'), $this->page->title)
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
