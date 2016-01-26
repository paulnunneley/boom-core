<?php

namespace BoomCMS\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;

    /**
     * @var string
     */
    protected $role;

    public function __construct(Request $request)
    {
        $this->request = $request;

        if ($this->role) {
            $this->authorize($this->role, $request);
        }
    }
}
