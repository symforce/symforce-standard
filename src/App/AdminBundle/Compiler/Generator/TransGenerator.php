<?php

namespace App\AdminBundle\Compiler\Generator ;

use Symfony\Component\Yaml\Yaml;

use App\AdminBundle\Compiler\Generator ;

/**
 * Description of ActionGenerator
 *
 * @author loong
 */
class TransGenerator extends TransGeneratorNode {
    
    /**
     * @var string 
     */
    protected $locale ;
    
    public function __construct( Generator $gen, $domain ) {
        if( !$domain  ){
            throw new \Exception( 'domain can not be null' );
        }
        $this->gen  = $gen ;
        $this->domain   = $domain ;
    }
    
    private function removeEmptyValue( array & $array ){
        foreach($array as $key => & $value ) {
            if( is_array($value) ) {
                if( count($value) ) {
                    $this->removeEmptyValue( $value ) ;
                } else {
                    unset( $array[$key] ) ;
                }
            }
        }
    }
    
    public function flush($gen, $locale, array & $cache ) {
        $path   = $gen->getParameter('kernel.root_dir') . '/Resources/AppAdminBundle/translations/' . $this->domain . '.' . $locale . '.yaml' ;
        $this->removeEmptyValue( $this->data ) ;
        $data   = Yaml::dump( $this->data , 4 ) ;
        $cache[ $path ] = $data ; 
    }
}
