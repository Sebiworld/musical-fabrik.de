<?php
namespace ProcessWire;

class FormException extends \Exception {
	protected $errorcode;

	public function __construct(string $message = '', string $errorcode = 'form_error', ?\Throwable $previous = null) {
		parent::__construct($message, 0, $previous);
		$this->errorcode = $errorcode;
	}

	/**
	 * Machine-readable error code for API responses.
	 */
	public function getErrorcode(): string {
		return $this->errorcode;
	}
}
class FormCriticalException extends FormException {
}
