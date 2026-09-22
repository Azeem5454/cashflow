<?php

namespace App\Exceptions;

/**
 * An expected, non-error reason for the blog autopilot not to publish
 * (disabled, cooldown, empty queue, run already in progress). The
 * blog:generate command exits cleanly on these; anything else is a real
 * failure that gets reported to Sentry and surfaced in the admin UI.
 */
class BlogAutopilotSkipped extends \RuntimeException
{
}
