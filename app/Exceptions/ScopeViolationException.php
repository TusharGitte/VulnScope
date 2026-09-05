<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by ScopeEnforcementService whenever an action would touch a host/port/IP
 * or time window outside the confirmed authorization. Controllers should catch this
 * and render a clear BLOCKED state to the analyst rather than a generic 500.
 */
class ScopeViolationException extends Exception
{
    //
}
