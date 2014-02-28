<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

use App\AdminBundle\Compiler\MetaType\Form ;

class ViewProperty extends ActionProperty {
    
    public function __construct(\App\AdminBundle\Compiler\MetaType\PropertyContainer $property_container, \App\AdminBundle\Compiler\MetaType\Admin\Entity $entity, $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot = null ) {
        parent::__construct($property_container, $entity, $property, $annot ) ;
        $this->checkAnnotChange() ;
    }
    
    protected function checkAnnotChange() {
        if( !empty($this->_annot_properties) ) {
            $ignor_properties   = array( 'property', 'position', 'label' );
            foreach($this->_annot_properties as $property_name => $changed ) {
                if( !in_array($property_name, $ignor_properties) ) {
                    $this->_annot  = true ;
                    break ;
                }
            }
            
            $ignor_label_properties   = array( 'label' );
            foreach($ignor_label_properties as $property_name ) {
                if( isset( $this->_annot_properties[$property_name]) ) {
                    $this->_annot_label   = true ;
                    break ;
                }
            }
        }
    }

    /**
     *  for date|datetime|time
     * @var string
     */
    public $format ;
    
    /**
     * @var Html\Tag 
     */
    public $tag ;
    
    public $_annot  = false ;
    public $_annot_label  = false ;
    
    public function set_tag( $annot ) {
        $this->tag   = new Html\Tag($annot) ;
    }
    
    /**
     * @var Html\Href 
     */
    public $href ;
    public function set_href( $annot ) {
        $this->href = new Html\Href($annot) ;
    }
    
    private $_compile_label ;
    public function compileLabel() {
        if( null !== $this->_compile_label ) {
            return $this->_compile_label ;
        }
        if( !$this->label || !is_object($this->label) ) {
            throw new \Exception( sprintf("%s->%s, %s->lable not set yet: %s ", 
                    $this->parent_object->admin_object->class_name , $this->class_property, 
                    $this->parent_object->getMeteTypeName(), 
                     json_encode($this->label))) ;
        }
        
        $this->_compile_label   = $this->label->getNakeTwigCode() ;
        
        return $this->_compile_label ;
    }

    private $_compile_value ;
    public function compileValue() {
        if( null !== $this->_compile_value ) {
            return $this->_compile_value ;
        }
        
        $r  = $this->admin_object->reflection->getProperty( $this->class_property ) ;
        $property_name  =  $this->class_property ;
        
        $property_code  = '_property_value' ;
        
        if( $this->template ) {
            try{
                $this->admin_object->generator->loadTwigTemplatePath( $this->template ) ;
            } catch ( \InvalidArgumentException $e) {
                throw  $e;
                $this->setChainException( $e ) ;
                $this->throwError( " %s->%s use  template:`%s` not found",  $this->admin_name, $this->class_property , $this->template );
            }
            $code   = '{% include "' .  $this->template . '" with { admin: admin, action: action, property:' . json_encode( $this->class_property  ) .', object: _object, value:attribute(_object, "' . $this->class_property . '") } %}' ;
        }  else if( $this->code ) {
            $code    = $this->code ;
        } else if( $this->admin_object->workflow && $this->class_property === $this->admin_object->workflow->property ) {
            $code    = '{{ admin.getObjectWorkflowLabel(_object) }}' ;
        } else if( $this->admin_object->owner && $this->class_property === $this->admin_object->owner->owner_property ) {
            // $code   = '{{ admin.owneradmin.string( attribute(_object, "' . $this->class_property . '") ) }}' ;
            $map    = $this->getPropertyDoctrineAssociationMapping() ;
             $code   = $this->getMapValueCode( $property_code, $map ) ;
        } else {
            $code   = '{{ ' . $property_code . ' }}' ;

            if( $this->form_element ) {
                if( $this->form_element instanceof Form\Integer ) {
                    if( $this->form_element instanceof Form\Range ) {
                        $code   =  $property_code ;
                        if( null !== $this->form_element->precision ) {
                            $code .= sprintf('|number_format(%d, ".", ",")', $this->form_element->precision ) ;
                        }
                        $code   = '<span class="number">{{ ' . $code . ' }}</span>' ;
                        if( null !== $this->form_element->_unit ) {
                            $code .=   $this->form_element->_unit->getTwigCode() ;
                        }
                    } else if( $this->form_element instanceof Form\Money ) {
                        $code   = '{{ ' . sprintf('app_money(%s, %d, "%s")', $property_code, $this->form_element->precision, $this->form_element->currency ) . ' }}' ;
                    } else if( $this->form_element instanceof Form\Percent ) {
                        $code   = '{{ ' . sprintf('%s|number_format(%d, ".", ",")', $property_code, $this->form_element->precision ) . ' }}' ;
                    } else {
                        $code   = '{{ ' . sprintf('%s|number_format(0, ".", ",")', $property_code ) . ' }}' ;
                    }
                } else if( $this->form_element instanceof Form\Choice ) {
                    $code   = '{{ admin.getChoiceText("' . $this->class_property . '", _object) }}' ;
                    if( $this->form_element instanceof Form\Owner ) {
                        $code   = '{{ ' . $property_code . ' }}' ;
                    } else if( $this->form_element instanceof Form\Entity ) {
                        $map    = $this->getPropertyDoctrineAssociationMapping() ;
                        $code   = $this->getMapValueCode( $property_code, $map, $action = 'view' );
                    } else if( $this->form_element instanceof Form\Checkbox ) {
                        
                    } else if( $this->form_element instanceof Form\Radio ) {

                    } else if( $this->form_element instanceof Form\Bool ) {
                        
                    } else if( $this->form_element instanceof Form\Country ) {

                    } else {
                        if( $this->form_element->multiple ) {
                            $code   = '{{ admin.getChoicesText("' . $this->class_property . '", _object) }}' ;
                        }
                    }
                } else if( $this->form_element instanceof Form\DateTime ) {
                    $format = $this->format ?:  $this->form_element->format ;
                    $code   = '{{ app_date_format(' . $property_code . ', "' . $format. '" ) }}' ;
                    if( $this->form_element instanceof Form\Birthday ) {

                    } 
                } else if( $this->form_element instanceof Form\File ) {
                    if( $this->form_element instanceof Form\Image ) {
                        // $this->form_element->image_size
                        $code   = '{% if ' . $property_code . ' %}' . sprintf('<div class="app_image_view" style="width:%spx;height:%spx;"><img src="{{ %s }}" /></div>', $this->form_element->image_size[0],$this->form_element->image_size[1], $property_code) .'{% endif %}' ;
                        
                    } 
                } else if( $this->form_element instanceof Form\Embed ) {
                    $map    = $this->getPropertyDoctrineAssociationMapping() ;
                    $code   = $this->getMapValueCode( $property_code, $map );
                } else if( $this->form_element instanceof Form\Text ) {
                    if( $this->form_element instanceof Form\Textarea ) {
                        if( $this->form_element instanceof Form\Html ) {
                            $code   = '{{ ' . $property_code . ' | raw }}' ;
                        } else if( $this->form_element instanceof Form\Markdown ) {

                        } 
                    } else if( $this->form_element instanceof Form\Color ) {

                    } else if( $this->form_element instanceof Form\Url ) {

                    } else if( $this->form_element instanceof Form\Email  ) {

                    } else if( $this->form_element instanceof Form\Password  ) {
                        $code   = '******' ;
                    } else {
                        
                        if( $this->admin_object->property_value_name === $this->class_property ) {
                            // add view to this action
                            $url   = '{{ admin.path("view", _object) }}' ; 
                            $code  = '{% if admin.auth("view", _object) %}' ;
                                $code .= sprintf('<a href="%s">{{ %s }}</a>', $url, $property_code ) ;
                            $code .= '{% else %}' ;
                                $code .= sprintf('{{ %s }}', $property_code ) ;
                            $code .= '{% endif %}' ;
                        }
                    }
                } else if( $this->form_element instanceof \App\UserBundle\Form\MetaType\AuthorizeMetaType ) {
                    $code   = '*' ;
                } else {
                    \Dev::debug($this->admin_object->name, $this->class_property, get_class($this->form_element) );
                }
            } else {
                // check form type 
                
                /**
                 * @var \Doctrine\ORM\Mapping\ClassMetadata 
                 */
                $meta       = $this->getEntityMetadata() ;
                
                $map        = $this->getPropertyDoctrineAssociationMapping() ;
                if( $map ) {
                    $target_class   = $map['targetEntity'] ;
                    // to_one 
                    if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE ) {
                        // find parent route 
                        $code   = $this->getMapValueCode( $property_code, $map );
                    } else if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY ) {
                        // find child route
                        $child_admin_object    = $this->admin_object->generator->getAdminByClass( $target_class ) ;
                        
                        $url   = sprintf('{{ admin.childpath(_object, "%s", "%s", "list", %s ) }}', $this->class_property, $child_admin_object->name, $property_code ) ;
                       
                        $text   = $child_admin_object->label->getTwigCode() ;
                        
                        $code  = '{% if ' . sprintf('app_auth("%s", "list")', $child_admin_object->name). ' %}' ;
                            $code .= sprintf('<a href="%s">%s ({{ %s.count() }})</a>', $url, $text, $property_code ) ;
                        $code .= '{% else %}' ;
                            $code .= sprintf('%s ({{ %s.count() }}) ', $text, $property_code ) ;
                        $code .= '{% endif %}' ;
                        
                    } else if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE ) {
                        // find sibling entity
                        $code   = $this->getMapValueCode( $property_code, $map );
                    } else {
                        throw new \Exception('bigger error') ;
                    }
                    
                } else {
                    $orm_type   = $this->getPropertyDoctrineType() ;
                    if( $orm_type ) {
                        if( 'date'  === $orm_type ) {
                            $format = $this->format ?: "Y/m/d" ;
                            $code   = '{{ app_date_format(' . $property_code . ', "' . $format . '" ) }}' ;
                        } else if( 'datetime' === $orm_type) {
                            $format = $this->format ?: "Y/m/d H:i:s" ;
                            $code   = '{{ app_date_format(' . $property_code . ', "' . $format . '" ) }}' ;
                        } else if( 'time'  === $orm_type ) {
                            $format = $this->format ?: "H:i:s" ;
                            $code   = '{{ app_date_format(' . $property_code . ', "' . $format . '" ) }}' ;
                        } else if( 'integer' === $orm_type ) {

                        } else if( 'text' === $orm_type ) {

                        } else if( 'string' === $orm_type ) {

                        } else if( 'boolean' === $orm_type ) {

                        } else if ( 'array' === $orm_type ) {
                            // list foreach array value ?
                            
                        } else {
                            \Dev::debug($this->admin_object->name, $this->class_property, $orm_type );
                        }
                    }  else {
                        // not sure what to do
                        // \Dev::debug($this->admin_object->name, $this->class_property, $orm_type );
                    }
                }

            }
            
            if( $this->tag ) {
                $code = $this->tag->compileTag( $code ) ;
            }
            
            if( $this->href ) {
                // @TODO add link handle 
                $code = $this->href->compileTag( $code ) ;
            }
        }
        
        $this->_compile_value   = $code ;
        return $code ;
    }
    
    
    private function getMapValueCode( $property_code, array $map, $action = 'view' ){
            $target_class   = $map['targetEntity'] ;
            if( !$this->admin_object->generator->hasAdminClass( $target_class ) ) {
                $this->throwError("map to `%s` not admin calss", $target_class);
            }
            $admin_object    = $this->admin_object->generator->getAdminByClass( $target_class ) ;
            
            $text   = $admin_object->label->getTwigCode() ;
            $admin_code = 'app_admin_class(' . var_export($target_class, 1) .')' ;
            $view_code = $admin_code . '.string(' .  $property_code . ') ';
            
            $url    = sprintf('{{ app_admin_path("%s", "%s", %s ) }}', $admin_object->name, $action, $property_code ) ;

            $code   = '{% if ' . $property_code . ' %}' ;
                $code   .= '{% if ' . sprintf('app_auth("%s", "%s", %s )', $admin_object->name, $action, $property_code ). ' %}' ;
                    $code .= sprintf('<a href="%s">{{ %s }}</a>', $url, $view_code ) ;
                $code .= '{% else %}' ;
                    $code .= '{{ ' . $view_code . ' }}' ; // empty value
                $code .= '{% endif %}' ;
            $code .= '{% endif %}' ;
            
            return $code ;
    }
    
    public function getCompileLabelCode() {
        if( $this->_annot_label ) {
            return $this->compileLabel() ;
        } else { 
            return sprintf('admin_macro.macro_label_%s(admin)' , $this->class_property ) ;
        }
    }

    public function getCompileValueCode( $macro_name = 'admin_macro' ) {
        if( $this->_annot ) {
            return $this->compileValue() ;
        } else { 
            return sprintf('{{ %s.macro_value_%s(_object, _property_value, admin) }}', $macro_name, $this->class_property ) ;
        }
    }
    
    private $_twig_value    = array() ;
    public function getTwigValue( $object = '_object' ) {
        if( !isset($this->_twig_value[ $object]) ) {
            $this->_twig_value[ $object]    = $this->admin_object->getPropertyTwigValue( $this->class_property, $object ) ;
        }
        return $this->_twig_value[ $object] ;
    }

    public function compileView(\App\AdminBundle\Compiler\Generator\PhpWriter $twig_writer ){
        $property_name  = $this->class_property ;
        $twig_writer
                    ->writeln( '{% ' . sprintf('if admin.isPropertyVisiable("%s", action, _object ) ', $property_name ) . ' %}' )
                    ->indent()
                        ->writeln( '{% set view_property_count = view_property_count + 1 %}' )
                        ->writeln( '{% set _property_value = ' .  $this->getTwigValue() . ' %}' )
                
                        ->writeln( '{% ' . sprintf('block _admin_%s_view', $property_name ) . ' %}' )
                        ->indent()
                
                        ->writeln('<div class="form-group">')
                        ->indent()
                        ->write( '{% ' . sprintf('block _admin_%s_label', $property_name ) . ' %}' )
                          ->write('<div class="control-label col-xs-3">')
                                ->write( '{{ ' .  $this->getCompileLabelCode() . '}}'  )
                          ->write('</div>')
                        ->writeln( '{% endblock %} ' )

                        ->write( '{% ' . sprintf('block _admin_%s_value', $property_name ) . ' %}' )
                          ->write('<div class="control-value col-xs-9">')
                                ->write( $this->getCompileValueCode()  ) 
                          ->write('</div>')
                        ->writeln( '{% endblock %} ' )
                        ->outdent()
                        ->writeln('</div>')
                
                        ->outdent()
                        ->writeln( '{% endblock %} ' )
                
                    ->outdent()
                    ->writeln( '{% endif %} ' )
                    ->write("\n")
                    ;
    }
    

    public function lazyInitialize(){
        parent::lazyInitialize() ;
        
        if( $this->template || $this->code ) {
            if( $this->tag || $this->href ){
                $this->throwError( "can not set tag,href with tamplate or code" );
            }
        } 
    }
    
    
    public $group ;
    public $postion ;
    public $_no_form_and_view ;
    
    public function set_position( $position ) {
        $this->position = (int) $position ;
    }
    
    public function set_group( $value ) {
        if( preg_match('/[\W]/', $value) ) {
            $this->throwError("group(%s) invalid", $value);
        }
        $this->group =  $value ;
    }
    
}
