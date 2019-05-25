<?php
namespace ProcessWire;

class BootstrapFormularAusgabe extends FormularAusgabeTyp{

	/**
	 * Liefert den HTML-String für die Ausgabe eines einzelnen Feldes
	 * @param  Field  $feld
	 * @param  Page   $seite
	 * @return string
	 */
	public function getFeldHTML(Field $feld, Page $seite) {

		$feldHTML = '';

		$isRequired = (!!$feld->required && empty($feld->requiredIf));

		$attribute = array();
		$attribute["name"] = $feld->name;
		$attribute['id'] = $this->idService->getID($feld->name);
		if ($isRequired) {
			$attribute['required'] = 'required';
		}
		if ($feld->rows) {
			$attribute['rows'] = $feld->rows;
		}

		$inputKlassen = array();
		$gruppenKlassen = array();
		$icon = $feld->getInputfield($seite)->icon;
		if ($icon) {
			$gruppenKlassen[] = 'icon-' . Twack::camelCaseToUnderscore($icon);
		}

		$gruppenAttribute = array();
		$gruppenKlassen[] = 'col-12';
		if ($feld->columnWidth !== null) {
			if ($feld->columnWidth == 66) {
				$gruppenKlassen[] = 'col-sm-8';
			} elseif ($feld->columnWidth == 50) {
				$gruppenKlassen[] = 'col-sm-6';
			} elseif ($feld->columnWidth == 33 || $feld->columnWidth == 34) {
				$gruppenKlassen[] = 'col-sm-4';
			} elseif ($feld->columnWidth == 25) {
				$gruppenKlassen[] = 'col-sm-3';
			}
			// $gruppenAttribute["style"] = 'width: '.$feld->columnWidth.'%;';
			// $gruppenKlassen[] = 'formular-feld-smaller';
		}

		// Twack::devEcho($feld->type);

		if ($feld->type instanceof \FieldtypeRuntimeMarkup) {
			$feldHTML .= '<div class="form-group '.($isRequired ? 'required' : '').' ' . implode(' ', $gruppenKlassen) . '" ' . $this->getAttributeString($gruppenAttribute) . '>';
			$feldHTML .= '<label for="' . $attribute['id'] . '" class="form-control-label">' . $feld->label . '</label>';

			if (!empty($feld->description.'')) {
				$feldHTML .= '<div class="form-text text-muted">' . $this->ersetzePlatzhalter($feld->description) . '</div>';
			}

			$feldHTML .= '<div class="form-text">' . $this->ersetzePlatzhalter($seite->get($feld->name)) . '</div>';

			if (!empty(''.$feld->notes)) {
				$feldHTML .= '<div class="form-text">' . $this->ersetzePlatzhalter($feld->notes) . '</div>';
			}
			$feldHTML .= '</div>';
		} elseif ($feld->type instanceof FieldtypeText || $feld->type instanceof FieldtypeFloat || $feld->type instanceof FieldtypeInteger) {
			$feldHTML .= '<div class="form-group '.($isRequired ? 'required' : '').' ' . implode(' ', $gruppenKlassen) . '" ' . $this->getAttributeString($gruppenAttribute) . '>';
			$feldHTML .= '<label for="' . $attribute['id'] . '" class="form-control-label">' . $feld->label . '</label>';

			if (!empty($feld->description.'')) {
				$feldHTML .= '<div class="form-text text-muted">' . $this->ersetzePlatzhalter($feld->description) . '</div>';
			}

			if ($feld->size && is_integer($feld->size)) {
				$attribute['size'] = $feld->size;
			}

			if ($feld->minlength && is_integer($feld->minlength)) {
				$attribute['minlength'] = $feld->minlength;
			}

			if ($feld->maxlength && is_integer($feld->maxlength)) {
				$attribute['maxlength'] = $feld->maxlength;
			}

			$feldHTML .= '<div class="input-group">';
			if ($feld->type instanceof FieldtypeTextarea) {
				$feldHTML .= '<textarea ' . $this->getAttributeString($attribute) . ' class="form-control ' . implode(' ', $inputKlassen) . '"></textarea>';
			} elseif ($feld->type instanceof FieldtypeEmail) {
				$feldHTML .= '<input ' . $this->getAttributeString($attribute) . ' type="email" class="form-control ' . implode(' ', $inputKlassen) . '" />';
			} elseif ($feld->inputType == 'number') {
				if ($feld->min && is_integer($feld->min)) {
					$attribute['min'] = $feld->min;
					$attribute['placeholder'] = 'mind. ' . $feld->min;
				}
				if ($feld->max && is_integer($feld->max)) {
					$attribute['max'] = $feld->max;
				}

				$feldHTML .= '<input ' . $this->getAttributeString($attribute) . ' type="number" class="form-control ' . implode(' ', $inputKlassen) . '" />';
			} else {
				$feldHTML .= '<input ' . $this->getAttributeString($attribute) . ' type="text" class="form-control ' . implode(' ', $inputKlassen) . '" />';
			}
			$feldHTML .= '<span class="input-group-addon icon-platzhalter"></span>';
			$feldHTML .= '</div>';
			if (!empty(''.$feld->notes)) {
				$feldHTML .= '<div class="form-text">' . $this->ersetzePlatzhalter($feld->notes) . '</div>';
			}
			$feldHTML .= '</div>';
		} elseif ($feld->type instanceof FieldtypeCheckbox) {
			$feldHTML .= '<div class="form-check ' . implode(' ', $gruppenKlassen) . '">';

			$feldHTML .= '<label class="form-check-label" for="'.$attribute['id'].'">';
			$feldHTML .= '<input class="form-check-input ' . implode(' ', $inputKlassen) . '" type="checkbox" ' . $this->getAttributeString($attribute) . ' />';
			$feldHTML .= '<div class="control__indicator"></div>';
			$feldHTML .= '<span class="label">'.$feld->label.'</span>';
			$feldHTML .= '</label>';

			if (!empty('' . $feld->description)) {
				$feldHTML .= '<div class="form-text text-muted">' . $this->ersetzePlatzhalter($feld->description) . '</div>';
			}
			if (!empty(''.$feld->notes)) {
				$feldHTML .= '<div class="form-text">' . $this->ersetzePlatzhalter($feld->notes) . '</div>';
			}
			$feldHTML .= '</div>';
		} elseif ($feld->type instanceof FieldtypeOptions) {
			$feldHTML .= '<div class="form-group '.($isRequired ? 'required' : '').' ' . implode(' ', $gruppenKlassen) . '">';
			$feldHTML .= '<label>' . $feld->label . '<span class="icon-platzhalter"> </span></label>';
			if (!empty('' . $feld->description)) {
				$feldHTML .= '<div class="form-text text-muted">' . $this->ersetzePlatzhalter($feld->description) . '</div>';
			}

			if ($feld->getInputfield($seite) instanceof InputfieldSelectMultiple) {
				$attribute['name'] .= '[]';
			}

			foreach ($feld->type->getOptions($feld) as $option) {
				$id = $this->idService->getID($attribute['id'].'-'.$option->id);
				$attribute['id'] = $id;
				if ($feld->getInputfield($seite) instanceof InputfieldSelectMultiple) {
					// Checkbox-Auswahl
					$feldHTML .= '<div class="form-check">';
					$feldHTML .= '<label class="form-check-label" for="' . $id . '">';
					$feldHTML .= '<input class="form-check-input ' . implode(' ', $inputKlassen) . '" type="checkbox" ' . $this->getAttributeString($attribute) . ' value="' . $option->id . '" /> ';
					$feldHTML .= '<div class="control__indicator"></div>';
					$feldHTML .= '<span class="label">'.$option->title.'</span>';
					$feldHTML .= '</label>';
					$feldHTML .= '</div>';
				} else {
					// Radio-Auswahl
					$feldHTML .= '<div class="form-check">';
					$feldHTML .= '<label class="form-check-label" for="' . $id . '">';
					$feldHTML .= '<input class="form-check-input ' . implode(' ', $inputKlassen) . '" type="radio" ' . $this->getAttributeString($attribute) . ' value="' . $option->id . '" /> ';
					$feldHTML .= '<span class="titel">'.$option->title.'</span>';
					$feldHTML .= '</label>';
					$feldHTML .= '</div>';
				}
			}

			if (!empty('' . $feld->notes)) {
				$feldHTML .= '<div class="form-text">' . $this->ersetzePlatzhalter($feld->notes) . '</div>';
			}

			$feldHTML .= '</div>';
		} elseif ($feld->type instanceof FieldtypeFieldsetClose) {
		} elseif ($feld->type instanceof FieldtypeFieldsetOpen) {
			$feldHTML .= "<div class='col-12'>";
			if (!empty(''.$feld->description)) {
				$feldHTML .= '<div class="form-text text-muted">'.$this->ersetzePlatzhalter($feld->description).'</div>';
			}
			if (!empty(''.$feld->notes)) {
				$feldHTML .= '<div class="form-text">' . $this->ersetzePlatzhalter($feld->notes) . '</div>';
			}
			$feldHTML .= "</div>";
		}

		return $feldHTML;
	}
}
