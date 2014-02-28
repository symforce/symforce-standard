<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appfile", map="App\AdminBundle\Entity\File")
 */
class File extends Element {
    
    public $required = false ;
    
    public $max_size = 819600 ; // 800k
  
    public $extentions = array() ;
    
    public function set_extentions( $extentions ) {
         if( !is_array($extentions ) ) {
            $extentions    = preg_split('/\s*\,\s*/', preg_replace('/^\s+|\s+$/', '', strtolower($extentions) ) );
         } 
         foreach($extentions as $_ext ) {
            if(preg_match('/\W/', $_ext) ) {
                throw new \Exception( sprintf("`extention:%s` is invalid", $_ext )); 
            }
         }
         $this->extentions  = $extentions ;
    }
    
    public function set_max_size( $size ) {
        if( preg_match('/^(\d+)(|b|k|m)$/', strtolower($size) , $_m ) ) {
            if( 'k' === $_m[2] ) {
                $this->max_size = 1024 * $_m[1] ;
            } else if ('m' === $_m[2] ){
                $this->max_size = 1024 * 1024 *  $_m[1] ;
            } else {
                $this->max_size = (int) $_m[1] ;
            }
        } else {
            throw new InvalidValueException( sprintf( " max_size should like 1, 1b, 1k, 1m, not `%s` ", $size)) ;
        }
    }
    
    public function getFormOptions() {
        
        $admin_class    = $this->admin_object->getCompileClass() ;
        
        $admin_class->addLazyArray( 'form_elements',  $this->class_property ,  array(
            'ext'   => $this->extentions ,
            'max'   => $this->max_size ,
        ) ) ;
        
        $this->admin_object->generator->setDoctrineConfig( $this->admin_object->class_name , 'file', $this->class_property ); 
        
        $options    = parent::getFormOptions() ; 
        
        $options['admin_class']  = $this->admin_object->class_name ;
        $options['admin_property']  = $this->class_property ;
        $options['admin_name']  = $this->admin_object->name ; 
        $options['admin_id']  = $this->admin_object->property_id_name ;
        
        $options['required']  = false ;
        
        $options['accept_file_type']  = join('|', $this->extentions ) ;
        $options['max_file_size']   = $this->max_size ;
        
        return $options ;
    }
    
    
}