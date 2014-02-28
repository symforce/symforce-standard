<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appimage")
 */
class Image extends File {
    
    public $extentions = array( 'png', 'jpg', 'gif' ) ;
    
    public $image_size = array( 100, 100 ) ;
    
    public $small_size = array( 0, 0 ) ;
    
    public $use_crop   =  true  ;
    
    function set_use_crop( $value ) {
        $this->use_crop = !! $value ;
    }
    
    function set_image_size( $size ) {
        if( preg_match('/^\s*(\d+)\s*[Xx,]\s*(\d+)\s*$/', strtolower($size) , $_m ) ) {
            $this->image_size   = array( (int) $_m[1], (int) $_m[2] );
        } else {
            $this->throwPropertyError('image_size', "format is like 100x100, not `%s`", $size ) ;
        }
    }
    
    function set_small_size( $size ) {
        if( preg_match('/^\s*(\d+)\s*[Xx,]\s*(\d+)\s*$/i', strtolower($size) , $_m ) ) {
            $this->small_size   = array( (int) $_m[1], (int) $_m[2] );
        } else {
            $this->throwPropertyError('small_size', "format is like 10x10, not `%s`", $size ) ;
        }
    }
    
    public function getFormOptions() {
        
        $admin_class    = $this->admin_object->getCompileClass() ;
        $admin_class->addLazyArray( 'form_elements',  $this->class_property ,  array(
            'size'      => $this->image_size , 
            'small'     => $this->small_size , 
        ) ) ;
        
        $options    = parent::getFormOptions() ; 
        // width, height, default
        $options['img_config']   = array(
            $this->image_size ,
            $this->small_size ,
            $this->default , 
            $this->use_crop , 
        );
        
        return $options ;
    }
    
}