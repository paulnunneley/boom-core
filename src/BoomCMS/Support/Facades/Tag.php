<?php

namespace BoomCMS\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Tag extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'boomcms.repositories.tag';
    }
}
