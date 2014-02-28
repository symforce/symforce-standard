<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS", "PROPERTY"})
 */
class Form extends AbstractProperty
{
    /** @var string */
    public $label ;
    
    /** @var string */
    public $property ;
    
    /** @var string */
    public $type ;
    
    /** @var bool */
    public $auth ;
    
    /** @var string */
    public $name ;
    
    /** @var bool */
    public $disabled;
    
// for element
    
    /** @var bool */
    public $mapped ;
    
    /** @var bool */
    public $error_bubbling ;
    
    /** @var string */
    public $group ;
    
    /** @var mixed */
    public $default ;
    
    /** @var bool */
    public $unique  ;
    
    /** @var bool */
    public $required  ;
    
    /** @var bool */
    public $read_only  ;
    
    /** @var bool */
    public $not_blank ;
    
    /** @var integer */
    public $position  ;
    
    /** @var mixed */
    public $invalid ;
    
// for mopa
    
    /** @var bool */
    public $label_render ;
    
    /** @var mixed */
    public $help ;
    
    /** @var mixed */
    public $widget ;
    
    /** @var mixed */
    public $attr ;
    
    /** @var mixed */
    public $wrap ;
    
// for text
    
    /** @var integer */
    public $width ;
    
    /** @var integer */
    public $max_length ;
    
    /** @var integer */
    public $min_length ;
    
    /** @var bool */
    public $trim ;
    
// for password
    
    /** @var string */
    public $repeat ;
    
    /** @var string */
    public $salt ;
    
    /** @var string */
    public $real_password ;

// for URL
    /** @var string */
    public $default_protocol ;
    
// for textarea
    
    /** @var integer */
    public $height ;
    
// for html
    public $image ;
    public $video ;
    public $valid_elements ;
    public $extended_valid_elements ;
    
// for datetime 
    
    /** @var string */
    public $format ;
    
    /** @var string */
    public $greater_than ;
    
    /** @var string */
    public $less_than ;
    
// for file
    
    /** @var string */
    public $max_size ;
    
    /** @var mixed */
    public $extentions ;
    
// for image
    
    /** @var string */
    public $image_size ;
    
    /** @var string */
    public $small_size ;
    
    /** @var bool */
    public $use_crop ;

// for bool
   /** @var string */
   public $yes ;
   
   /** @var string */
   public $no ;
   
// for integer
    
    /** @var integer */
    public $max ;
    
    /** @var integer */
    public $min ;
    
    /** @var integer */
    public $rounding_mode ;
    
    /** @var integer */
    public $grouping ;

// for percent
    
    /** @var string */
    public $real_type ;
    
    /** @var integer */
    public $precision ;

// for money
    
    /** @var string */
    public $currency ;
    
    /** @var integer */
    public $divisor ;
    
// for choice 
    
    /** @var mixed */
    public $show_on ;
    
    /** @var bool */
    public $multiple ;
    
    /** @var bool */
    public $expanded ;
    
    /** @var mixed */
    public $empty_value ;
    
    /** @var mixed */
    public $empty_data ;
    
    /** @var mixed */
    public $preferred_choices ;
    
    /** @var string */
    public $choice_code ;
    
    /** @var mixed */
    public $choices ;
    
    /** @var bool */
    public $by_reference ;
    
    /** @var bool */
    public $virtual ;
    
// for range
    /** @var string */
    public $unit ;
    
    /** @var string */
    public $icon ;
    
// for entity

    /** @var string */
    public $group_by ;
    
// for embed form
    
    /** @var array */
    public $copy_properties ;

}
