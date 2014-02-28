<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class Filter extends AbstractProperty
{
    /** @var string */
    public $label ;
    
    /** @var string */
    public $property ;
    
    /** @var bool */
    public $order ;
    
    /** @var string */
    public $template ;
    
    /** @var string */
    public $code ;
    
    /** @var integer */
    public $position ;
    
}
