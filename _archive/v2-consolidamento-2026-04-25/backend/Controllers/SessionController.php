<?php

namespace App\Http\Controllers;

/**
 * @deprecated Use AuthSessionController (show) and SessionDataController (firstStep, secondStep) instead.
 *
 * This class is kept only for backward compatibility with any code that may
 * reference it directly.  All route registrations have been migrated to the
 * two focused controllers listed above.
 */
class SessionController extends SessionDataController
{
    // Inherits all public methods from SessionDataController (firstStep, secondStep,
    // static pricing helpers) and the BuildsSessionPayload trait (show helper).
    // The `show` method lives on AuthSessionController but routes now point there directly.
}
