<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(orm="string", default=true )
 */
class Text extends Element {
    
    public $width ;
    
    public $max_length  = 255 ;
    public $min_length ;
    
    public $trim ;

    static public function getSupportedDoctrineType(){
        return array( 'string' ) ;
    }
    
    public function set_width( $value ) {
        // todo check
        $this->width = $value ;
    }
    
    public function set_max_length( $value ){
        $this->max_length  = (int) $value ;
        if( null !== $this->min ) {
            if( $this->min_length >= $thix->max_length ) {
                $this->throwError(" min:%s is bigger than max:%s", $this->min_length, $this->max_length );
            }
        }
    }
    
    public function set_length( $value ){
        $this->min_length = (int) $value ;
        if( null !== $this->max_length ) {
            if( $this->min_length >= $thix->max_length ) {
                $this->throwError(" min:%s is bigger than max:%s", $this->min_length, $this->max_length );
            }
        }
    }
    
   
    public function getFormOptions(){
        $options    = parent::getFormOptions() ;
        
        if( null !== $this->trim ) {
            $options['trim']    = $this->trim ;
        }
        
        if( null !== $this->min_length || null !== $this->max_length ) {
            $max    = $this->max_length ?: 0xff ;
            $min    = $this->min_length ?: 0 ;
            
            $writer = new \App\AdminBundle\Compiler\Generator\PhpWriter();
            $writer
               ->writeln('new \Symfony\Component\Validator\Constraints\Length(array(')  
               ->indent()
                   ->writeln( sprintf(' "min" => %d, ',  $min )) 
                   ->writeln( sprintf(' "max" => %d, ',  $max )) 
               ->outdent() 
               ->writeln('))')
               ;
            $options['constraints'][]   = $this->compilePhpCode( $writer->getContent() ) ;
        }
        return $options ;
    }
    
}