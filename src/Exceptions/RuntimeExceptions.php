<?php

namespace blitzik\Routing\Exceptions;

class RuntimeException extends \RuntimeException {}

    class UrlAlreadyExistsException extends RuntimeException {}

    class UrlNotPersistedException extends RuntimeException {}

    class NoLocalesSetException extends RuntimeException {}