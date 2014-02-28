<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("apppassword", guess="password,passwd")
 */
class Password extends Text {
    
    public $view = false ;
    
    public $max_length = 32 ;
    public $min_length = 4 ;
    
    public $always_empty ;
    
    public $required = false ;
    
    public $salt = 'salt' ;
    
    public $real_password = 'password' ;
    
    public function getFormOptions() {
        
        $options   = parent::getFormOptions() ;
        
        if( 0 ) {
            $writer = new \App\AdminBundle\Compiler\Generator\PhpWriter();
            $writer
               ->writeln('new \Symfony\Component\Security\Core\Validator\Constraints\UserPassword(array(')  
               ->indent()
                    // ->writeln( ) 
               ->outdent() 
               ->writeln('))')
               ;
            $options['constraints'][]   = $this->compilePhpCode( $writer->getContent() ) ;
            
            
            if( null !== $this->repeat ) {
                $options['type']    = 'password' ;
                //$options['invalid_message']    =  'The password fields must match.',

                $options['first_options']    = array(
                    'label' => $_options['label'] ,
                    'required'  => $_options['required'] ,
                 );

                $options['second_options']    = array(
                    'label_render' => false ,
                    'required'  => $_options['required'] ,
                );
            }
            
        }
        
        if( !property_exists($this->admin_object->class_name, $this->salt) ) {
            $this->throwError("salt property(%s), not exist in class(%s)", $this->salt ,  $this->admin_object->class_name );
        }
        if( !property_exists($this->admin_object->class_name, $this->real_password) ) {
            $this->throwError("real_password property(%s), not exist in class(%s)", $this->real_password ,  $this->admin_object->class_name );
        }
        if( $this->class_property === $this->real_password ) {
            $this->throwError("real_password property(%s) can not be it self, class(%s)", $this->real_password ,  $this->admin_object->class_name );
        }
        if( $this->class_property === $this->salt ) {
            $this->throwError("salt property(%s) can not be it self, class(%s)", $this->salt ,  $this->admin_object->class_name );
        }
        
        $options['salt_property']   = $this->salt ;
        $options['password_property']   = $this->real_password ;
        
        $options['required']   = $this->compilePhpCode('$action->isCreateAction()') ;
        
        return  $options  ;
    }
    
}