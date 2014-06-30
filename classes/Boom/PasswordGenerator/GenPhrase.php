<?php

namespace Boom\PasswordGenerator;

use \Kohana as Kohana;
use \GenPhrase as GenPhrase;

class GenPhrase extends Boom\PasswordGenerator
{
	public function __construct()
	{
		require Kohana::find_file('vendor', 'genphrase/library/GenPhrase/Loader');
		$loader = new GenPhrase\Loader('GenPhrase');
		$loader->register();
	}

	public function get_password()
	{
		$gen = new GenPhrase\Password();
		return $gen->generate();
	}
}