<?php

/**
 * "Expected" HTTP exception class. Used for all [HTTP_Exception]'s where a standard
 * KO7 error page should never be shown.
 * Eg [HTTP_Exception_301], [HTTP_Exception_302] etc
 *
 * @package    KO7
 * @category   Exceptions
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
abstract class KO7_HTTP_Exception_Expected extends HTTP_Exception
{

    /**
     * @var  Response   Response Object
     */
    protected $_response;

    /**
     * Creates a new translated exception.
     *     throw new KO7_Exception('Something went terrible wrong, :user',
     *         array(':user' => $user));
     *
     * @param string $message status message, custom content to display with error
     * @param array $variables translation variables
     * @return  void
     */
    public function __construct($message = null, array $variables = null, Exception $previous = null)
    {
        parent::__construct($message, $variables, $previous);

        // Prepare our response object and set the correct status code.
        $this->_response = Response::factory()
            ->status($this->_code);
    }

    /**
     * Gets and sets headers to the [Response].
     *
     * @param mixed $key
     * @param string $value
     * @return  mixed
     * @see     [Response::headers]
     */
    public function headers($key = null, $value = null)
    {
        $result = $this->_response->headers($key, $value);

        if (! $result instanceof Response) {
            return $result;
        }

        return $this;
    }

    /**
     * Validate this exception contains everything needed to continue.
     *
     * @return bool
     * @throws KO7_Exception
     */
    public function check()
    {
        return true;
    }

    /**
     * Generate a Response for the current Exception
     *
     * @return Response
     * @uses   KO7_Exception::response()
     */
    public function get_response()
    {
        $this->check();

        return $this->_response;
    }

}
