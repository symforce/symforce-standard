<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class View extends AbstractProperty
{
    
    /** @var string */
    public $label ;
    
    /** @var string */
    public $property ;
    
    /** @var string */
    public $icon ;
    
    /** @var bool */
    public $order ;
    
    /** @var string */
    public $template ;
    
    /** @var string */
    public $code ;
    
    /** @var integer */
    public $position ;
    
}
