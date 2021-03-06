<?php

namespace BoomCMS\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Group extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'boomcms.repositories.group';
    }
}
