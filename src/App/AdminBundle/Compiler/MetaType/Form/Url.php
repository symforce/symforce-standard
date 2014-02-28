<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(guess=true)
 */
class Url extends Text {
    
    public $default_protocol ;
    
    public function set_default_protocol( $value ){
        // @todo check 
        $this->default_protocol = $value ;
    }
    
    public function getFormOptions(){
        $options    = parent::getFormOptions() ;
        if( null !== $this->max_length || null !== $this->max_length ) {
            $max    = $this->max_length ?: 0xff ;
            $min    = $this->min_length ?: 0 ;
            
            $writer = new \App\AdminBundle\Compiler\Generator\PhpWriter();
            $writer
               ->writeln('new \Symfony\Component\Validator\Constraints\Url(array(')  
               ->indent()
                   // ->writeln( sprintf('"protocols" => %s, ',  false )) 
               ->outdent() 
               ->writeln('))')
               ;
            $options['constraints'][]   = $this->compilePhpCode( $writer->getContent() ) ;
            
        }
        return $options ;
    }
    
}