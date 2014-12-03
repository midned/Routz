<?php

require 'vendor/autoload.php';

use Routz\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

Request::enableHttpMethodParameterOverride();

$router = new Router();

$router->get('/, /home', function() {
	// $this refers to the router
	// through the router you can access the current request, session and other methods
	$content = '';

	if ($rem = $this->session->get('name')) {
		$content .= 'Whoa, I remember you '.$rem.'<br />';
		$this->session->remove('name');
	}
	else {
		$this->session->set('name', 'Alvaro');
	}

	$content .= 'Hello there ' . $this->request->get('name', 'alvaro').'<br />';

	$content .= $this->to('home').'<br />';

	$content .= $this->to('testo').'<br />';

	$content .= $this->to('profile', ['id' => 2]).'<br />';

	return new Response($content);
})->named('home');

$router->get('/test', function() {
	echo ':D';
})->named('testo');

$router->get('/user/{id}', function($id) {
	echo 'Hi ' . $id;
})->before(function($id) {
	if ($id == 2) {
		return new RedirectResponse($this->to('home'));
	}
})->named('profile');

$router->error(function() {
	echo 'Whoa, an error';
});

$router->run()->send();