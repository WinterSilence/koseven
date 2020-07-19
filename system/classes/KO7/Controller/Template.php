<?php

/**
 * Abstract controller class for automatic templating.
 *
 * @package    KO7
 * @category   Controller
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
abstract class KO7_Controller_Template extends Controller
{
    
    /**
     * @var  View  page template
     */
    public $template = 'template';
    
    /**
     * @var  boolean  auto render template
     **/
    public $auto_render = true;
    
    /**
     * Loads the template [View] object.
     */
    public function before()
    {
        parent::before();
        
        if ($this->auto_render === true) {
            // Load the template
            $this->template = View::factory($this->template);
        }
    }
    
    /**
     * Assigns the template [View] as the request response.
     */
    public function after()
    {
        if ($this->auto_render === true) {
            $this->response->body($this->template->render());
        }
        
        parent::after();
    }
    
}
