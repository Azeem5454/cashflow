<?php

namespace App\Services;

/**
 * Thrown by AppleIdentityTokenVerifier when an identity token is rejected.
 * The message is a short internal reason for logs — never shown to clients.
 */
class AppleTokenInvalid extends \RuntimeException
{
}
