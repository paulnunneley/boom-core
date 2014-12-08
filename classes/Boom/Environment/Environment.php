<?php

namespace Boom\Environment;

use Exception;
use Boom\Exception\Handler\Priv as PrivateExceptionHandler;

abstract class Environment
{
    /**
     *
     * @var boolean
     */
    protected $requiresLogin = false;

    /**
     *
     * @param  Exception               $e
     * @return PrivateExceptionHandler
     */
    public function getExceptionHandler(Exception $e)
    {
        return new PrivateExceptionHandler($e);
    }

    /**
     *
     * @return boolean
     */
    public function isDevelopment()
    {
        return false;
    }

    /**
     *
     * @return boolean
     */
    public function isProduction()
    {
        return false;
    }

    /**
     *
     * @return boolean
     */
    public function isStaging()
    {
        return false;
    }

    /**
     *
     * @return boolean
     */
    public function requiresLogin()
    {
        return $this->requiresLogin;
    }
}