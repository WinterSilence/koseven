<?php

/**
 * Cookie-based session class.
 *
 * @package    KO7
 * @category   Session
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
class KO7_Session_Cookie extends Session
{

    /**
     * @param string $id session id
     * @return  string
     */
    protected function _read($id = null)
    {
        return Cookie::get($this->_name, null);
    }

    /**
     * @return  null
     */
    protected function _regenerate()
    {
        // Cookie sessions have no id
        return null;
    }

    /**
     * @return  bool
     */
    protected function _write()
    {
        return Cookie::set($this->_name, $this->__toString(), $this->_lifetime);
    }

    /**
     * @return  bool
     */
    protected function _restart()
    {
        return true;
    }

    /**
     * @return  bool
     */
    protected function _destroy()
    {
        return Cookie::delete($this->_name);
    }

}
