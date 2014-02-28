<?php

namespace App\AdminBundle\Compiler ;

use Doctrine\Bundle\DoctrineBundle\Registry ;
use App\AdminBundle\Compiler\Generator ;

/**
 *
 * @author loong
 */
class MetaFormFactory {
    
    /**
     * @var Registry 
     */
    private $doctrine ;
    
    /**
     * @var array
     */
    private $default_type   = array() ;
    
    /**
     * @var array
     */
    private $types  = array() ;
    
    /**
     * @var array
     */
    private $guess  = array() ;

    
    /**
     * @var Generator 
     */
    private $gen ;
    
    static private function readFormType(\Doctrine\Common\Annotations\Reader $reader, \ReflectionClass $rc, array & $out, array & $non_deep_fields ) {
        $as = $reader->getClassAnnotations($rc) ; 
        $annot  = null ;
        if( $as) foreach($as as $_annot ) {
            if( $_annot instanceof \App\AdminBundle\Compiler\Annotation\FormType ) {
                if( null !== $annot ) {
                    throw new \Exception(sprintf("app_admin.form.type ( name: %s -> class: %s ) has multi annotation", $name, $class_name));
                }
                $annot  = $_annot ;
            }
        }
        if( $annot ) {
            foreach($annot as $key => $value ) {
                if( !isset($out[$key]) || null === $out[$key] ) {
                    if( !$out['deep'] || !in_array($key, $non_deep_fields)) {
                        $out[$key]  = $value ;
                    }
                }
            }
        }
        $parent = $rc->getParentClass() ;
        if( $parent && !$parent->isAbstract() ) {
            $out['deep']++ ;
            self::readFormType( $reader, $parent, $out, $non_deep_fields ) ;
        }
    }
    
    static private function deep_sort(array & $a, array & $b ) {
        if( $a['deep'] === $b['deep'] ) {
            return $a['default'] < $b['default'] ;
        }
        return $a['deep'] > $b['deep'] ;
    }


    public function __construct(Registry $doctrine, \Doctrine\Common\Annotations\Reader $reader, array $config_types ) {
        $this->doctrine = $doctrine ;
        
        $keyword_guess  = array() ;
        $config = array() ;
        
        $non_deep_fields    = array( 'default' ) ;
        
        foreach ($config_types as $name => $class_name ) {
            if( !class_exists($class_name) ) {
                throw new \Exception(sprintf("app_admin.form.type ( name: %s -> class: %s ) not exists", $name, $class_name));
            }
            $rc = new \ReflectionClass( $class_name ) ;
            
            $o  = array(
                'name'  => $name ,
                'class'  => $class_name ,
                'deep'  => 0 ,
                'type'  => null ,
                'orm'   => null ,
                'map'   => null ,
                'guess'   => null ,
                'default'  => null ,
            ) ;
            self::readFormType($reader, $rc, $o, $non_deep_fields ) ;
            if( null === $o['type'] ) {
                $o['type']  = $name ;
            }
            
            if( !$o['orm'] && !$o['map']) {
                throw new \Exception(sprintf("app_admin.form.type ( name: %s -> class: %s ) no orm and map", $name, $class_name));
            } else if( $o['orm'] && $o['map']) {
                throw new \Exception(sprintf("app_admin.form.type ( name: %s -> class: %s ) set both orm and map", $name, $class_name));
            }
            
            if( $o['orm'] ) {
                $o['orm']   = explode(' ', strtolower( trim(preg_replace('/\W+/', ' ', $o['orm']))) );
                if( in_array('string', $o['orm']) ) {
                    if( null !==  $o['guess'] ) {
                        if( true === $o['guess']  ) {
                            $o['guess']  = array($name) ;
                        } else if ( !is_array( $o['guess']) ) {
                            $o['guess']  = preg_split('/\s*\,\s*/', trim($o['guess']) ) ;
                        }
                    }
                } else {
                   if( null !==  $o['guess'] ) {
                       throw new \Exception(sprintf("app_admin.form.type ( name: %s -> class: %s ) guess only support orm:string", $name, $class_name));
                   }
                }
            } 
            
            if( is_string($o['default']) ) {
                if( isset($this->default_type[ $o['default'] ]) ) {
                    throw new \Exception(sprintf("app_admin.form.type %s and %s has same default orm type:%s", $this->default_type[ $o['default'] ], $name, $o['default'] ));
                }
                $this->default_type[ $o['default'] ] = $name  ;
            }
            
            $config[] = $o ;
        }
        
        usort( $config, array(__CLASS__, 'deep_sort')); 
        
        foreach($config as $o ) {
            $name   = $o['name'] ;
            if($o['guess']) foreach($o['guess'] as $keyword ) {
                if( !isset($this->guess [$keyword]) ) {
                    $this->guess [$keyword] = $name ;
                }
            }  
            unset($o['name']);
            unset($o['guess']);
            unset($o['deep']);
            unset($o['default']);
            $this->types[ $name ] = $o ;
        } 
        // var_dump($this->types); exit;
    }
    
    public function setGenerator(Generator $gen) {
        $this->gen = $gen ;
    }

    public function create($class_name, $property_name, \App\AdminBundle\Compiler\Annotation\Form $annot, \App\AdminBundle\Compiler\MetaType\PropertyContainer $parent, \App\AdminBundle\Compiler\MetaType\Admin\Entity $entity = null ) {
        $om     = $this->doctrine->getManagerForClass( $class_name ) ;
        if( !$om ) {
            throw new \Exception(sprintf("%s->%s has no orm", $class_name, $property_name));
        }
        $meta   = $om->getClassMetadata( $class_name ) ;
        if( ! $meta ) {
            throw new \Exception(sprintf("%s->%s has no orm", $class_name, $property_name));
        }
        $map    = null ;
        if( $meta->hasAssociation( $property_name ) ) {
            $map = $meta->getAssociationMapping( $property_name ) ;
        }
        $orm_type   = $meta->getTypeOfField( $property_name ) ;
        $form_type  = $annot->type ;
      
        if( $entity ) {
            if( $entity->class_name !== $class_name ) {
                throw new \Exception(sprintf("%s->%s not match admin(%s) ", $class_name, $property_name, $entity->admin_name )) ;
            }
        } else {
            if( !$this->gen->hasAdminClass($class_name) ) {
                throw new \Exception(sprintf("%s->%s not find admin", $class_name, $property_name )) ;
            }
            $entity = $this->gen->getAdminByClass($class_name) ;
        }
        
        if( $form_type ) {
            if( 'workflow' === $form_type ) {
                if( !$entity->workflow ) {
                    throw new \Exception(sprintf("%s->%s use form type:%s without define workflow", $class_name, $property_name, $form_type));
                } else if ( $entity->workflow->property !== $property_name ) {
                    throw new \Exception(sprintf("%s->%s use form type:%s, but workflow property is %s", $class_name, $property_name, $form_type, $entity->workflow->property ));
                }
            }
            if( 'owner' === $form_type ) {
                if( !$entity->owner ) {
                    throw new \Exception(sprintf("%s->%s use form type:%s without define owner", $class_name, $property_name, $form_type));
                } else if ( $entity->owner->owner_property !== $property_name ) {
                    throw new \Exception(sprintf("%s->%s use form type:%s, but owner property is %s", $class_name, $property_name, $form_type, $entity->owner->owner_property ));
                }
            }
            if( $map ) {
                if( $map['targetEntity'] !== $this->types[$form_type]['map'] && '*' !== $this->types[$form_type]['map'] ) {
                    if( $this->types[$form_type]['map'] ) {
                        throw new \Exception(sprintf("%s->%s use form type:%s with orm map type:%s, form type not accept orm map", $class_name, $property_name, $form_type, $map['targetEntity'] ));
                    } else {
                        throw new \Exception(sprintf("%s->%s use form type:%s with orm map type:%s, only accept orm map:(%s)", $class_name, $property_name, $form_type, $map['targetEntity'], $this->types[$form_type]['map'] ));
                    }
                }
            } else{
                if( $orm_type && !in_array($orm_type, $this->types[$form_type]['orm']) ){
                    throw new \Exception(sprintf("%s->%s use form type:%s with orm type:%s, only accept orm:(%s)", $class_name, $property_name, $form_type, $orm_type,  join(',', $this->types[$form_type]['orm'] ) ));
                }
            }
        } else {
            
            if( $entity->workflow && $entity->workflow->property === $property_name) {
                $form_type  = 'workflow' ;
            } else if ( $entity->owner && $entity->owner->owner_property  === $property_name ) {
                $form_type  = 'owner' ;
            }
            
            if( !$form_type ) {
                if( $map ) {
                    // $map['targetEntity'] 
                    foreach($this->types as $_type_name => $_type ) {
                        if( $map['targetEntity']  === $_type['map'] ) {
                            $form_type  = $_type_name ;
                            break ;
                        }
                    }
                    if( !$form_type ) {
                        // check if it is embed type
                        $cache  = $this->gen->getAnnotationCache( $class_name ) ;
                        if( isset($cache->propertie_annotations[ $property_name ]['properties'] ) ) {
                            $form_type  = 'embed' ;
                        } else {
                            $form_type  = 'entity' ;
                        }
                    }
                } else {
                    if( 'string' ===  $orm_type ) {
                        foreach($this->guess as  $keyword => $_type) {
                            if( false !== strpos($property_name , $keyword) ) {
                                $form_type  = $_type ;
                                break ;
                            }
                        }
                    } else {
                        if( isset($this->default_type[$orm_type]) )  {
                            $form_type  = $this->default_type[$orm_type] ;
                        }
                        foreach($this->types as $_type_name => $_type ) {
                            if(  !$_type['map'] && in_array($orm_type, $_type['orm']) ){
                                $form_type  = $_type_name ;
                                break ;
                            }
                        }
                    }
                    
                    if( ! $form_type ) {
                        $form_type  = 'text' ;
                    }
                }
            }
            
        }

        if( ! isset($this->types[ $form_type ]['class']) ) {
            throw new \Exception(sprintf("%s->%s with form(%s) no class ", $class_name, $property_name, $form_type )) ;
        }
        
        $form_class = $this->types[ $form_type ]['class'] ;
        /*
        if( !($form_class instanceof \App\AdminBundle\Compiler\MetaType\Form\Element) ) {
            throw new \Exception(sprintf("`%s` is invalid ", $form_class));
        }*/
        $form_element  = new $form_class( $parent, $entity, $property_name , $annot ) ; 
        $form_element->compile_form_type    = $this->types[ $form_type ]['type'] ;
        $form_element->compile_orm_type    = $orm_type ; 
        
        if( $meta->hasField( $property_name) ) {
            if( $meta->isUniqueField( $property_name ) ) {
                if( null === $annot->unique ) {
                    $form_element->unique = true ;
                }
            }
        }
        
        if( $form_element->unique ) {
            $cache  = $this->gen->getAnnotationCache( $class_name ) ;
            if( isset($cache->propertie_annotations[$property_name]) ) {
                // $cache->propertie_annotations[$property_name]['Gedmo\Mapping\Annotation\Translatable']
                if( isset( $cache->propertie_annotations[$property_name]['Gedmo\Mapping\Annotation\Slug'] ) ) {
                    if( null === $annot->unique ) {
                        $form_element->unique = false ;
                    }
                    if( null === $annot->required ) {
                        $form_element->required = false ;
                    }
                }
            }
        }
        
        return $form_element ;
    }
}

