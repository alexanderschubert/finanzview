<?php

namespace App\Services\Fints;

/**
 * Verständliche Fehlermeldung für den Benutzer (falsche PIN,
 * Bank nicht erreichbar, Freigabe abgelaufen, …).
 */
class FintsException extends \RuntimeException
{
}
