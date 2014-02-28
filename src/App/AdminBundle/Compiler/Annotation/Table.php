<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class Table extends AbstractProperty
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
    
    // for table.th.td
    
    /** @var mix */
    public $th ;
    
    /** @var mix */
    public $td ;
    
    /** @var mix */
    public $tag ;
    
    /** @var mix */
    public $href ;
    
    /** @var mix */
    public $width ;
    
    // for date
    
    /** @var string */
    public $format ;
    
}
