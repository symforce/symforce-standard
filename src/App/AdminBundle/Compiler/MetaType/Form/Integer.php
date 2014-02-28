<?php

namespace App\AdminBundle\Compiler\MetaType\Form;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(orm="integer,bigint,smallint", default=true )
 */
class Integer extends Element {
    
    public $max ;
    public $min ;
    
    public $rounding_mode ;
    
    public $grouping ;
    
    /** @var string */
    public $greater_than ;
    
    /** @var string */
    public $less_than ;
    
    public function set_max( $value ){
        $this->max  = (int) $value ;
        if( null !== $this->min ) {
            if( $this->min >= $this->max ) {
                $this->throwError( " min:%s is bigger than max:%s", $this->min, $this->max );
            }
        }
    }
    
    public function set_min( $value ){
        $this->min = (int) $value ;
        if( null !== $this->max ) {
            if( $this->min >= $this->max ) {
                $this->throwError( " min:%s is bigger than max:%s", $this->min, $this->max );
            }
        }
    }
    
    public function getFormOptions(){
        $options    =  parent::getFormOptions() ; 
        
        if( null !== $this->min || null !== $this->max ) {
            $max    = $this->max ?: 0x7fffff ;
            $min    = $this->min ?: 0 ;
            
            
            $writer = new \App\AdminBundle\Compiler\Generator\PhpWriter();
            $writer
               ->writeln('new \Symfony\Component\Validator\Constraints\Range(array(')  
               ->indent()
                   ->writeln( sprintf(' "min" => %d, ',  $min )) 
                   ->writeln( sprintf(' "max" => %d, ',  $max )) 
               ->outdent() 
               ->writeln('))')
               ;
            $options['constraints'][]   = $this->compilePhpCode( $writer->getContent() ) ;
            
            $options['attr']['min'] = $min ;
            $options['attr']['max'] = $max ;
        }
        
        if( $this->greater_than || $this->less_than ) {
            $writer = new \App\AdminBundle\Compiler\Generator\PhpWriter();
            $writer
               ->writeln('new \App\AdminBundle\Form\Constraints\CompareValidator(array(')  
               ->indent() ;
             if( $this->greater_than ) {
                 $writer->writeln( sprintf(' "greater_than" => %s, ', var_export($this->greater_than, 1) ))  ;
             }
             if( $this->less_than ) {
                 $writer->writeln( sprintf(' "less_than" => %s, ', var_export($this->less_than, 1) ))  ;
             }
             if( $this instanceof Range ) {
                 if( $this->_unit ) {
                     $writer->writeln( sprintf(' "unit" => %s, ', var_export( array( $this->_unit->getPath(), $this->_unit->getDomain() ), 1) ))  ;
                 }
             }
             $writer
                ->outdent() 
                ->writeln('), $this)')
               ;
            $options['subscribers'][]   = $this->compilePhpCode( $writer->getContent() ) ;
        }
        
        return $options ;
    }
    
}