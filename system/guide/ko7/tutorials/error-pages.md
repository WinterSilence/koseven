# Custom Error Pages

Custom error pages allow you to display a friendly error message to users, rather than the standard Koseven stack trace.

## Prerequisites

1. You will need `'errors' => TRUE` passed to [KO7::init](../api/KO7#init). This will convert PHP-errors into exceptions
   which are easier to handle (The default value is `TRUE`).
2. Custom error pages will only be used to handle throw [HTTP_Exception](../api/HTTP_Exception)'s. If you simply set a 
   status of, for example, 404 via [Respose::status](../api/Respone#status) the custom page will not be used.

## Extending the HTTP_Exception classes

Handling [HTTP_Exception](../api/HTTP_Exception)'s in Koseven has become easier with the changes introduced in version 3.3

For each [HTTP_Exception](../api/HTTP_Exception) class we can individually override the generation of the 
[Response](../api/Response) instance.

[!!] Note: We can also use HMVC to issue a sub-request to another page rather than generating the [Response](../api/Response) in the [HTTP_Exception](../api/HTTP_Exception) itself.

For example, to handle 404 pages we can do this in `APPPATH/classes/HTTP/Exception/404.php`:

	class HTTP_Exception_404 extends KO7_HTTP_Exception_404 {
		
		/**
		 * Generate a Response for the 404 Exception.
		 *
		 * The user should be shown a nice 404 page.
		 * 
		 * @return Response
		 */
		public function get_response()
		{
			$view = View::factory('errors/404');

			// Remembering that `$this` is an instance of HTTP_Exception_404
			$view->message = $this->getMessage();

			$response = Response::factory()
				->status(404)
				->body($view->render());

			return $response;
		}
	}

Another example, this time to handle 401 Unauthorized errors (aka "Not Logged In") we can do this in 
`APPPATH/classes/HTTP/Exception/401.php`:

	class HTTP_Exception_401 extends KO7_HTTP_Exception_401 {
		
		/**
		 * Generate a Response for the 401 Exception.
		 * 
		 * The user should be redirect to a login page.
		 * 
		 * @return Response
		 */
		public function get_response()
		{
			$response = Response::factory()
				->status(401)
				->headers('Location', URL::site('account/login'));

			return $response;
		}
	}

Finally, to override the default [Response](../api/Response) for all [HTTP_Exception](../api/HTTP_Exception)'s without 
a more specific override we can do this in `APPPATH/classes/HTTP/Exception.php`:

	class HTTP_Exception extends KO7_HTTP_Exception {
		
		/**
		 * Generate a Response for all Exceptions without a more specific override
		 * 
		 * The user should see a nice error page, however, if we are in development
		 * mode we should show the normal Koseven error page.
		 * 
		 * @return Response
		 */
		public function get_response()
		{
			// Lets log the Exception, Just in case it's important!
			KO7_Exception::log($this);

			if (KO7::$environment >= KO7::DEVELOPMENT)
			{
				// Show the normal Kohana error page.
				return parent::get_response();
			}
			else
			{
				// Generate a nicer looking "Oops" page.
				$view = View::factory('errors/default');

				$response = Response::factory()
					->status($this->getCode())
					->body($view->render());

				return $response;
			}
		}
	}
