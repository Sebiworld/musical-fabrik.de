<?php

namespace ProcessWire;

class FormOutputBootstrap extends FormOutputType {
    /**
     * Returns the HTML string for the output of a single template-field.
     * @param  Field  $feld
     * @param  Page   $seite
     * @return string
     */
    public function getFieldHtml(Field $field, Page $page, $evaluationResponse = []) {
        $fieldHtml = '';

        $isRequired = (!!$field->required && empty($field->requiredIf));

        $attributes         = array();
        $attributes['name'] = $field->name;
        $attributes['id']   = $this->idService->getID($field->name);
        if ($isRequired) {
            $attributes['required'] = 'required';
        }
        if ($field->rows) {
            $attributes['rows'] = $field->rows;
        }

        $inputClasses = array();
        $groupClasses = array();
        $icon         = $field->getInputfield($page)->icon;
        if ($icon) {
            $groupClasses[] = 'icon-' . Twack::camelCaseToUnderscore($icon);
        }

        $groupAttributes = array();
        $groupClasses[]  = 'col-12';
        if ($field->columnWidth !== null) {
            if ($field->columnWidth == 66) {
                $groupClasses[] = 'col-sm-8';
            } elseif ($field->columnWidth == 50) {
                $groupClasses[] = 'col-sm-6';
            } elseif ($field->columnWidth == 33 || $field->columnWidth == 34) {
                $groupClasses[] = 'col-sm-4';
            } elseif ($field->columnWidth == 25) {
                $groupClasses[] = 'col-sm-3';
            }
            // $groupAttributes["style"] = 'width: '.$field->columnWidth.'%;';
            // $groupClasses[] = 'formular-feld-smaller';
        }

        $errormsg     = [];
        $successmsg   = [];
        $currentValue = null;
        if (!empty($evaluationResponse['fields'][$field->name]) && is_array($evaluationResponse['fields'][$field->name])) {
            if (isset($evaluationResponse['fields'][$field->name]['currentValue'])) {
                $currentValue = $evaluationResponse['fields'][$field->name]['currentValue'];
            }

            if (!empty($evaluationResponse['fields'][$field->name]['error']) && is_array($evaluationResponse['fields'][$field->name]['error'])) {
                $inputClasses[] = 'is-invalid';
                $errormsg       = $evaluationResponse['fields'][$field->name]['error'];
            } elseif (!empty($evaluationResponse['fields'][$field->name]['success']) && is_array($evaluationResponse['fields'][$field->name]['success'])) {
                $inputClasses[] = 'is-valid';
                $successmsg     = $evaluationResponse['fields'][$field->name]['success'];
            }
        }

        if ($field->name === 'antispam_code') {
            $isRequired             = true;
            $attributes['required'] = 'required';
            $fieldHtml .= '<div class="form-group antispam-code ' . ($isRequired ? 'required' : '') . ' ' . implode(' ', $groupClasses) . '" ' . $this->getAttributeString($groupAttributes) . '>';
            $fieldHtml .= '<label for="' . $attributes['id'] . '" class="form-control-label">' . $field->label . '</label>';

            if (!empty($field->description . '')) {
                $fieldHtml .= '<div class="form-text text-muted">' . $this->replacePlaceholders($field->description) . '</div>';
            }

            if ($field->size && is_integer($field->size)) {
                $attributes['size'] = $field->size;
            }

            if ($field->minlength && is_integer($field->minlength)) {
                $attributes['minlength'] = $field->minlength;
            }

            if ($field->maxlength && is_integer($field->maxlength)) {
                $attributes['maxlength'] = $field->maxlength;
            }
            
            $fieldHtml .= '<div class="input-group">';
            $fieldHtml .= '<div class="code">' . $this->replacePlaceholders($page->get($field->name)) . '</div>';

            $fieldHtml .= '<input ' . $this->getAttributeString($attributes) . ' type="text" class="form-control ' . implode(' ', $inputClasses) . '" />';

            $fieldHtml .= '<span class="input-group-addon icon-placeholder"></span>';
            if (!empty($errormsg)) {
                $fieldHtml .= '<div class="invalid-feedback">' . implode(', ', $errormsg) . '</div>';
            } elseif (!empty($successmsg)) {
                $fieldHtml .= '<div class="valid-feedback">' . implode(', ', $successmsg) . '</div>';
            }

            $fieldHtml .= '</div>';
            if (!empty('' . $field->notes)) {
                $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($field->notes) . '</div>';
            }
            $fieldHtml .= '</div>';
        } elseif ($field->type instanceof \FieldtypeRuntimeMarkup) {
            $fieldHtml .= '<div class="form-group ' . ($isRequired ? 'required' : '') . ' ' . implode(' ', $groupClasses) . '" ' . $this->getAttributeString($groupAttributes) . '>';
            $fieldHtml .= '<label class="form-control-label">' . $field->label . '</label>';

            if (!empty($field->description . '')) {
                $fieldHtml .= '<div class="form-text text-muted">' . $this->replacePlaceholders($field->description) . '</div>';
            }

            $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($page->get($field->name)) . '</div>';

            if (!empty('' . $field->notes)) {
                $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($field->notes) . '</div>';
            }
            $fieldHtml .= '</div>';
        } elseif ($field->type instanceof FieldtypeText || $field->type instanceof FieldtypeFloat || $field->type instanceof FieldtypeInteger) {
            $fieldHtml .= '<div class="form-group ' . ($isRequired ? 'required' : '') . ' ' . implode(' ', $groupClasses) . '" ' . $this->getAttributeString($groupAttributes) . '>';
            $fieldHtml .= '<label for="' . $attributes['id'] . '" class="form-control-label">' . $field->label . '</label>';

            if (!empty($field->description . '')) {
                $fieldHtml .= '<div class="form-text text-muted">' . $this->replacePlaceholders($field->description) . '</div>';
            }

            if ($field->size && is_integer($field->size)) {
                $attributes['size'] = $field->size;
            }

            if ($field->minlength && is_integer($field->minlength)) {
                $attributes['minlength'] = $field->minlength;
            }

            if ($field->maxlength && is_integer($field->maxlength)) {
                $attributes['maxlength'] = $field->maxlength;
            }

            if (!empty($currentValue)) {
                $attributes['value'] = $currentValue;
            }

            $fieldHtml .= '<div class="input-group">';
            if ($field->type instanceof FieldtypeTextarea) {
                $fieldHtml .= '<textarea ' . $this->getAttributeString($attributes) . ' class="form-control ' . implode(' ', $inputClasses) . '"></textarea>';
            } elseif ($field->type instanceof FieldtypeEmail) {
                $fieldHtml .= '<input ' . $this->getAttributeString($attributes) . ' type="email" class="form-control ' . implode(' ', $inputClasses) . '" />';
            } elseif ($field->inputType == 'number') {
                if ($field->min && is_integer($field->min)) {
                    $attributes['min']         = $field->min;
                    $attributes['placeholder'] = 'mind. ' . $field->min;
                }
                if ($field->max && is_integer($field->max)) {
                    $attributes['max'] = $field->max;
                }

                $fieldHtml .= '<input ' . $this->getAttributeString($attributes) . ' type="number" class="form-control ' . implode(' ', $inputClasses) . '" />';
            } else {
                $fieldHtml .= '<input ' . $this->getAttributeString($attributes) . ' type="text" class="form-control ' . implode(' ', $inputClasses) . '" />';
            }
            $fieldHtml .= '<span class="input-group-addon icon-placeholder"></span>';
            if (!empty($errormsg)) {
                $fieldHtml .= '<div class="invalid-feedback">' . implode(', ', $errormsg) . '</div>';
            } elseif (!empty($successmsg)) {
                $fieldHtml .= '<div class="valid-feedback">' . implode(', ', $successmsg) . '</div>';
            }

            $fieldHtml .= '</div>';
            if (!empty('' . $field->notes)) {
                $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($field->notes) . '</div>';
            }
            $fieldHtml .= '</div>';
        } elseif ($field->type instanceof FieldtypeCheckbox) {
            if (!empty($currentValue) && $currentValue) {
                $attributes['checked'] = 'checked';
            }

            $fieldHtml .= '<div class="form-check ' . implode(' ', $groupClasses) . '">';

            $fieldHtml .= '<label class="form-check-label" for="' . $attributes['id'] . '">';
            $fieldHtml .= '<input class="form-check-input ' . implode(' ', $inputClasses) . '" type="checkbox" ' . $this->getAttributeString($attributes) . ' />';
            $fieldHtml .= '<span class="control__indicator"></span>';
            $fieldHtml .= '<span class="label">' . $field->label . '</span>';
            if (!empty($errormsg)) {
                $fieldHtml .= '<div class="invalid-feedback">' . implode(', ', $errormsg) . '</div>';
            } elseif (!empty($successmsg)) {
                $fieldHtml .= '<div class="valid-feedback">' . implode(', ', $successmsg) . '</div>';
            }
            $fieldHtml .= '</label>';

            if (!empty('' . $field->description)) {
                $fieldHtml .= '<div class="form-text text-muted">' . $this->replacePlaceholders($field->description) . '</div>';
            }
            if (!empty('' . $field->notes)) {
                $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($field->notes) . '</div>';
            }
            $fieldHtml .= '</div>';
        } elseif ($field->type instanceof FieldtypeOptions) {
            $fieldHtml .= '<div class="form-group ' . ($isRequired ? 'required' : '') . ' ' . implode(' ', $groupClasses) . '">';
            $fieldHtml .= '<label>' . $field->label . '<span class="icon-placeholder"> </span></label>';
            if (!empty('' . $field->description)) {
                $fieldHtml .= '<div class="form-text text-muted">' . $this->replacePlaceholders($field->description) . '</div>';
            }

            if ($field->getInputfield($page) instanceof InputfieldSelectMultiple) {
                $attributes['name'] .= '[]';
            }

            foreach ($field->type->getOptions($field) as $option) {
                $id               = $this->idService->getID($attributes['id'] . '-' . $option->id);

                if (!empty($currentValue) && is_array($currentValue) && in_array($option->id, $currentValue)) {
                    $attributes['checked'] = 'checked';
                } elseif (isset($attributes['checked'])) {
                    unset($attributes['checked']);
                }

                $attributes['id'] = $id;
                if ($field->getInputfield($page) instanceof InputfieldSelectMultiple) {
                    // Checkbox-Auswahl
                    $fieldHtml .= '<div class="form-check">';
                    $fieldHtml .= '<label class="form-check-label" for="' . $id . '">';
                    $fieldHtml .= '<input class="form-check-input ' . implode(' ', $inputClasses) . '" type="checkbox" ' . $this->getAttributeString($attributes) . ' value="' . $option->id . '" /> ';
                    $fieldHtml .= '<span class="control__indicator"></span>';
                    $fieldHtml .= '<span class="label">' . $option->title . '</span>';
                    $fieldHtml .= '</label>';
                    if (!empty($errormsg)) {
                        $fieldHtml .= '<div class="invalid-feedback">' . implode(', ', $errormsg) . '</div>';
                    } elseif (!empty($successmsg)) {
                        $fieldHtml .= '<div class="valid-feedback">' . implode(', ', $successmsg) . '</div>';
                    }
                    $fieldHtml .= '</div>';
                } else {
                    // Radio-Auswahl
                    $fieldHtml .= '<div class="form-check">';
                    $fieldHtml .= '<label class="form-check-label" for="' . $id . '">';
                    $fieldHtml .= '<input class="form-check-input ' . implode(' ', $inputClasses) . '" type="radio" ' . $this->getAttributeString($attributes) . ' value="' . $option->id . '" /> ';
                    $fieldHtml .= '<span class="title">' . $option->title . '</span>';
                    $fieldHtml .= '</label>';
                    if (!empty($errormsg)) {
                        $fieldHtml .= '<div class="invalid-feedback">' . implode(', ', $errormsg) . '</div>';
                    } elseif (!empty($successmsg)) {
                        $fieldHtml .= '<div class="valid-feedback">' . implode(', ', $successmsg) . '</div>';
                    }
                    $fieldHtml .= '</div>';
                }
            }

            if (!empty('' . $field->notes)) {
                $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($field->notes) . '</div>';
            }

            $fieldHtml .= '</div>';
        } elseif ($field->type instanceof FieldtypeFieldsetClose) {
        } elseif ($field->type instanceof FieldtypeFieldsetOpen) {
            $fieldHtml .= "<div class='col-12'>";
            if (!empty('' . $field->description)) {
                $fieldHtml .= '<div class="form-text text-muted">' . $this->replacePlaceholders($field->description) . '</div>';
            }
            if (!empty('' . $field->notes)) {
                $fieldHtml .= '<div class="form-text">' . $this->replacePlaceholders($field->notes) . '</div>';
            }
            $fieldHtml .= '</div>';
        }

        return $fieldHtml;
    }
}
