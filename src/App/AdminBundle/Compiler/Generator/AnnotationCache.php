<?php

namespace App\AdminBundle\Compiler\Generator;


use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;

class AnnotationCache {
    
    static private $sql_keys    = array(
        'database' , 'table' , 'view',
        'drop' , 'create' , 'alter' , 'default', 
        'primary', 'foreign', 'key', 'index', 'sequence', 'auto', 'increment', 
        'select' ,  'update' , 'delete', 
        'into', 'as',  'join' , 'from' , 'union' ,  'with', 
        'left', 'right' , 'inner', 
        'where' , 'not', 'in' , 'null' , 'values', 
        'group', 'by', 'having', 'order', 'desc', 'asc', 'limit' ,
    );
    
    public  $class_name ;
    public  $class_annotations = array() ;
    public  $propertie_annotations = array() ;
    public  $method_annotations = array() ;
    
    public function __construct(Reader $reader, ClassMetadata $meta) {
        $reflect    = $meta->getReflectionClass() ;
        $class_name = $reflect->getName() ;
        $this->class_name   = $class_name ;
        
        self::addAnnotation( $reader->getClassAnnotations($reflect), $this->class_annotations, $class_name , true ) ;
        
        foreach ($reflect->getProperties() as $p ) {
            $property_name  = $p->getName() ;
            
            if( in_array(strtolower($property_name), self::$sql_keys ) ) {
                throw new \Exception(sprintf("`%s->%s` use SQL key words as name", $class_name, $property_name)); 
            }
            
            $this->propertie_annotations[ $property_name ] = array() ;
            
            self::addAnnotation( $reader->getPropertyAnnotations($p), $this->propertie_annotations[ $property_name ] , sprintf("%s->%s", $class_name , $property_name) ) ;
        
            if( count($this->propertie_annotations[ $property_name ]) < 1 ) {
                unset( $this->propertie_annotations[ $property_name ] ) ;
            }
        }
        
        /*
        if( $this->class_name === 'App\AdminBundle\Entity\Page' ) {
            \Dev::dump($this, 4 );
        }
         */
    }
    
    static private function addAnnotation(array $annotations , array & $map, $name , $for_class = false ){
        foreach($annotations  as $_annot ) {
                $_type = get_class($_annot) ;
                
                if( !($_annot instanceof \App\AdminBundle\Compiler\Annotation\Annotation ) ) {
                    if( 0 === strpos($_type, 'Doctrine') ) {
                        continue ;
                    }
                    
                    if( isset($map[ $_type ]) ) {
                        if(is_array($map[ $_type ]) ) {
                            $map[ $_type ][] = $_annot ;
                        } else {
                            $map[ $_type ] = array($_annot , $map[ $_type ] );
                        }
                    } else {
                        $map[ $_type ] = $_annot ;
                    }
                    continue ;
                }
                
                if( $_annot instanceof  \App\AdminBundle\Compiler\Annotation\AbstractProperty ) {
                    if( $for_class ) {
                        if( ! $_annot->property ) {
                            var_dump($_annot) ; 
                            throw new \Exception( sprintf("[%s] class annotation [@%s] property is null", $name , $_type ) ) ;
                        } 
                        if( isset($map[ $_type ][ $_annot->property ] ) ) {
                            throw new \Exception( sprintf("[%s] class annotation [@%s] property:%s is duplicate", $name , $_type, $_annot->property  ) ) ;
                        }
                        $map[ $_type ][ $_annot->property ] = $_annot ;
                    } else {
                        if( !$_annot->property ) {
                            $map[ $_type ]  = $_annot ;
                        }  else {
                            if( isset($map['properties'][ $_type ][ $_annot->property ] ) ) {
                                throw new \Exception( sprintf("%s property annotation [@%s] property:%s is duplicate", $name , $_type, $_annot->property  ) ) ;
                            }
                            $map['properties'][ $_type ][ $_annot->property ] = $_annot ;
                        }
                    }
                } else {
                
                    $map_key_property   = $_annot->getArrayKeyProperty() ; 
                    if( $map_key_property ) {
                        if( !$_annot->$map_key_property ) {
                            throw new \Exception( sprintf("[%s] annotation [%s] key:%s is null", $name , $_type, $map_key_property ) ) ;
                        }
                        if( isset($map[ $_type ][ $_annot->$map_key_property ]) ) {
                            throw new \Exception( sprintf("[%s] annotation [%s] has dumplicate key:`%s` value `%s` ", $name , $_type, $map_key_property, $_annot->$map_key_property  ) ) ;
                        }
                        $map[ $_type ][ $_annot->$map_key_property ] = $_annot ;
                    } else {
                        if( isset($map[ $_type ]) ) {
                            throw new \Exception( sprintf("[%s] annotation [%s] should only have one instance", $name , $_type ) ) ;
                        }
                        $map[ $_type ] = $_annot ;
                    }
                }
                
            }
    }
}