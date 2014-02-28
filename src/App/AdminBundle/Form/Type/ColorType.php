<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType ;

// http://github.com/xaguilars/bootstrap-colorpicker

class ColorType extends TextType {
    
    public function getName(){
        return 'appcolor' ;
    }
    
    public function getExtendedType()
    {
        return 'text';
    }
}
