<?php

namespace BoomCMS\Core\Controllers\CMS\Auth;

use BoomCMS\Core\Controllers\Controller;
use BoomCMS\Core\Person;

class Login extends Controller
{
    public function showLoginForm()
    {
        if ($this->auth->auto_login()) {
            $this->request->redirect('/');
        } else {
            return $this->displayLoginForm();
        }
    }

    public function processLogin(Person\Provider $provider)
    {
        $person = $provider->findByEmail($this->request->input('email'));

        try {
            $this->auth->authenticate($this->request->input('email'), $this->request->input('password'));
        } catch (Exception $e) {
            $this->_log_login_success();
            $this->redirect($this->_get_redirect_url(), 303);
        }
        if ($this->auth->login($person, $this->request->input('password'), $this->request->input('remember') == 1)) {
            $this->_login_complete();
        } else {
            $this->_log_login_failure();

            $error = ($person->isLocked()) ? 'locked' : 'invalid';
            $error_message = Kohana::message('login', "errors.$error");

            if ($person->isLocked()) {
                $lock_wait = \Date::span($person->getLockedUntil());
                $lock_wait = $lock_wait['minutes']." ".Inflector::plural('minute', $lock_wait['minutes']);
                $error_message = str_replace(':lock_wait', $lock_wait, $error_message);
            }

            $this->displayLoginForm(['login_error' => $error_message]);
        }
    }

    protected function displayLoginForm()
    {
        return view('boom::account.login', [
            'request' => $this->request
        ]);
    }
}