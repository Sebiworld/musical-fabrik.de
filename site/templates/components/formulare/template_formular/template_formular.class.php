<?php
namespace ProcessWire;

class TemplateFormular extends TwackComponent {

	protected $containerSeite;
	protected $formularTemplate;
	protected $platzhaltertexte;
	protected $idService;

	protected $emailParams;

	public function __construct($args) {
		parent::__construct($args);

		require_once __DIR__ . "/../formular_exception.class.php";

		// Standardwerte für die Email:
		$this->emailParams = array(
			'betreff' => 'Neue Anfrage',
			'plain' => array(
				'inhalt' => '',
				'signatur' => ''
			),
			'html' => array(
				'inhalt' => '',
				'signatur' => ''
			),
			'empfaenger' => '',
			'empfaengerCC' => '',
			'empfaengerBCC' => '',
			'platzhalter' => array(
				'html' => array(),
				'plain' => array()
			)
		);

		$this->idService = $this->getService('IdService');

		// Container-Seite bestimmen. Eine Containerseite muss das TemplateSelect-Feld "formular_template" haben, in dem angegeben wird, welches Template als Formular benutzt werden soll. Das Template muss als Child-Template für die Container-Seite zugelassen sein, damit Seiten mit den ausgefüllten Formularen unterhalb der Containerseite angelegt werden können.
		if (isset($args['containerSeite']) && $args['containerSeite'] instanceof Page && $args['containerSeite']->id) {
			$this->containerSeite = $args['containerSeite'];
		} else {
			$this->containerSeite = $this->page->get('formular');
		}

		if (!($this->containerSeite instanceof Page) || !$this->containerSeite->id) {
			throw new ComponentNotInitializedException('TemplateFormular', 'An das Formular wurde keine valide Containerseite übergeben.');
		}

		// Platzhalter-Sammlung befüllen. {{Platzhalter}} werden durch die angegebene Entsprechung ersetzt.
		$this->platzhaltertexte = array();
		if (isset($args['platzhaltertexte']) && is_array($args['platzhaltertexte'])) {
			$this->platzhaltertexte = $args['platzhaltertexte'];
		}
		if ($this->containerSeite->template->hasField('platzhalter') && $this->containerSeite->platzhalter) {
			foreach ($this->containerSeite->platzhalter as $platzhalter) {
				$this->platzhaltertexte["{$platzhalter->kurztext}"] = $platzhalter->freitext;
			}
		}

		// Welches Template soll für die Formular-Auswertung benutzt werden?
		if (isset($args['nutzeTemplate'])) {
			if ($args['nutzeTemplate'] instanceof Template) {
				$this->template = $args['nutzeTemplate'];
			} else {
				$this->template = wire('templates')->get($args['nutzeTemplate']);
			}
		} elseif ($this->containerSeite->template->hasField('formular_template') && !empty($this->containerSeite->formular_template)) {
			$this->template = $this->containerSeite->formular_template[0];
		}

		if (!($this->template instanceof Template) || !$this->template->id) {
			throw new ComponentNotInitializedException('TemplateFormular', 'Auf der Container-Seite wurde kein Formular-Template definiert.');
		}

		require_once __DIR__ . "/formular_ausgabe_typ.class.php";

		// Formularausgabe bestimmen. Später sollen auch andere Möglichkeiten als Bootstrap möglich sein:
		$formularAusgabeArgs = array(
			'name' => 'formularAusgabe',
			'platzhaltertexte' => $this->platzhaltertexte
		);
		if (isset($args['ausgabeTyp']) && $args['ausgabeTyp'] == 'bootstrap') {
			$this->addComponent('BootstrapFormularAusgabe', $formularAusgabeArgs);
		} else {
			$this->addComponent('BootstrapFormularAusgabe', $formularAusgabeArgs);
		}

		if ($this->getComponent('formularAusgabe') instanceof TwackNullComponent) {
			throw new ComponentNotInitializedException('TemplateFormular', 'Es wurde keine Ausgabeklasse gefunden.');
		}

		// Mit dem Setzen des Templates wird dieses durchlaufen und es wird ausgewertet, welche Felder generiert und validiert werden müssen:
		$this->setTemplate($this->template);

		if (isset($args['formularName']) && empty($args['formularName'])) {
			$this->formularName = $args['formularName'];
		}

		// Das Formular-Skript macht das Formular Ajax-fähig. Eine Auswertung ohne Ajax ist nicht vorgesehen.
		$this->addScript(wire('config')->urls->templates . 'assets/js/formular.min.js', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/template_formular.min.css', true, true);
	}

	public function getAjax() {
		$this->anfragenAuswerten();
		return array();
	}

	/**
	 * Reagiert auf POST-Anfragen, die zu dieser Formular-ID passen.
	 */
	protected function anfragenAuswerten() {
		// Ein Post-Request von diesem Modal ist zu bearbeiten

		// Sammlung aller Fehler, die beim Formular aufgetreten sind:
		$ausgabe = array(
			// Meldungen pro Feld:
			'felder' => array(),

			// Allgemeine Fehlermeldungen:
			'fehler' => array(),

			// Allgemeine Erfolgsmeldungen:
			'erfolg' => array()
		);

		try {
			wire('session')->CSRF->validate($this->formularName);

			$neueAnfrage = new Page();
			$neueAnfrage->template = $this->template;
			$fehlerFlag = false;

			foreach ($this->felder as $feldParams) {
				$feld = $this->template->fieldgroup->getField($feldParams->name, true);

				$inputField = $feld->getInputfield($neueAnfrage);
				$inputField->processInput(wire('input')->post);

				$ausgabe['felder'][$feld->name] = array(
					'name' => $feld->name,
					'label' => $feld->label,
					'aktuellerWert' => $inputField->attr('value'),
					'fehler' => array(),
					'erfolg' => array()
				);

				// Das Feld hat keinen Inhalt, ist aber required:
				if (!!$feld->required && empty($feld->requiredIf) && $inputField->isEmpty()) {
					$fehlerFlag = true;
					$ausgabe['felder'][$feld->name]['fehler'][] = 'Dieses Feld ist ein Pflichtfeld.';
				}

				// Fehlermeldungen vom Inputfield, werden pro Feldname gesammelt:
				if ($inputField->getErrors()) {
					$fehlerFlag = true;
					foreach ($inputField->getErrors(true) as $error) {
						$ausgabe['felder'][$feld->name]['fehler'][] = $error;
					}
				}

				$neueAnfrage->{$feld->name} = $inputField->attr('value');
			}

			// Required-If-Angaben prüfen (geht erst, wenn alle POST-Informationen in die Page übertragen wurden):
			foreach ($this->felder as $feldParams) {
				$feld = $this->template->fieldgroup->getField($feldParams->name, true);

				$inputField = $feld->getInputfield($neueAnfrage);
				$inputField->processInput(wire('input')->post);

				if (!isset($ausgabe['felder'][$feld->name]) || !is_array($ausgabe['felder'][$feld->name])) {
					$ausgabe['felder'][$feld->name] = array(
						'name' => $feld->name,
						'label' => $feld->label,
						'fehler' => array(),
						'erfolg' => array()
					);
				}

				if (!!$feld->required && !empty($feld->requiredIf) && $neueAnfrage->matches($feld->requiredIf) && $inputField->isEmpty()) {
					$fehlerFlag = true;
					$ausgabe['felder'][$feld->name]['fehler'][] = 'Dieses Feld ist ein Pflichtfeld.';
				}
			}

			if ($fehlerFlag) {
				throw new FormularException('Ein oder mehrere Felder weisen Fehler auf.');
				// throw new FormularException('Ein oder mehrere Felder weisen Fehler auf: "' . implode(', ', $ausgabe['felder']) .'"');
			}

			$neueAnfrage->parent = $this->containerSeite;
			$name = "";
			if ($neueAnfrage->vorname) {
				$name .= $neueAnfrage->vorname;
			}
			if ($neueAnfrage->nachname) {
				if (!empty($name)) {
					$name .= " ";
				}
				$name .= $neueAnfrage->nachname;
			}
			if (empty($neueAnfrage->betreff)) {
				$neueAnfrage->betreff = 'Anfrage';
			}
			$neueAnfrage->title = date('d.m.Y') . ": " . $neueAnfrage->betreff . " (von " . $name . ')';

			// Angefragte Seite hinterlegen:
			$neueAnfrage->seite = $this->page;

			if (!$neueAnfrage->save()) {
				$ausgabe['processwire_fehler'] = array(
					'text' => 'Die Anfrage konnte nicht gespeichert werden.',
					'felder' => array()
				);
				throw new FormularException('Die Anfrage konnte nicht gespeichert werden.');
			}
		} catch (WireCSRFException $e) {
			$ausgabe['fehler']['csrf_fehler'] = 'Diese Anfrage war anscheinend gefälscht und wurde daher abgebrochen.';
			$ausgabe['status'] = false;
			Twack::sendResponse($ausgabe, 403);
			return false;
		} catch (FormularException $e) {
			$ausgabe['fehler']['formularfehler'] = $e->getMessage();
			$ausgabe['status'] = false;
			Twack::sendResponse($ausgabe, 400);
			return false;
		} catch (\Exception $e) {
			$ausgabe['fehler']['formularfehler'] = $e->getMessage();
			$ausgabe['status'] = false;
			Twack::sendResponse($ausgabe, 400);
			return false;
		}

		try {
			$this->benachrichtigungVerschicken($neueAnfrage);
		} catch (\Exception $e) {
			wire('log')->save('formular', 'Ein Fehler ist beim Verschicken der Benachrichtigung aufgetreten: '.$e->getMessage());
		}

		wire('session')->CSRF->resetToken($this->formularName);
		unset($_POST);

		$ausgabe['status'] = true;
		$ausgabe['erfolg']['erfolgreich'] = 'Ihre Anfrage wurde erfolgreich verarbeitet.';
		Twack::sendResponse($ausgabe, 200);
	}

	protected function benachrichtigungVerschicken(Page $erstellteSeite) {
		if ($this->containerSeite->template->hasField('email_benachrichtigung')) {
			foreach ($this->containerSeite->email_benachrichtigung as $benachrichtigung) {
				$emailParams = $this->emailParams;

				if ($erstellteSeite->id) {
					// Platzhalter-Werte erstellen auf Basis der Seite:
					// {{url}}, {{edit_url}}, {{title}}, {{feldinhalte}}
					$this->emailParams['platzhalter']['plain']['url'] = $erstellteSeite->httpUrl;
					$this->emailParams['platzhalter']['html']['url'] = "<a href='{$erstellteSeite->httpUrl}'>{$erstellteSeite->httpUrl}</a>";
					$this->emailParams['platzhalter']['plain']['edit_url'] = $this->getEditUrl($erstellteSeite);
					$this->emailParams['platzhalter']['html']['edit_url'] = "<a href='{$this->getEditUrl($erstellteSeite)}'>{$this->getEditUrl($erstellteSeite)}</a>";

					$this->emailParams['platzhalter']['title'] = $erstellteSeite->title;

					// Feldinhalte ablegen:
					$this->emailParams['platzhalter']['plain']['feldinhalte'] = '';
					$this->emailParams['platzhalter']['html']['feldinhalte'] = '';

					$this->emailParams['platzhalter']['html']['feldinhalte'] .= "<p><table cellspacing=\"0\" cellpadding=\"5\" border=\"0\" width=\"700\">";
					$einruecken = false;
					foreach ($erstellteSeite->template->fields as $feld) {
						$feld = $erstellteSeite->template->fieldgroup->getField($feld->name, true); // Fieldgroup-Settings mit einbeziehen
						if ($feld->hasFlag(Field::flagSystem)) {
							continue; // Keine System-Felder ausspielen
						}
						if (empty($this->getFeldHTML($feld, $erstellteSeite))) {
							continue;
						}

						$label = trim($feld->label);
						if (!empty($label) && substr($label, -1) !== ':') {
							$label .= ':';
						}

						if ($feld->type instanceof FieldtypeFieldsetClose) {
							$einruecken = false;
							$this->emailParams['platzhalter']['html']['feldinhalte'] .= "<tr><td> </td><td> </td></tr>";
							// $this->emailParams['platzhalter']['html']['feldinhalte'] .= "<tr><td> </td></tr>";
							// $this->emailParams['platzhalter']['plain']['feldinhalte'] .= "\n";
							continue;
						} elseif ($feld->type instanceof FieldtypeFieldsetOpen) {
							$einruecken = true;
							if (empty($label)) {
								continue;
							}
							$this->emailParams['platzhalter']['html']['feldinhalte'] .= "<tr><td> </td></tr>";
							$this->emailParams['platzhalter']['html']['feldinhalte'] .= "<tr><td><strong>{$label}</strong></td><td></td></tr>";
							$this->emailParams['platzhalter']['plain']['feldinhalte'] .= "\r\n\r\n" .$label . ' ';
							continue;
						} elseif ($feld->type instanceof \FieldtypeRuntimeMarkup) {
							continue;
						}

						$this->emailParams['platzhalter']['html']['feldinhalte'] .= "<tr>";
						$this->emailParams['platzhalter']['plain']['feldinhalte'] .= "\n";

						$this->emailParams['platzhalter']['html']['feldinhalte'] .= '<td width=\"190\">';
						if ($einruecken) {
							$this->emailParams['platzhalter']['html']['feldinhalte'] .= "&nbsp; &nbsp; ";
							$this->emailParams['platzhalter']['plain']['feldinhalte'] .= "    ";
						}
						$this->emailParams['platzhalter']['html']['feldinhalte'] .= '<strong>'.$label.' </strong></td>';
						$this->emailParams['platzhalter']['plain']['feldinhalte'] .= $label . ' ';

						$wert = (string) $erstellteSeite->get($feld->name);
						if ($feld->type instanceof FieldtypeCheckbox) {
							if ($wert) {
								$wert = "Ja";
							} else {
								$wert = "Nein";
							}
						} elseif ($feld->type instanceof FieldtypeOptions) {
							$wert = '';
							foreach ($erstellteSeite->get($feld->name) as $option) {
								if (!empty($wert)) {
									$wert .= ', ';
								}
								$wert .= $option->title;
							}
						}

						$htmlWert = $wert;
						if (empty($wert)) {
							$wert = '[keine Angabe]';
							$htmlWert = '<i>[keine Angabe]</i>';
						}

						$this->emailParams['platzhalter']['html']['feldinhalte'] .= "<td width=\"390\">{$htmlWert}</td>";
						$this->emailParams['platzhalter']['plain']['feldinhalte'] .= $wert;

						$this->emailParams['platzhalter']['html']['feldinhalte'] .= "</tr>";

						if ($feld->name !== 'feldinhalte' && !isset($this->emailParams['platzhalter']['plain'][$feld->name])) {
							// Feldwert als Platzhalter hinzufügen (Key = Feldname):
							$this->emailParams['platzhalter']['html'][$feld->name] = $wert;
							$this->emailParams['platzhalter']['plain'][$feld->name] = $wert;
						}
					}

					$this->emailParams['platzhalter']['html']['feldinhalte'] .= "</table></p>";
					$this->emailParams['platzhalter']['plain']['feldinhalte'] .= "\n";
				}

				// Betreff:
				if (!empty($benachrichtigung->kurztext)) {
					$emailParams['betreff'] = $this->platzhalterErsetzen($benachrichtigung->get('kurztext'), true);
				}

				// Inhaltstext:
				if (!empty($benachrichtigung->freitext)) {
					$emailParams['html']['inhalt'] = $this->platzhalterErsetzen($benachrichtigung->get('freitext'));
					$emailParams['plain']['inhalt'] = $this->platzhalterErsetzen($this->htmlToPlain($benachrichtigung->get('freitext')), true);
				}

				// Signatur:
				if (!empty($benachrichtigung->signatur)) {
					$emailParams['html']['signatur'] = $this->platzhalterErsetzen($benachrichtigung->get('signatur'));
					$emailParams['plain']['signatur'] = $this->platzhalterErsetzen($this->htmlToPlain($benachrichtigung->get('signatur')), true);
				}

				// Email-Empfänger auslesen:
				if (!empty($benachrichtigung->email_empfaenger)) {
					$emailParams['empfaenger'] = $this->getEmailEmpfaenger($benachrichtigung->get('email_empfaenger'));
				}
				if (!empty($benachrichtigung->email_empfaenger_cc)) {
					$emailParams['empfaengerCC'] = $this->getEmailEmpfaenger($benachrichtigung->get('email_empfaenger_cc'));
				}
				if (!empty($benachrichtigung->email_empfaenger_bcc)) {
					$emailParams['empfaengerBCC'] = $this->getEmailEmpfaenger($benachrichtigung->get('email_empfaenger_bcc'));
				}

				// Sind die erforderlichen Werte vorhanden?
				if (empty($emailParams['betreff'])) {
					continue;
				}
				if (empty($emailParams['html']['inhalt']) && empty($emailParams['plain']['inhalt'])) {
					continue;
				}
				if (empty($emailParams['empfaenger'])) {
					continue;
				}

				// Mail erstellen und verschicken:
				$email = wireMail();
				$email->header('X-Mailer', wire('pages')->get(1)->httpUrl.'');

				$email->to($emailParams['empfaenger']);
				if (!empty($emailParams['empfaengerCC'])) {
					$email->header('cc', $emailParams['empfaengerCC']);
				}
				if (!empty($emailParams['empfaengerBCC'])) {
					$email->header('bcc', $emailParams['empfaengerBCC']);
				}

				$email->subject($emailParams['betreff']);

				if (!empty($emailParams['plain']['inhalt'])) {
					if (!empty($emailParams['plain']['signatur'])) {
						$emailParams['plain']['inhalt'] .= "\r\n\r\n\r\n -- " . $emailParams['plain']['signatur'];
					}
					$email->body($emailParams['plain']['inhalt']);
				}
				if (!empty($emailParams['html']['inhalt'])) {
					if (!empty($emailParams['html']['signatur'])) {
						$emailParams['html']['inhalt'] .= "\r\n\r\n\r\n -- " . $emailParams['html']['signatur'];
					}
					$email->bodyHTML($emailParams['html']['inhalt']);
				}

				$email->send();
			}
		}
	}

	protected function getEditUrl($seite) {
		$protocol = wire('config')->https ? 'https' : 'http';
		$pageEditUrl = strpos($seite->editUrl, $protocol) === 0 ? $seite->editUrl : $protocol . '://'.wire('config')->httpHost.$seite->editUrl;
		return $pageEditUrl;
	}

	/**
	 * Wandelt einen HTML-String in einen String für eine Plain-Mail um
	 * @param  string $text
	 * @return string
	 */
	protected function htmlToPlain($text) {
		$text = str_replace(['</p>', '<br/>', '</tr>', '</table>'], "\r\n", $text);
		$text = str_replace('</td>', "\t", $text);
		return strip_tags($text);
	}

	/**
	 * Ersetzt die Platzhalter in einem Text.
	 *
	 * @param  string $text
	 * @return string
	 */
	protected function platzhalterErsetzen($text, $plain = false) {
		if (!$plain) {
			$text = preg_replace_callback('/\{\{(\w+)}}/', function ($treffer) {
				if (isset($this->emailParams['platzhalter']['html']) && isset($this->emailParams['platzhalter']['html'][$treffer[1]])) {
					return $this->emailParams['platzhalter']['html'][$treffer[1]];
				} elseif (isset($this->emailParams['platzhalter'][$treffer[1]])) {
					return $this->emailParams['platzhalter'][$treffer[1]];
				}
			}, $text);
		} else {
			$text = preg_replace_callback('/\{\{(\w+)}}/', function ($treffer) {
				if (isset($this->emailParams['platzhalter']['plain']) && isset($this->emailParams['platzhalter']['plain'][$treffer[1]])) {
					return $this->emailParams['platzhalter']['plain'][$treffer[1]];
				} elseif (isset($this->emailParams['platzhalter'][$treffer[1]])) {
					return $this->emailParams['platzhalter'][$treffer[1]];
				}
			}, $text);
		}

		return $text;
	}

	protected function getEmailEmpfaenger($empfaengerFeld) {
		$empfaengerListe = array();
		foreach ($empfaengerFeld as $empfaenger) {
			if ($empfaenger->type == 'nutzer') {
				// Nutzer
				if (!empty($empfaenger->nutzer)) {
					foreach ($empfaenger->nutzer as $nutzer) {
						if (!empty($nutzer->kontakt_email)) {
							$empfaengerListe[] = $nutzer->kontakt_email;
						} else {
							$empfaengerListe[] = $nutzer->email;
						}
					}
				}
			} elseif ($empfaenger->type == 'text') {
				// Textfelder
				if (!empty($empfaenger->email)) {
					$empfaengerListe[] = $empfaenger->email;
				}
			} elseif ($empfaenger->type == 'variable') {
				// Variablenwert
				if (!empty($empfaenger->kurztext)) {
					$empfaengerListe[] = $this->platzhalterErsetzen($empfaenger->kurztext, true);
				}
			}
		}
		return implode(', ', $empfaengerListe);
	}

	protected function setTemplate(Template $template) {
		$this->felder = new WireArray();
		$this->template = $template;

		if (empty($this->formularName)) {
			$this->formularName = $this->template->name;
		}

		$neueAnfrage = new Page();
		$neueAnfrage->template = $this->template;

		foreach ($template->fields as $feld) {
			try {
				// Keine System-Felder ausgeben:
				if ($feld->hasFlag(Field::flagSystem)) {
					continue;
				}

				$feld = $template->fieldgroup->getField($feld->name, true); // Fieldgroup-Settings mit einbeziehen

				// Keine Felder ausgeben, die keine HTML-Ausgabe haben:
				if (empty($this->getFeldHTML($feld, $neueAnfrage))) {
					continue;
				}

				$feld->inputHtml = $this->getFeldHTML($feld, $neueAnfrage);
				$this->felder->add($feld);
			} catch (\Exception $e) {
				// Twack::devEcho($e->getMessage());
			}
		}
	}

	/**
	 * Liefert zu einem speziellen Feld ein Input-Feld als HTML-String.
	 * @param  Field  $feld
	 * @param  Page   $seite
	 * @return string
	 */
	protected function getFeldHTML(Field $feld, Page $seite) {
		return $this->getComponent('formularAusgabe')->getFeldHtml($feld, $seite);
	}
}
