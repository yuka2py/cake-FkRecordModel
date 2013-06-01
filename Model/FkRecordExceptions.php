<?php


/**
 * Represents an HTTP 422 error.
 */
class FkRecordInvalidException extends RuntimeException {
	public function __construct($message='Record invalid', $code=422) {
		parent::__construct($message, $code);
	}
}


class FkRecordValidationError extends FkRecordInvalidException {
	public function __construct($message='Record validation failed', $code=422) {
		parent::__construct($message, $code);
	}
}