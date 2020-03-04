<?php

namespace ProcessWire;

class FormTemplate extends TwackComponent {
    protected $containerPage;
    protected $formTemplate;
    protected $placeholders;
    protected $idService;

    protected $emailParams;

    public function __construct($args) {
        parent::__construct($args);

        require_once __DIR__ . '/../form_exception.class.php';

        // Standardwerte für die Email:
        $this->emailParams = array(
            'subject' => $this->_('New request'),
            'plain'   => array(
                'content'   => '',
                'signature' => ''
            ),
            'html' => array(
                'content'   => '',
                'signature' => ''
            ),
            'recipient'     => '',
            'recipientCC'   => '',
            'recipientBCC'  => '',
            'placeholders'  => array(
                'html'  => array(),
                'plain' => array()
            )
        );

        $this->idService          = $this->getService('IdService');
        $this->evaluationResponse = [];

        // Determine Container Page. A container page must have the TemplateSelect field "form_template" in which it is specified which template is to be used as the form. The template must be permitted as a child template for the container page so that pages with the completed forms can be created below the container page.
        if (isset($args['containerPage']) && $args['containerPage'] instanceof Page && $args['containerPage']->id) {
            $this->containerPage = $args['containerPage'];
        } else {
            $this->containerPage = $this->page->get('form');
        }

        if (!($this->containerPage instanceof Page) || !$this->containerPage->id) {
            throw new ComponentNotInitializedException('FormTemplate', 'No valid container page was passed to the form.');
        }

        $this->formOrigin = $this->containerPage->id;
        // $this->formAction = $this->containerPage->url;

        // Fill placeholder collection. {{placeholders}}} will be replaced by the specified match.
        $this->placeholders = array();
        if (isset($args['placeholders']) && is_array($args['placeholders'])) {
            $this->placeholders = $args['placeholders'];
        }
        if ($this->containerPage->template->hasField('placeholder') && $this->containerPage->placeholder) {
            foreach ($this->containerPage->placeholder as $placeholder) {
                $this->placeholders["{$placeholder->short_text}"] = $placeholder->freetext;
            }
        }

        // Which template should be used for the form evaluation?
        if (isset($args['useTemplate'])) {
            if ($args['useTemplate'] instanceof Template) {
                $this->template = $args['useTemplate'];
            } else {
                $this->template = wire('templates')->get($args['useTemplate']);
            }
        } elseif ($this->containerPage->template->hasField('form_template') && !empty($this->containerPage->form_template)) {
            $this->template = $this->containerPage->form_template[0];
        }

        if (!($this->template instanceof Template) || !$this->template->id) {
            throw new ComponentNotInitializedException('FormTemplate', 'No form template was defined on the container page.');
        }

        if ($this->template->hasField('antispam_code')) {
            if (empty($this->getAntispamCode())) {
                $this->regenerateAntispamCode();
            }
            $this->placeholders['session_antispam_code'] = $this->getAntispamCode();
        }

        require_once __DIR__ . '/form_output_type.class.php';

        // Determine form output. Later other possibilities than bootstrap should be possible:
        $formOutputArgs = array(
            'name'         => 'formOutput',
            'placeholders' => $this->placeholders
        );
        if (isset($args['outputType']) && $args['outputType'] == 'bootstrap') {
            $this->addComponent('FormOutputBootstrap', $formOutputArgs);
        } else {
            $this->addComponent('FormOutputBootstrap', $formOutputArgs);
        }

        if ($this->getComponent('formOutput') instanceof TwackNullComponent) {
            throw new ComponentNotInitializedException('FormTemplate', 'No output class was found.');
        }

        // When the template is set, it is run through and it is evaluated which fields must be generated and validated:
        $this->setTemplate($this->template);

        if (wire('input')->post->int('form-origin') === $this->formOrigin) {
            $this->evaluationResponse = $this->evaluateRequest();
        }

        if (isset($args['formName']) && empty($args['formName'])) {
            $this->formName = $args['formName'];
        }

        // When the template is set, it is run through and it is evaluated which fields must be generated and validated:
        $this->setTemplate($this->template);

        // The form script makes the form Ajax-enabled. An evaluation without Ajax is not intended.
        $this->addScript('form-template.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
        $this->addScript('legacy/form-template.js', array(
            'path'     => wire('config')->urls->templates . 'assets/js/',
            'absolute' => true
        ));
    }

    protected function regenerateAntispamCode() {
        wire('session')->set('antispam_code_' . $this->formOrigin, mt_rand(1000, 9999));
    }

    protected function clearAntispamCode() {
        wire('session')->remove('antispam_code_' . $this->formOrigin);
    }

    protected function getAntispamCode() {
        return wire('session')->get('antispam_code_' . $this->formOrigin);
    }

    public function getAjax($ajaxArgs = []) {
        // $this->evaluateRequest();
        return array();
    }

    /**
     * Responds to POST requests that match this form ID.
     */
    protected function evaluateRequest() {
        // A post-request of this modal is to be processed

        // Collection of all errors that occurred in the form:
        $output = array(
            // Messages per field:
            'fields' => array(),

            // General error messages:
            'error' => array(),

            // General success messages:
            'success' => array()
        );

        try {
            // Check Honeypot:
            if (!empty(wire('input')->post->information)) {
                throw new FormException($this->_('Form could not be submitted.'));
            }

            // Throws Exception if csrf validation fails:
            wire('session')->CSRF->validate($this->formOrigin);

            $newRequest            = new Page();
            $newRequest->template  = $this->template;
            $errorFlag             = false;
            $values                = [];

            foreach ($this->fields as $fieldParams) {
                $field = $this->template->fieldgroup->getField($fieldParams->name, true);

                if ($field->name === 'antispam_code') {
                    $antispamCode = wire('input')->post->int('antispam_code');

                    $output['fields'][$field->name] = array(
                        'name'           => $field->name,
                        'label'          => $field->label,
                        'currentValue'   => '',
                        'error'          => array(),
                        'success'        => array()
                    );

                    if ($this->getAntispamCode() !== $antispamCode) {
                        $errorFlag                                 = true;
                        $output['fields'][$field->name]['error'][] = $this->_('Please fill in the code above.');
                        // $this->regenerateAntispamCode();
                    }
                    continue;
                }

                $inputField = $field->getInputfield($newRequest);
                $inputField->processInput(wire('input')->post);

                $output['fields'][$field->name] = array(
                    'name'           => $field->name,
                    'label'          => $field->label,
                    'currentValue'   => $inputField->attr('value'),
                    'error'          => array(),
                    'success'        => array()
                );
                $values[$field->name] = $inputField->attr('value');

                // The field has no content, but is required:
                if (!!$field->required && empty($field->requiredIf) && $inputField->isEmpty()) {
                    $errorFlag                                 = true;
                    $output['fields'][$field->name]['error'][] = $this->_('This field is mandatory.');
                }

                // Error messages from the input field are collected for each field name:
                if ($inputField->getErrors()) {
                    $errorFlag = true;
                    foreach ($inputField->getErrors(true) as $error) {
                        $output['fields'][$field->name]['error'][] = $error;
                    }
                }

                $newRequest->{$field->name} = $inputField->attr('value');
            }

            $messageIdent = md5(http_build_query($values));
            if (!empty(wire('session')->get($this->formOrigin)) && wire('session')->get($this->formOrigin) === $messageIdent) {
                throw new FormCriticalException($this->_('Form was already submitted.'));
            }

            // Check Required-If information (only possible when all POST information has been transferred to the page):
            foreach ($this->fields as $fieldParams) {
                $field = $this->template->fieldgroup->getField($fieldParams->name, true);

                if ($field->name === 'antispam_code') {
                    continue;
                }

                $inputField = $field->getInputfield($newRequest);
                $inputField->processInput(wire('input')->post);

                if (!isset($output['fields'][$field->name]) || !is_array($output['fields'][$field->name])) {
                    $output['fields'][$field->name] = array(
                        'name'    => $field->name,
                        'label'   => $field->label,
                        'error'   => array(),
                        'success' => array()
                    );
                }

                if (!!$field->required && !empty($field->requiredIf) && $newRequest->matches($field->requiredIf) && $inputField->isEmpty()) {
                    $errorFlag                                 = true;
                    $output['fields'][$field->name]['error'][] = $this->_('This field is mandatory.');
                }
            }

            if ($errorFlag) {
                throw new FormException($this->_('One or more fields have errors.'));
            }

            $newRequest->parent  = $this->containerPage;
            $name                = '';
            if ($newRequest->first_name) {
                $name .= $newRequest->first_name;
            }
            if ($newRequest->surname) {
                if (!empty($name)) {
                    $name .= ' ';
                }
                $name .= $newRequest->surname;
            }
            if (empty($newRequest->subject)) {
                $newRequest->subject = $this->_('Request');
            }
            $newRequest->title = sprintf($this->_('%1$s: %2$s (by %3$s)'), date('d.m.Y'), $newRequest->subject, $name);

            // Save requested page:
            $newRequest->page = $this->page;

            if (!$newRequest->save()) {
                $output['error']['processwire_error'] = array(
                    'text'   => $this->_('The request could not be saved.'),
                    'fields' => array()
                );
                throw new FormException($this->_('The request could not be saved.'));
            }
        } catch (WireCSRFException $e) {
            $output['error']['csrf_error']   = $this->_('This request was apparently forged and therefore aborted.');
            $output['submission_blocked']    = true;
            $output['status']                = false;
            if ($this->twack->isTwackAjaxCall()) {
                Twack::sendResponse($output, 403);
            }
            return $output;
        } catch (FormCriticalException $e) {
            $output['error']['form_error']      = $e->getMessage();
            $output['submission_blocked']       = true;
            $output['status']                   = false;

            if ($this->twack->isTwackAjaxCall()) {
                Twack::sendResponse($output, 400);
            }
            return $output;
        } catch (FormException $e) {
            $output['error']['form_error']      = $e->getMessage();
            $output['submission_blocked']       = false;
            $output['status']                   = false;

            if ($this->twack->isTwackAjaxCall()) {
                Twack::sendResponse($output, 400);
            }
            return $output;
        } catch (\Exception $e) {
            $output['error']['form_error']      = $e->getMessage();
            $output['submission_blocked']       = false;
            $output['status']                   = false;

            if ($this->twack->isTwackAjaxCall()) {
                Twack::sendResponse($output, 400);
            }
            return $output;
        }

        try {
            $this->sendNotification($newRequest);
        } catch (\Exception $e) {
            wire('log')->save('forms', 'An error occurred while sending the notification: ' . $e->getMessage());
        }

        $this->clearAntispamCode();
        wire('session')->set($this->formOrigin, $messageIdent);
        wire('session')->CSRF->resetToken($this->formularName);
        unset($_POST);

        $output['submission_blocked']    = true;
        $output['status']                = true;
        $output['success']['finished']   = $this->_('Your request was processed successfully.');

        if ($this->twack->isTwackAjaxCall()) {
            Twack::sendResponse($output, 200);
        }

        return $output;
    }

    protected function sendNotification(Page $newRequestPage) {
        if ($this->containerPage->template->hasField('email_notification')) {
            foreach ($this->containerPage->email_notification as $emailNotification) {
                $emailParams = $this->emailParams;

                if ($newRequestPage->id) {
                    // Create placeholder values based on the page:
                    // {{url}}, {{edit_url}}, {{title}}, {{field_contents}}
                    $this->emailParams['placeholders']['plain']['url']      = $newRequestPage->httpUrl;
                    $this->emailParams['placeholders']['html']['url']       = "<a href='{$newRequestPage->httpUrl}'>{$newRequestPage->httpUrl}</a>";
                    $this->emailParams['placeholders']['plain']['edit_url'] = $this->getEditUrl($newRequestPage);
                    $this->emailParams['placeholders']['html']['edit_url']  = "<a href='{$this->getEditUrl($newRequestPage)}'>{$this->getEditUrl($newRequestPage)}</a>";

                    $this->emailParams['placeholders']['title'] = $newRequestPage->title;

                    // Feldinhalte ablegen:
                    $this->emailParams['placeholders']['plain']['field_contents'] = '';
                    $this->emailParams['placeholders']['html']['field_contents']  = '';

                    $this->emailParams['placeholders']['html']['field_contents'] .= '<p><table cellspacing="0" cellpadding="5" border="0" width="700">';
                    $einruecken = false;
                    foreach ($newRequestPage->template->fields as $field) {
                        $field = $newRequestPage->template->fieldgroup->getField($field->name, true); // Include fieldgroup settings
                        if (!($field instanceof Field)) {
                            continue;
                        }
                        if ($field->hasFlag(Field::flagSystem)) {
                            continue; // Do not play any system fields
                        }
                        if (empty($this->getFieldHtml($field, $newRequestPage))) {
                            continue;
                        }

                        $label = trim($field->label);
                        if (!empty($label) && substr($label, -1) !== ':') {
                            $label .= ':';
                        }

                        if ($field->type instanceof FieldtypeFieldsetClose) {
                            $einruecken = false;
                            $this->emailParams['placeholders']['html']['field_contents'] .= '<tr><td> </td><td> </td></tr>';
                            // $this->emailParams['placeholders']['html']['field_contents'] .= "<tr><td> </td></tr>";
                            // $this->emailParams['placeholders']['plain']['field_contents'] .= "\n";
                            continue;
                        } elseif ($field->type instanceof FieldtypeFieldsetOpen) {
                            $einruecken = true;
                            if (empty($label)) {
                                continue;
                            }
                            $this->emailParams['placeholders']['html']['field_contents'] .= '<tr><td> </td></tr>';
                            $this->emailParams['placeholders']['html']['field_contents'] .= "<tr><td><strong>{$label}</strong></td><td></td></tr>";
                            $this->emailParams['placeholders']['plain']['field_contents'] .= "\r\n\r\n" . $label . ' ';
                            continue;
                        } elseif ($field->type instanceof \FieldtypeRuntimeMarkup) {
                            continue;
                        }

                        $this->emailParams['placeholders']['html']['field_contents'] .= '<tr>';
                        $this->emailParams['placeholders']['plain']['field_contents'] .= "\n";

                        $this->emailParams['placeholders']['html']['field_contents'] .= '<td width=\"190\">';
                        if ($einruecken) {
                            $this->emailParams['placeholders']['html']['field_contents'] .= '&nbsp; &nbsp; ';
                            $this->emailParams['placeholders']['plain']['field_contents'] .= '    ';
                        }
                        $this->emailParams['placeholders']['html']['field_contents'] .= '<strong>' . $label . ' </strong></td>';
                        $this->emailParams['placeholders']['plain']['field_contents'] .= $label . ' ';

                        $value = (string) $newRequestPage->get($field->name);
                        if ($field->type instanceof FieldtypeCheckbox) {
                            if ($value) {
                                $value = $this->_('Yes');
                            } else {
                                $value = $this->_('No');
                            }
                        } elseif ($field->type instanceof FieldtypeOptions) {
                            $value = '';
                            foreach ($newRequestPage->get($field->name) as $option) {
                                if (!empty($value)) {
                                    $value .= ', ';
                                }
                                $value .= $option->title;
                            }
                        }

                        $htmlValue = $value;
                        if (empty($value)) {
                            $value     = $this->_('[no input]');
                            $htmlValue = '<i>' . $this->_('[no input]') . '</i>';
                        }

                        $this->emailParams['placeholders']['html']['field_contents'] .= "<td width=\"390\">{$htmlValue}</td>";
                        $this->emailParams['placeholders']['plain']['field_contents'] .= $value;

                        $this->emailParams['placeholders']['html']['field_contents'] .= '</tr>';

                        if ($field->name !== 'field_contents' && !isset($this->emailParams['placeholders']['plain'][$field->name])) {
                            // Add field value as placeholder (key = field name):
                            $this->emailParams['placeholders']['html'][$field->name]  = $htmlValue;
                            $this->emailParams['placeholders']['plain'][$field->name] = $value;
                        }
                    }

                    $this->emailParams['placeholders']['html']['field_contents'] .= '</table></p>';
                    $this->emailParams['placeholders']['plain']['field_contents'] .= "\n";
                }

                // subject:
                if (!empty($emailNotification->short_text)) {
                    $emailParams['subject'] = $this->replacePlaceholders($emailNotification->get('short_text'), true);
                }

                // content text:
                if (!empty($emailNotification->freetext)) {
                    $emailParams['html']['content']  = $this->replacePlaceholders($emailNotification->get('freetext'));
                    $emailParams['plain']['content'] = $this->replacePlaceholders($this->htmlToPlain($emailNotification->get('freetext')), true);
                }

                // Signature:
                if (!empty($emailNotification->signature)) {
                    $emailParams['html']['signature']  = $this->replacePlaceholders($emailNotification->get('signature'));
                    $emailParams['plain']['signature'] = $this->replacePlaceholders($this->htmlToPlain($emailNotification->get('signature')), true);
                }

                // Set email recipient:
                if (!empty($emailNotification->email_recipient)) {
                    $emailParams['recipient'] = $this->getEmailRecipients($emailNotification->get('email_recipient'));
                }
                if (!empty($emailNotification->email_recipient_cc)) {
                    $emailParams['recipientCC'] = $this->getEmailRecipients($emailNotification->get('email_recipient_cc'));
                }
                if (!empty($emailNotification->email_recipient_bcc)) {
                    $emailParams['recipientBCC'] = $this->getEmailRecipients($emailNotification->get('email_recipient_bcc'));
                }

                // Are the required values available?
                if (empty($emailParams['subject'])) {
                    wire('log')->save('forms', 'No subject.');
                    continue;
                }
                if (empty($emailParams['html']['content']) && empty($emailParams['plain']['content'])) {
                    wire('log')->save('forms', 'No content.');
                    continue;
                }
                if (empty($emailParams['recipient'])) {
                    wire('log')->save('forms', 'No recipient. ' . json_encode($emailParams));
                    continue;
                }

                // Create and send mail:
                $email = wireMail();
                $email->header('X-Mailer', wire('pages')->get(1)->httpUrl . '');

                $email->to($emailParams['recipient']);
                if (!empty($emailParams['recipientCC'])) {
                    $email->header('cc', $emailParams['recipientCC']);
                }
                if (!empty($emailParams['recipientBCC'])) {
                    $email->header('bcc', $emailParams['recipientBCC']);
                }

                $email->subject($emailParams['subject']);

                if (!empty($emailParams['plain']['content'])) {
                    if (!empty($emailParams['plain']['signature'])) {
                        $emailParams['plain']['content'] .= "\r\n\r\n\r\n -- " . $emailParams['plain']['signature'];
                    }
                    $email->body($emailParams['plain']['content']);
                }
                if (!empty($emailParams['html']['content'])) {
                    if (!empty($emailParams['html']['signature'])) {
                        $emailParams['html']['content'] .= "\r\n\r\n\r\n -- " . $emailParams['html']['signature'];
                    }
                    $email->bodyHTML($emailParams['html']['content']);
                }

                $email->send();
            }
        }
    }

    protected function getEditUrl($page) {
        $protocol    = wire('config')->https ? 'https' : 'http';
        $pageEditUrl = strpos($page->editUrl, $protocol) === 0 ? $page->editUrl : $protocol . '://' . wire('config')->httpHost . $page->editUrl;
        return $pageEditUrl;
    }

    /**
     * Converts an HTML string into a string for a plain mail
     * @param  string $text
     * @return string
     */
    protected function htmlToPlain($text) {
        $text = str_replace(['</p>', '<br/>', '</tr>', '</table>'], "\r\n", $text);
        $text = str_replace('</td>', "\t", $text);
        return strip_tags($text);
    }

    /**
     * Replaces the placeholders in a text.
     *
     * @param  string $text
     * @return string
     */
    protected function replacePlaceholders($text, $plain = false) {
        if (!$plain) {
            $text = preg_replace_callback('/\{\{(\w+)}}/', function ($hits) {
                if (isset($this->emailParams['placeholders']['html']) && isset($this->emailParams['placeholders']['html'][$hits[1]])) {
                    return $this->emailParams['placeholders']['html'][$hits[1]];
                } elseif (isset($this->emailParams['placeholders'][$hits[1]])) {
                    return $this->emailParams['placeholders'][$hits[1]];
                }
            }, $text);
        } else {
            $text = preg_replace_callback('/\{\{(\w+)}}/', function ($hits) {
                if (isset($this->emailParams['placeholders']['plain']) && isset($this->emailParams['placeholders']['plain'][$hits[1]])) {
                    return $this->emailParams['placeholders']['plain'][$hits[1]];
                } elseif (isset($this->emailParams['placeholders'][$hits[1]])) {
                    return $this->emailParams['placeholders'][$hits[1]];
                }
            }, $text);
        }

        return $text;
    }

    protected function getEmailRecipients($recipientsField) {
        $recipients = array();
        foreach ($recipientsField as $recipient) {
            if ($recipient->type == 'user') {
                // user
                if (!empty($recipient->user_accounts)) {
                    foreach ($recipient->user_accounts as $user) {
                        if (!empty($user->contact_email)) {
                            $recipients[] = $user->contact_email;
                        } else {
                            $recipients[] = $user->email;
                        }
                    }
                }
            } elseif ($recipient->type == 'text') {
                // text boxes
                if (!empty($recipient->email)) {
                    $recipients[] = $recipient->email;
                }
            } elseif ($recipient->type == 'variable') {
                // variable value
                if (!empty($recipient->short_text)) {
                    $recipients[] = $this->replacePlaceholders($recipient->short_text, true);
                }
            }
        }
        return implode(', ', $recipients);
    }

    protected function setTemplate(Template $template) {
        $this->fields   = new WireArray();
        $this->template = $template;

        if (empty($this->formName)) {
            $this->formName = $this->template->name;
        }

        $newRequest           = new Page();
        $newRequest->template = $this->template;

        foreach ($template->fields as $field) {
            try {
                // Do not output system fields:
                if ($field->hasFlag(Field::flagSystem)) {
                    continue;
                }

                $field = $template->fieldgroup->getField($field->name, true); // Include fieldgroup settings

                if (!($field instanceof Field)) {
                    continue;
                }

                // Do not output fields that do not have HTML output:
                if (empty($this->getFieldHtml($field, $newRequest))) {
                    continue;
                }

                $field->inputHtml = $this->getFieldHtml($field, $newRequest);
                $this->fields->add($field);
            } catch (\Exception $e) {
                Twack::devEcho($e->getMessage());
            }
        }
    }

    /**
     * Returns an input field as HTML string for a special field.
     * @param  Field  $field
     * @param  Page   $page
     * @return string
     */
    protected function getFieldHtml(Field $field, Page $page) {
        return $this->getComponent('formOutput')->getFieldHtml($field, $page, $this->evaluationResponse);
    }
}
