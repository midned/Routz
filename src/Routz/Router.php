<?php

namespace Routz;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class to manage routes
 *
 * @author Alvaro Carneiro <alv.ccbb@gmail.com>
 * @package Routz
 * @license MIT License
 */
class Router {
	
	/**
	 * Current http request
	 *
	 *
	 * @var Symfony\Component\HttpFoundation\Request
	 */
	public $request;

	/**
	 * List of routes names
	 * Used to generate urls using the base url and assigning values to wildcards
	 *
	 * @var array
	 */
	protected $name = [];
	
	/**
	 * List of error handlers. They will be called when we catch a HttpException error
	 *
	 * @var array
	 */
	protected $error_handlers;
	
	/**
	 * List of callbacks assigned to certain request method
	 *
	 * @var array
	 */
	protected $actions = [
		'ANY'		=>	[],
		'GET'		=>	[],
		'POST'		=>	[],
		'PUT'		=>	[],
		'DELETE'	=>	[]
	];


	/**
	 * Create the router instance
	 *
	 * @param Symfony\Component\HttpFoundation\Session\Session $session The current php sessioon
	 */
	public function __construct($session = null, $use_session = true) {
		if ( ! ($session instanceof Session) && true === $use_session) {
			$session = new Session();
			$session->start();
		}

		$this->session = $session;
	}

	/**
	 * Assign a callback to one or more routes
	 *
	 * @param string $method Request method
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	protected function register($method, $route, callable $callback)
	{
		// Create the new action
		$action = new Action($route, $callback, $this);
		
		// and save it to the corresponding request method
		return $this->actions[$method][] = $action;
	}
	
	/**
	 * Assign a callback to one or more routes from every request method
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function any($route, $callback)
	{
		return $this->register('ANY', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from GET request method
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function get($route, $callback)
	{
		return $this->register('GET', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from POST request method
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function post($route, $callback)
	{
		return $this->register('POST', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from GET request method
	 *
	 * Because HTML don't support PUT/DELETE methods we use "_method" input (in $_REQUEST variable). 
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function put($route, $callback)
	{
		return $this->register('PUT', $route, $callback);
	}
	
	/**
	 * Assign a callback to one or more routes from DELETE request method
	 *
	 * Because HTML don't support PUT/DELETE methods we use "_method" input (in $_REQUEST variable). 
	 *
	 * @param string|array $route Routes this callback with handle
	 * @param callable $callback The callback
	 *
	 * @throws InvalidArgumentException When the callback parameter isn't valid
	 *
	 * @return Action The action
	 */
	public function delete($route, $callback)
	{
		return $this->register('DELETE', $route, $callback);
	}
	
	/**
	 * Generate a route using the base_url variable.
	 * If there's no predefined route identified with the $name we'll use
	 * the $name variable as the url we're going to generate
	 *
	 * @param string $name The name of the route
	 * @param array|object $replacements Structure to give to it
	 * @param integer $offset Some actions have more than one route, optionally you can declare wich one do you like to use
	 *
	 * @return string The build route
	 */
	public function to($name, $replacements = [], $offset = 0)
	{
		// try to set the route with a predefined one
		// but if it don't exists use the name as it
		if ( ! ($action = $this->find($name)))
		{
			$route = $name;
		}
		else
		{
			$routes = $action->route();
			
			$route = isset($routes[$offset]) ? $routes[$offset] : $routes[0];
		}
		
		// if the "replacements" variable is an object
		// use the attributes of it to make the replacements
		if (is_object($replacements))
		{
			$replacements = get_object_vars($replacements);
		}
		
		// let's iterate over each replacement
		foreach ((array)$replacements as $key => $value)
		{
			// we dont like to put these in the parameters
			$route = str_replace('{'.$key.'}', $value, $route);
			
			// if there's nothing to replace exit this loop
			if ( ! strstr($route, '{'))
			{
				break;
			}
		}
		
		return $this->request->getBasePath().$route;
	}
	
	/**
	 * Sets an identifier to this route, so we can make a url to it
	 *
	 * @param Action $action The action
	 * @param string $name The name of this action
	 *
	 * @return void
	 */
	public function identify(Action $action, $name)
	{
		$this->name[$name] = $action;
	}
	
	/**
	 * Get the action with this identifier
	 *
	 * @param string $name The name of the action
	 *
	 * @return Action|bool The action, or false if it don't exists
	 */
	public function find($name)
	{
		return isset($this->name[$name]) ? $this->name[$name] : false;
	}

	/**
	 * Run the router using the current global request
	 *
	 * @return Symfony\Component\HttpFoundation\Response The response to be sent
	 */
	public function run() {
		return $this->handle(Request::createFromGlobals());
	}
	
	/**
	 * Run using a custom request
	 *
	 * @param Symfony\Component\HttpFoundation\Request $request Request to be handled
	 *
	 * @return Symfony\Component\HttpFoundation\Response The resultant response
	 */
	public function forge(Request $request) {
		return $this->handle($request);
	}

	/**
	 * Ensure that we'll return a valid Symfony\Component\HttpFoundation\Response object
	 *
	 * @param mixed $mixed The parameter to convert to a Response object
	 *
	 * @return Symfony\Component\HttpFoundation\Response The response
	 */
	public static function ensureItsResponseObject($mixed) {
		if ( ! ($mixed instanceof Response)) {
			$mixed = new Response($mixed);
		}

		return $mixed;
	}

	/**
	 * Handle a request and return the response to be sent
	 *
	 * @param Symfony\Component\HttpFoundation\Request The request to be handled
	 *
	 * @return Symfony\Component\HttpFoundation\Response The resultant response
	 */
	protected function handle(Request $request)
	{
		$this->request = $request;

		$method = $request->getMethod();

		if ( ! in_array($method, array_keys($this->actions))) {
			$method = 'GET';
		}
		
		// merge the actions with the ones that correspond
		// to the actual request method
		$actions = array_merge($this->actions['ANY'], $this->actions[$method]);
		
		try
		{
			// Itereate over each action
			foreach ($actions as $action)
			{
				// And over each route of it
				foreach ($action->route() as $route)
				{
					// check if the current uri matches with the
					// action's route and fetch the arguments to pass to it
					list($matches, $arguments) = $this->matches($route);
					
					// if matches, call it with the arguments
					// and return the contents of it
					if ($matches)
					{
						$response = $action->call($arguments);
						break 2;
					}
				}
			}
			
			if ( ! $matches) {
				// we finished the foreach without calling any action
				// so we can throw a http not found error
				throw new HttpException('Route not found', 404);
			}
		}
		// use that try statment so we can throw
		// an http exception inside of an action
		catch(HttpException $error)
		{
			// If we can handle errors with this status code
			if (isset($this->error_handlers[(string)$error->getCode()]))
			{
				$handler = $this->error_handlers[(string)$error->getCode()];
			}
			// if we setted a global handler for all type of http errors
			elseif (isset($this->error_handlers['global']))
			{
				$handler = $this->error_handlers['global'];
			}
			// Re-throw the exception, we haven't assigned one
			else
			{
				$handler = function($exception){ throw $exception; };
			}
			
			// Call the handler passing the exception instance
			$response = call_user_func($handler, $error);
		}

		return static::ensureItsResponseObject($response);
	}
	
	/**
	 * Produce a http error with a http code and message
	 *
	 * <code>
	 *	$router->produce(404); // Throw a http not found error
	 *
	 *	$router->produce(500, 'Something went wrong');
	 * </code>
	 *
	 * @param int $error_code The code of the http error
	 * @param string $message The message of the http error
	 */
	public function produce($error_code, $message = null)
	{
		throw new HttpException($message, $error_code);
	}
	
	/**
	 * Set an error handler to the given http error codes
	 * If $code isn't an integer type it must be a callable type used to handle all type
	 * of http errors
	 *
	 * <code>
	 *	$router->error('404', function(){ }); // Will handle http errors with 404 code (page not found)
	 *	
	 *	$router->error(function(){ }); // Will handle all type of http errors except 404 because we already have defined the handler for that error
	 * </code>
	 *
	 * @param int $code The http error code we'll handle
	 * @param callable $handler The handler of it
	 */
	public function error($code, $handler = null)
	{
		// if the $code is a callable variable then
		// use it as a handler for all type of http errors
		if (is_callable($code))
		{
			$handler = $code;
			$code = 'global';
		}
		
		// If the handler is invalid
		// throw an exception
		if ( ! is_callable($handler))
		{
			throw new \InvalidArgumentException('The $handler parameter isn\'t valid.');
		}
		
		// Make the $code variable an array so we can
		// handle mutliple http errors types
		foreach ((array)$code as $status)
		{
			$this->error_handlers[(string)$status] = $handler;
		}
	}
	
	/**
	 * Check if the route matches the requested uri
	 *
	 * @param string $route The route to check if matches with the current one
	 *
	 * @return array The first element of the array is the resultant of the comparison and the second are the parameters to pass to it
	 */
	public function matches($route)
	{		
		// No wildcards, simply compare it
		if ( ! strpos($route, '{'))
		{
			// Because it don't have regular expressions
			// compare it and simply pass an array as arguments
			$matches = $route == $this->request->getPathInfo();
			
			$arguments = [];
		}
		else
		{
			// apply wildcards to the route
			$route = Wildcards::make($route);
			
			// Use regular expressions to compare it and
			// store the results in the arguments array to pass
			// them to the action
			$arguments = [];
			
			$matches = (bool)preg_match('#^' . $route . '$#', $this->request->getPathInfo(), $arguments);
			
			array_shift($arguments);
		}
		
		return [$matches, $arguments];
	}
}