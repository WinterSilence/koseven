<?php

/**
 * Class method parameter documentation generator.
 *
 * @package    KO7/Userguide
 * @category   Base
 * @copyright  (c) 2007-2016  Kohana Team
 * @copyright  (c) since 2016 Koseven Team
 * @license    https://koseven.dev/LICENSE
 */
class KO7_Kodoc_Method_Param extends Kodoc
{

    /**
     * @var  object  ReflectionParameter for this property
     */
    public $param;

    /**
     * @var  string  name of this var
     */
    public $name;

    /**
     * @var  string  variable type, retrieved from the comment
     */
    public $type;

    /**
     * @var  string  default value of this param
     */
    public $default;

    /**
     * @var  string  description of this parameter
     */
    public $description;

    /**
     * @var  boolean  is the parameter passed by reference?
     */
    public $reference = false;

    /**
     * @var  boolean  is the parameter optional?
     */
    public $optional = false;

    public function __construct($method, $param)
    {
        $this->param = new ReflectionParameter($method, $param);

        $this->name = $this->param->name;

        if ($this->param->isDefaultValueAvailable()) {
            $this->default = Debug::dump($this->param->getDefaultValue());
        }

        if ($this->param->isPassedByReference()) {
            $this->reference = true;
        }

        if ($this->param->isOptional()) {
            $this->optional = true;
        }
    }

    public function __toString()
    {
        $display = '';

        if ($this->type) {
            $display .= '<small>' . $this->type . '</small> ';
        }

        if ($this->reference) {
            $display .= '<small><abbr title="passed by reference">&</abbr></small> ';
        }

        if ($this->description) {
            $display .= '<span class="param" title="' . preg_replace(
                    '/\s+/',
                    ' ',
                    $this->description
                ) . '">$' . $this->name . '</span> ';
        } else {
            $display .= '$' . $this->name . ' ';
        }

        if ($this->default) {
            $display .= '<small>= ' . $this->default . '</small> ';
        }

        return $display;
    }

} // End Kodoc_Method_Param
