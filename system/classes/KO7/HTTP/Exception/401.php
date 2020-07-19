<?php

class KO7_HTTP_Exception_401 extends HTTP_Exception_Expected
{
    
    /**
     * @var   integer    HTTP 401 Unauthorized
     */
    protected $_code = 401;
    
    /**
     * Specifies the WWW-Authenticate challenge.
     *
     * @param string $challenge WWW-Authenticate challenge (eg `Basic realm="Control Panel"`)
     */
    public function authenticate($challenge = null)
    {
        if ($challenge === null) {
            return $this->headers('www-authenticate');
        }
        
        $this->headers('www-authenticate', $challenge);
        
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
        if ($this->headers('www-authenticate') === null) {
            throw new KO7_Exception('A \'www-authenticate\' header must be specified for a HTTP 401 Unauthorized');
        }
        
        return true;
    }
    
}
