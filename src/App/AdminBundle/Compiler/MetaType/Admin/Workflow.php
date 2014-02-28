<?php


namespace App\AdminBundle\Compiler\MetaType\Admin ;

/**
 * Description of StatusCollection
 *
 * @author loong
 */
class Workflow extends EntityAware {
    
    const NO_FILTER = -1 ;
    
    const FLAG_VIEW = 1 ; // &*
    const FLAG_EDIT = 2 ; // $*
    const FLAG_LIST = 4 ; // #*
    const FLAG_AUTH = 8 ; // @*
    const FLAG_HIDE = 16 ; // !*
    
    private static $_internal_status = array(
           'all'        => self::NO_FILTER ,
           'none'        => -0x7fff ,
           'removed'    => 0x7fff ,
       ) ;
    
    private  static $_internal_properties = array(
           'all'        => array( "role", "list" ) ,
           'none'        => array( "role", "properties" ) ,
           'removed'    => array( "role", "source" ) ,
       ) ;

    private static function is_internal_value( $value ) {
        return in_array($value, self::$_internal_status );
    }
    
    /**
     * @var Entity
     */
    public $admin_object ;
    
    /**
     * @var string
     */
    public $property ;
    
    /**
     * @var string
     */
    public $orm_type ;
    
    /**
     * @var string
     */
    public $all ;
    
    /**
     * @var array
     */
    public $children ;
    
    public $properties ;
    
    public $_admin_children_permertions  ;
    public $_parsed_children_permertions  ;

    public function __construct(Entity $admin, \App\AdminBundle\Compiler\Annotation\Workflow $annot ) {
        $this->setAdminObject($admin) ;
        $this->setMyPropertie( $annot ) ;
        
        $children   = $this->children ;
        $this->children = array() ;
        
        
        foreach(self::$_internal_status as $name => $value ) {
            $annot  = array() ;
            if( isset($children[$name]) ) {
                $annot = $children[$name] ;
                unset($children[$name]) ;
                foreach($annot as $_key => $_value ) {
                    if( !in_array($_key, self::$_internal_properties[$name]) ) {
                        $this->throwError("%s not allow property `%s`, only accept(%s) ", $name, $_key, join(',', self::$_internal_properties[$name] ) );
                    }
                }
            }
            $annot['value'] = $value ;
            if( !isset($annot['list']) ) {
                $annot['list']  = false ;
            }
            $this->children[ $name ] = new WorkflowStep( $this, $name, $annot , true ) ;
        }
        
        foreach($children as $key => $value ) {
            if( is_integer($key) && $key >=0 ) {
                $node = new WorkflowStep( $this, null, $value) ;
            } else {
                $node = new WorkflowStep( $this, $key, $value) ;
            }
            if( isset($this->children[ $node->name ] ) ) {
                $this->throwError("status(%s) duplicate", $node->name );
            }
            
            if( null !== $node->value && !$node->is_string_value ) {
                if( self::is_internal_value( $node->value ) ) {
                    $this->throwError("%s value can not set to `%s`", $node->name, $node->value );
                } else if ( $node->value < 0 ) {
                    $this->throwError("%s value can not set to `%s`", $node->name, $node->value );
                }
            }
            
            $this->children[ $node->name ]  = $node ;
        }
        
    }
    
    public function getPropertiesFromAnnot( $name, $value, array $parents = array() ){
        if( empty($value) ) {
            return null ;
        }
        
        $properties = preg_split('/\s*,\s*/', trim($value) ) ;
        $config = array() ;
        $rc = $this->admin_object->reflection ;
        
        foreach($properties as $property) {
            if( empty($property) ) {
                continue ;
            }
            if( !preg_match('/^([^\w\*]*)(\w+|\*)$/', $property, $ls) ) {
                \Dev::dump($properties); exit;
                $this->throwError("properties(%s) is invalid for %s", $property, $name );
            }
            $property   = $ls[2] ;
            
            $flag_value = 0 ;
            $_config    = array() ;
            
            if( preg_match_all('/\S/', $ls[1], $_flags) ) {
                foreach($_flags[0] as $_flag ) {
                    if( isset($_config[$_flag]) ) {
                        $this->throwError("duplicate flag(%s) for property(%s) for %s", $_flag, $property, $name );
                    }
                    if( '#' === $_flag ) {
                        $flag   = self::FLAG_LIST ;
                    } else if( '$' === $_flag ){
                        $flag   = self::FLAG_EDIT ;
                    } else if( '&' === $_flag ){
                        $flag   = self::FLAG_VIEW ;
                    } else if( '@' === $_flag ){
                        $flag   = self::FLAG_AUTH ;
                    } else if( '!' === $_flag ){
                        $flag   = self::FLAG_HIDE ;
                    } else {
                        $this->throwError("unknow flag(%s) for property(%s) for %s", $_flag, $property, $name );
                    }
                    $_config[$_flag]    = $flag ;
                    $flag_value |= $flag ;
                }
            }
            
            if( empty($_config) ) {
                $flag_value = self::FLAG_VIEW ;
            }
            
            if( '*' !== $property ) {
                if( !$rc->hasProperty($property ) ) {
                    $this->throwError("class(%s) do not has property(%s) for %s", $this->admin_object->class_name , $property, $name );
                }
            }
            
            if( isset($config[$property]) ) {
                $this->throwError("duplicate property(%s)  for %s",  $property, $name );
            }
            $config[$property] = $flag_value ;
        } 
        if( isset($config['*']) ) {
            // @fixme me,  check private 
            foreach($rc->getProperties() as $p) {
                if( $p->isStatic() ) {
                    continue ;
                }
                $_p = $p->getName() ;
                if( isset($config[$_p]) ) {
                    continue ;
                }
                if( isset($parents[$_p]) ) {
                    $config[$_p] = $parents[$_p] ;
                } else {
                    $config[$_p] = $config['*'] ;
                }
            }
            unset($config['*']) ;
        } else {
            foreach($parents as $property => $flag ) {
                if( isset($config[$property]) ) {
                    continue ;
                }
                $config[$property]  = $flag ;
            }
        }
        
        foreach($config as $property => $flag ) {
            /*
            if( (self::FLAG_EDIT & $flag) &&  ( self::FLAG_VIEW & $flag ) ) {
                $this->throwError("property(%s) can not use edit($) and view(&) same time on properties(%s)", $property, $value );
            }
            */
            
            if( $flag & self::FLAG_HIDE ) {
                if( self::FLAG_HIDE !== $flag ) {
                    $this->throwError("property(%s) can not use hide(!) with other flag on properties(%s)", $property , $value );
                }
            }
            if(  $flag & self::FLAG_AUTH ) {
                if ( self::FLAG_AUTH !== $flag) {
                    $this->throwError("property(%s) can not use auth(@) with other flag on properties(%s)", $property , $value );
                }
            }
        }
        
        return $config ;
    }
    
    /**
     * @param array $config
     * @return array
     */
    public function fixProperties( array $config = array() ){
        foreach($config as $property => $flag ) {
            if( $flag & self::FLAG_HIDE ) {
                if( self::FLAG_HIDE !== $flag ) {
                    $this->throwError("property(%s) can not use hide(!) with other flag on properties(%s)", $property , $value );
                }
                unset( $config[$property] );
            }
        }
        return $config ;
    }
    
    protected function set_permertions($value) {
        if( !is_array($value) ) {
            $this->throwError("children must be array");
        }
        foreach($value as $child_admin => $actions ) {
            if( !is_array($actions) ) {
                $this->throwError("children.action must be array");
            }
            foreach($actions as $action_name => $status ) {
                if( !is_string($status) ) {
                    $this->throwError("children.action.configu must string");
                }
            }
        }
        $this->_admin_children_permertions  = $value ;
    }
    protected function set_properties($value){
        $this->properties   = $this->getPropertiesFromAnnot( '@default',  $value ) ;
    }

    protected function set_status( $values ){
        if( !is_array( $values ) ) {
            $this->throwError("status must be array");
        }
        $this->children = $values ;
    }
    
    protected function set_property( $property ) {
        if( !is_string($property) ) {
            $this->throwError("property must be string") ;
        }
        if( !property_exists( $this->admin_object->class_name , $property)){
            $this->throwError("%s->%s not exists", $this->admin_object->class_name , $property ) ;
        }
        if( !$this->admin_object->isFieldProperty($property) ) {
            $this->throwError("property `%s` is not orm type", $property);
        }
        $type = $this->admin_object->getPropertyDoctrineType($property) ;
        if( $type !== 'string' && $type !== 'integer' ) {
            $this->throwError("property `%s` can not use orm type(%s)", $property, $type);
        }
        $this->orm_type = $type ;
        $this->property = $property ;
    }


    private $lazy_initialized ;
    public function lazyInitialize() {
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        if( !$this->property ) {
            $this->throwError("need status property") ;
        }
        if( count($this->children) < 4 ) {
            $this->throwError("user defined status is empty") ;
        }
        
        $this->tr_node  = $this->admin_object->tr_node->getNodeByPath('status') ;
        
        if( 'integer' === $this->orm_type ) {
            $index  = 0 ;
            $exists = array() ;
            foreach($this->children as $name => $node ) {
                if( $node->is_string_value  ) {
                    $this->throwError("integer property(%s) conflict with status(%s=%s)", $this->property, $name, $node->value);
                }
                if( $node->isInternal() || null === $node->value ) {
                    continue ;
                }
                if( isset($exists[ $node->value ]) ) {
                    $this->throwError("integer property(%s) value(%s) conflict with status(%s=%s)", $this->property, $node->value, $name, $exists[$node->value] ) ;
                }
                $exists[ $node->value ] = $name ;
                if( $node->value >= $index ) { 
                    $index  = $node->value+ 1 ;
                }
            }
            foreach($this->children as $name => $node ) {
                if( null === $node->value ) {
                    while( self::is_internal_value($index) ) {
                        $index++ ;
                    }
                    $node->set_value( $index ) ;
                    $index++ ;
                }
            }
        } else {
            foreach($this->children as $name => $node ) {
                if( null === $node->value ) {
                    $node->set_value( $name ) ;
                }
            }
        }
        
        foreach($this->children as $name => $node ) {
            $node->lazyInitialize() ;
        }
        
    }
    
    public function childrenInitialize() {
        $permertions = $this->parseChildrenPermertions() ;
        if( $permertions ) {
            foreach($permertions as $child_admin_name => $config ) {
                if( $child_admin_name === $this->admin_object->name ) {
                    continue ;
                }
                $visited = array() ;
                $path   = array() ;
                if( ! $this->admin_object->_route_assoc->hasChildPath($child_admin_name, $path, $visited) ) {
                    throw new \Exception(sprintf("admin(%s) use child admin(%s) not find on path", $this->admin_object->name , $child_admin_name )) ;;
                }
                $parent_admin = $this->admin_object ;
                
                foreach($path as $_child_admin_name) {
                    $_child_admin = $this->admin_object->generator->getAdminByName( $_child_admin_name ) ;
                    $find_orm_map = false ;
                    foreach($_child_admin->_orm_map as $child_property => $map) {
                        if( $map[1] === $parent_admin->name ) {
                            $find_orm_map = true ;
                            $_child_admin->_auth_parents[ $child_property ] = $parent_admin->name ;
                        }
                    }
                    if( !$find_orm_map ) {
                        // maybe in child other parent , we should find that parent and put it to child->_auth_parents ?
                        throw new \Exception(sprintf("admin(%s) use child admin(%s) not find on orm map", $parent_admin->name , $_child_admin_name )) ;
                    }
                    $parent_admin   = $_child_admin ;
                } 
            }
            $this->_parsed_children_permertions = $permertions ;
        }
    }

    public function compile(){
        
        foreach($this->children as $name => $node ) {
            $node->compile() ;
        }
        
        $admin_class    = $this->admin_object->getCompileClass() ;
        
        $status = array() ;
        $map    = array() ;
        foreach($this->children as $name => $node ) {
            $status[$name] = $node->getCompileValue() ;
            $map[ $node->value ] = $name ;
        }
        
        $config = array(
            'property'  => $this->property ,
            'type'  => $this->orm_type , 
            'status'  => $status ,
            'value'  => $map ,
        ) ;
        
        $admin_class->addProperty('workflow', $config, 'array', null, 'public' ) ;
        
        $auth_permertions = array() ;
        
        foreach($this->children as $status_name => $node ) {
            foreach($node->properties as $property => $flag ) {
                if( self::FLAG_AUTH & $flag ) {
                    $auth_permertions[ $status_name ]['properties'][] = $property ;
                }
            }
        }
        
        if( $this->_parsed_children_permertions ) {
            $admin_class->addProperty('workflow_permertions', $this->_parsed_children_permertions, 'array', null, 'public' ) ;
            
            foreach($this->_parsed_children_permertions as $child_admin => $child_config ) {
                if( $child_admin === $this->admin_object->name ) {
                    continue ;
                }
                foreach($child_config as $action_name => $action_config ) {
                    foreach($action_config as $status_name => $flag ) {
                        if( !$flag ) {
                            $auth_permertions[ $status_name ]['children'][ $child_admin ][] = $action_name ;
                        }
                    }
                }
            }
            
        }
        
        if( !empty($auth_permertions) ) {
            // \Dev::dump($auth_permertions);
            $admin_class->addProperty('workflow_auth_permertions', $auth_permertions , 'array', null, 'public' ) ;
        }
    }
    
    private function parseChildrenPermertions() {
        
        $children_config = array() ;
        
        $self_config    = array() ;
        foreach($this->children as $status_name => $status_config ) {
            if( $status_config->target ) {
                if( in_array('removed', $status_config->target ) ) {
                    if( !isset($self_config['delete']) ) {
                        $self_config['delete']  = array() ;
                    }
                    $self_config['delete'][$status_name]    =  true ;
                }
            }
            if( $status_config->source ) {
                if( in_array('none', $status_config->source ) ) {
                    if( !isset($self_config['create']) ) {
                        $self_config['create']  = array() ;
                    }
                    $self_config['create'][$status_name]    = true ;
                }
            }
        }
        $children_config[ $this->admin_object->name ] = $self_config ;
        
        if( $this->_admin_children_permertions ) {
            
            foreach($this->_admin_children_permertions as $child_admin_name => $actions ) {
                $visted = array() ;
                $config = array(
                        '*' => array() ,
                    ) ;
                if( $child_admin_name !== $this->admin_object->name ) {
                    if( !$this->admin_object->generator->hasAdminName( $child_admin_name ) ) {
                         throw new \Exception(sprintf("admin(%s) workflow.children admin(%s) not exists", $this->admin_object->name, $child_admin_name)) ;
                    }
                    if( !$this->admin_object->_route_assoc->hasChildAdmin( $child_admin_name , $visted) ) {
                        throw new \Exception(sprintf("admin(%s) has no child admin(%s)", $this->admin_object->name, $child_admin_name)) ;
                    }
                }
                foreach($actions as $action_name => $status_config ) {
                    if( $action_name !== '*' ) {
                        if( !isset($this->admin_object->action_collection->children[$action_name]) ) {
                            throw new \Exception(sprintf("admin(%s) workflow.children admin(%s) action(%s) not exists!", $this->admin_object->name, $child_admin_name, $action_name )) ;
                        }
                    }
                    $_status_config = preg_split('/\s*,\s*/', trim($status_config) ) ;
                    $_valid_status  = array() ;
                    foreach($_status_config as $_status ) {
                        if( empty($_status) ) continue ;

                        $flag_value = true ;
                        if( preg_match('/^[^\w\*\_\-]/', $_status , $_flag ) ) {
                            $_flag  = $_flag[0] ;
                            if( '@' === $_flag ){
                                $flag_value   = false ;
                            } else if( '!' === $_flag ){
                                $flag_value   = -1 ;
                            } else {
                                $this->throwError("unknow flag(%s) for admin(%s) workflow.children admin(%s) action(%s)", $_flag, $this->admin_object->name, $child_admin_name, $action_name );
                            }
                            $_status    = trim( substr($_status, 1 ) ) ;
                        }

                        if( empty($_status) ) {
                            throw new \Exception(sprintf("admin(%s) workflow.children admin(%s) action(%s) use empty status() !", $this->admin_object->name, $child_admin_name, $action_name )) ;
                        }
                        
                        if( '*' !== $_status ) {
                            if( !isset($this->children[$_status]) ) {
                                throw new \Exception(sprintf("admin(%s) workflow.children admin(%s) action(%s) use not exists status(%s) !", $this->admin_object->name, $child_admin_name, $action_name, $_status )) ;
                            }
                        }
                        if( isset($_valid_status[$_status]) ) {
                            throw new \Exception(sprintf("admin(%s) workflow.children admin(%s) action(%s) duplicate status(%s) !", $this->admin_object->name, $child_admin_name, $action_name, $_status )) ;
                        }
                        $_valid_status[$_status] = $flag_value ;
                    }
                    $config[ $action_name ] = $_valid_status ;
                }
                
                $default_config = $config['*'] ;
                if( isset($config['*']['*']) ) {
                    foreach($this->children as $name => $none ) {
                        if( !isset($default_config[ $name ]) ) {
                            $default_config[ $name ] = $config['*']['*'] ;
                        }
                    }
                    unset($default_config['*']) ;
                }
                unset($config['*']) ;

                foreach($config as $_aciton_name => & $_action_config ) {
                    if( isset($_action_config['*']) ) {
                        foreach($default_config as $status => $status_value ) {
                            if( !isset($_action_config[$status]) ) {
                                $_action_config[$status] = $status_value ;
                            }
                        }
                        unset($_action_config['*']) ;
                    }
                }

                foreach($this->admin_object->action_collection->children as $action_name => $none ) {
                    if( !isset($config[$action_name]) ) {
                        $config[$action_name]   = $default_config ;
                    }
                }

                foreach($config as $action_name => & $action_config ) {
                    foreach($action_config as $status => $none ) {
                        if( -1 === $none ) {
                            unset($action_config[$status]) ;
                        }
                    }
                }
                
                if( $child_admin_name === $this->admin_object->name ) {
                    /**
                     * @todo no need config for myself ?
                     */
                    \Dev::dump($config); exit;
                }
                $children_config[ $child_admin_name ] = $config ;
            } 
        }
        
        return $children_config ; 
    }
    
    public function compileListAction(\App\AdminBundle\Compiler\Generator\PhpWriter $writer){
        
        $writer->writeln('<div class="btn-group btn-group-sm">');
        
        foreach($this->children as $name => $node ) if( false !== $node->list ) {
            if( ! $node->list ) {
                continue ;
            }
            if( $node->role ) {
                $writer->writeln('{% if is_granted("' . $node->role . '") %}');
            }
            
            $counter    = '{% set app_workflow_counter = admin.getRouteWorkflowCount("' . $name . '") %}' ;
            $counter    .= '(<span {% if app_workflow_counter > 0 %}class="'. $this->admin_object->name . '_workflow_count_' . $name .'{% endif %}">{{ app_workflow_counter }}</span>)';
            
            if( $node->isInternal() ) {
                $label  = '{{ "' . $node->label->getPath() . '"| trans({"%admin%": admin.label}, "' . $node->label->getDomain() . '") }} ' . $counter  ;
            } else {
                $label  = '{{ "' . $node->label->getPath() . '"| trans({}, "' . $node->label->getDomain() . '") }} ' . $counter ;
            }
            
            $writer->writeln( '<a href="{{ admin.path("list", null, {"admin_route_workflow":"' . $name . '"}) }}" class="btn {% if admin.routeworkflow == "' .  $name . '" %}btn-primary{% else %}btn-default{% endif %}">' . $label . '</a>');
        
            if( $node->role ) {
                $writer->writeln('{% endif %}');
            }
        }
        $writer->writeln('</div>');
        
    }
}