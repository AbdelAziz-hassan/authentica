<?php

namespace AbdelAzizHassan\Authentica\Facades;

use Illuminate\Support\Facades\Facade;
use AbdelAzizHassan\Authentica\Contracts\AuthenticaClient;

/**
 * @method static array balance()
 */
class Authentica extends Facade
{
    protected static function getFacadeAccessor()
    {
        return AuthenticaClient::class;
    }
}
