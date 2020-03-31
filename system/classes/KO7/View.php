<?php
/**
 * Acts as an object wrapper for HTML pages with embedded PHP, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package    KO7
 * @category   Base
 *
 * @copyright  (c) 2007-2020  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
abstract class KO7_View {

	/**
	 * @var array Global variables
	 */
	protected static $_global_data = [];

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [View::set_filename].
	 *
	 * @param   string|null  $file   view filename
	 * @param   iterable     $data   array of values
	 * @return  View
	 */
	public static function factory($file = NULL, iterable $data = [])
	{
		return new View($file, $data);
	}

	/**
	 * Captures the output that is generated when a view is included.
	 * The view data will be extracted to make local variables. This method
	 * is static to prevent object scope resolution.
	 *
	 * @param   string  $ko7_view_filename   filename
	 * @param   array   $ko7_view_data       variables
	 * @return  string
	 * @throws  Throwable
	 */
	protected static function capture($ko7_view_filename, $ko7_view_data)
	{
		// Import the view variables to local namespace
		extract($ko7_view_data, EXTR_SKIP);

		if (View::$_global_data)
		{
			// Import the global view variables to local namespace
			extract(View::$_global_data, EXTR_SKIP | EXTR_REFS);
		}

		// Capture the view output
		ob_start();

		try
		{
			// Load the view within the current scope
			include $ko7_view_filename;
		}
		catch (Throwable $e)
		{
			// Delete the output buffer
			ob_end_clean();
 			// Re-throw the exception
 			$path = Dump::path($ko7_view_filename);
			throw new View_Exception(
				'Rendering error in view :file: :error',
				[':file' => $path, ':error' => $e->getMessage()],
				0,
				$e
			);
		}

		// Get the captured output and close the buffer
		return ob_get_clean();
	}

	/**
	 * Sets a global variable, similar to [View::set], except that the
	 * variable will be accessible to all views.
	 *
	 *     View::set_global($name, $value);
	 *
	 * You can also use an array or Traversable object to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the view
	 *     View::set_global(['food' => 'bread', 'beverage' => 'water']);
	 *
	 * [!!] Note: When setting with using Traversable object we're not attaching the whole object to the view,
	 * i.e. the object's standard properties will not be available in the view context.
	 *
	 * @param   string|iterable  $key    variable name or an array of variables
	 * @param   mixed            $value  value
	 * @return  void
	 */
	public static function set_global($key, $value = NULL)
	{
		if (is_iterable($key))
		{
			foreach ($key as $name => $value)
			{
				View::$_global_data[$name] = $value;
			}
		}
		else
		{
			View::$_global_data[$key] = $value;
		}
	}

	/**
	 * Assigns a global variable by reference, similar to [View::bind], except
	 * that the variable will be accessible to all views.
	 *
	 * @param   string  $key    variable name
	 * @param   mixed   $value  referenced variable
	 * @return  void
	 */
	public static function bind_global($key, &$value)
	{
		View::$_global_data[$key] =& $value;
	}

	/**
	 * @var string Full path to view
	 */
	protected $_file;

	/**
	 * @var string Source view filename
	 */
	protected $_source_file;

	/**
	 * @var array Local variables
	 */
	protected $_data = [];

	/**
	 * Sets the initial view filename and local data. Views should almost
	 * always only be created using [View::factory].
	 *
	 * @param   string|null  $file   view filename
	 * @param   iterable     $data   array of values
	 */
	public function __construct($file = NULL, iterable $data = [])
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}
		if ($data)
		{
			$this->set($data);
		}
	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 * [!!] If the variable has not yet been set, an exception will be thrown.
	 *
	 * @param   string  $key  Variable name
	 * @return  mixed
	 * @throws  View_Exception
	 */
	public function &__get($key)
	{
		if (array_key_exists($key, $this->_data))
		{
			return $this->_data[$key];
		}
		elseif (array_key_exists($key, View::$_global_data))
		{
			return View::$_global_data[$key];
		}
		throw new View_Exception(
			'Variable is not set: :var',
			[':var' => $key]
		);
	}

	/**
	 * Magic method, calls [View::set] with the same parameters.
	 *
	 * @param   string  $key    variable name
	 * @param   mixed   $value  value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 * [!!] `NULL` variables are not considered to be set by [isset](https://php.net/isset).
	 *
	 * @param   string  $key  variable name
	 * @return  bool
	 */
	public function __isset($key)
	{
		return isset($this->_data[$key]) OR isset(View::$_global_data[$key]);
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 * @param   string  $key    variable name
	 * @return  void
	 */
	public function __unset($key)
	{
		unset($this->_data[$key], View::$_global_data[$key]);
	}

	/**
	 * Magic method, returns the output of [View::render].
	 *
	 * @return string
	 */
	public function __toString()
	{
		try
		{
			return (string) $this->render();
		}
		catch (Throwable $e)
		{
			/**
			 * Display the exception message and halt script execution.
			 * We use this method here because it's impossible to throw an
			 * exception from `__toString()`.
			 */
			View_Exception::handler($e);
			// This line will never ne reached
			return '';
		}
	}

	/**
	 * Sets the view filename.
	 *
	 * @param   string  $file   view filename
	 * @return  $this
	 * @throws  View_Exception
	 */
	public function set_filename($file)
	{
		$path = KO7::find_file('views', $file);
		if (! $path)
		{
			throw new View_Exception(
				'The requested view :file could not be found',
				[':file' => $file]
			);
		}

		// Store the file path locally
		$this->_file = $path;
		$this->_source_file = $file;

		return $this;
	}

	/**
	 * Gets the view filename.
	 *
	 * @param bool $full If true, return full path, else source filename
	 * @return string|null
	 */
	public function get_filename(bool $full = false): ?string
	{
		return $full ? $this->_file : $this->_source_file;
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the view file.
	 *
	 * You can also use an array or Traversable object to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the view
	 *     $view->set(['food' => 'bread', 'beverage' => 'water']);
	 *
	 * [!!] When setting with using Traversable object we're not attaching the whole object to the view,
	 * i.e. the object's standard properties will not be available in the view context.
	 *
	 * @param   string|iterable  $key    variable name or an array of variables
	 * @param   mixed            $value  value
	 * @return  $this
	 */
	public function set($key, $value = NULL)
	{
		if (is_iterable($key))
		{
			foreach ($key as $name => $value)
			{
				$this->_data[$name] = $value;
			}
		}
		else
		{
			$this->_data[$key] = $value;
		}

		return $this;
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the view file.
	 *
	 * @param   string  $key    variable name
	 * @param   mixed   $value  referenced variable
	 * @return  self
	 */
	public function bind($key, &$value)
	{
		$this->_data[$key] =& $value;

		return $this;
	}

	/**
	 * Deletes all local and optionally global variables.
	 *
	 *     $datasets = [['food' => 'bread', 'beverage' => 'water'], ['food' => 'bread']];
	 *     foreach ($datasets as $key => $dataset)
	 *     {
	 *         $datasets[$key] = $view->set($dataset)->render();
	 *         $view->clear();
	 *     }
	 *
	 * [!!] Useful when template rendering with different datasets.
	 *
	 * @param  bool  $all  delete global data?
	 * @return self
	 */
	public function clear($all = FALSE)
	{
		$this->_data = [];

		if ($all)
		{
			View::$_global_data = [];
		}

		return $this;
	}

	/**
	 * Renders the view object to a string. Global and local data are merged
	 * and extracted to create local variables within the view file.
	 *
	 * [!!] Global variables with the same key name as local variables will be
	 * overwritten by the local variable.
	 *
	 * @param   string|null  $file   view filename
	 * @return  string
	 * @throws  View_Exception
	 */
	public function render($file = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}
		if (! $this->_file)
		{
			throw new View_Exception(
				'You must set the file to use within your view before rendering'
			);
		}
		// Combine local and global data and capture the output
		return View::capture($this->_file, $this->_data);
	}
}
