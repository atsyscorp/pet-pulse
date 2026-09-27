<?php

declare(strict_types=1);

namespace App\Exceptions\Kardex;

use DomainException;

/**
 * Base class for business-rule violations on the nursing kárdex.
 *
 * These are expected, user-facing outcomes (not bugs): they are excluded from
 * error reporting and carry a message safe to display at the bedside
 * (see the dontReport/render registration in bootstrap/app.php).
 */
abstract class KardexException extends DomainException {}
