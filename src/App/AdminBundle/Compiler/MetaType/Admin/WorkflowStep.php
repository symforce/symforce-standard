<?php


namespace App\AdminBundle\Compiler\MetaType\Admin ;

use App\AdminBundle\Compiler\Annotation\Status ;

/**
 * Description of Status
 *
 * @author loong
 */
class WorkflowStep extends \App\AdminBundle\Compiler\MetaType\Type {
    
    /**
     * @var Workflow
     */
    private $parent ;

    public $name ;
    
    public $value ;
    public $is_string_value ;
    
    public $label ;
    public $list = true ;
    public $action ;
    public $update ;
    public $btn ;
    public $message ;

    public $source ;
    
    public $role ;
    public $duty ;
    
    public $color ;
    
    public $properties ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorNode 
     */
    public $tr_node ;
    
    private $internal ;
    public $target ;
    
    public function __construct( Workflow $parent, $name, $value, $internal = false ){
        $this->parent   = $parent ;
        $this->internal = $internal ;
        
        if( $name ) {
            if( !is_string($name)  ) {
                $this->throwError("bigger error") ;
            }
            $this->set_name($name) ;
        }
        
        if( null !== $value ) {
            if( is_scalar($value) ) {
                if( !$this->name ) {
                    $this->set_name( $value ) ;
                } else {
                    $this->set_value( $value ) ;
                }
            } else if( is_array($value) ) {
                if( isset($value['name']) ) {
                    $this->set_name( $value['name'] ) ;
                    unset($value['name']) ;
                }
                $this->setMyPropertie( $value ) ;
            } else {
                $this->throwError("bigger error") ;
            }
        }
        
        if( null === $this->properties ) {
            $this->properties   = $this->parent->properties ;
        }
    }
    
    protected function set_name( $value ) {
        if( $this->name ) {
            $this->throwError("name duplicate(%s, %s)", $this->name , $value );
        }
        if( preg_match('/\W/', $value) ) {
            $this->throwError("name(%s) is invalid", var_export($value, 1) ) ;
        }
        $this->name = $value ;
    }
    
    protected function set_role($value){
        if( preg_match('/^[AZ_]+$/', $value) ) {
            $this->throwError("role(%s) is invalid", var_export($value, 1) ) ;
        }
        $this->role = $value ;
    }

    protected function set_duty($value){
        if( !is_bool($value) ) {
            $this->throwError("duty(%s) is invalid", var_export($value, 1) ) ;
        }
        $this->duty = $value ;
    }
    
    protected function set_color($value){
        if( !preg_match('/^\#?[0-9a-fA-F]{3}$|^\#?[0-9a-fA-F]{6}$/', $value) ) {
            $this->throwError("color(%s) is invalid", $value ) ;
        }
        $this->color = $value ;
    }


    public function set_value( $value ) {
        if( is_integer($value) ) {
            $this->value    = $value ;
        } else if ( is_string ($value) ) {
            if( preg_match('/^\d+\$/', $value ) ) {
                $this->value    = (int) $value ;
            } else if( preg_match('/\W/', $value ) ) {
                $this->throwError("value %s is invalid",  $value ) ;
            } else {
                $this->is_string_value = true ;
                $this->value    = $value ;
            }
        } else {
            $this->throwError("value must be integer or string, %s",  $value ) ;
        }
    }
    
    protected function set_source( $value ) {
        $this->source   = preg_split('/\s*,\s*/', trim($value) ) ;
    }
    
    protected function set_is_string_value( $value ){
        $this->throwError("bigger error" ) ;
    }

    protected function set_default( $value ) {
        if( !is_bool($value) ) {
            $this->throwError("default must be bool");
        }
        $this->default  = $value ;
    }
    
    protected function set_properties( $value ) {
        $this->properties = $this->parent->getPropertiesFromAnnot( sprintf('@status(%s)', $this->name ), $value, $this->parent->properties ) ;
    }

    protected function set_list( $value ) {
        if( !is_bool($value) ) {
            $this->throwError("default must be bool");
        }
        $this->list  = $value ;
    }
    
    public function isInternal(){
        return $this->internal ;
    }
    
    private $lazy_initialized ;
    public function lazyInitialize(){
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        if( $this->source ) foreach($this->source as $name ) {
            if( !isset( $this->parent->children[$name]) ) {
                $this->throwError("in(%s) not exists for status(`%s`)", $name, $this->name );
            }
        }
        
        if( $this->internal ) {
            $this->tr_node  = $this->parent->admin_object->generator->getTransNodeByPath( $this->parent->admin_object->app_domain , 'app.status.' . $this->name ) ;
            if( null === $this->label ) {
                $this->label   = $this->name ;
            }
            if( null === $this->message ) {
                $this->message   = $this->name ;
            }
        } else {
            $this->tr_node  = $this->parent->tr_node->getNodeByPath( $this->name ) ;
        }
        
        if( $this->label ) {
            $this->label    = $this->tr_node->createValue( 'label', $this->label ) ;
        } else {
            $this->label    = $this->tr_node->createValue( 'label', $this->name ) ;
        }
        
        if( $this->action ) {
            $this->action    = $this->tr_node->createValue( 'action', $this->action ) ;
        } /* else {
            if( !$this->internal ) {
                $this->action    = $this->tr_node->createValue( 'action', $this->name ) ;
            }
        } */
        
        if( $this->update ) {
            $this->update    = $this->tr_node->createValue( 'update', $this->update ) ;
        }
        
        
        if( $this->message ) {
            $this->message    = $this->tr_node->createValue( 'message', $this->message ) ;
        } else if( false !== $this->list ){
            $this->message    = $this->label ;
        }

        $this->target   = array() ;
        foreach($this->parent->children as $name => $node ) {
            if( $node->source && in_array( $this->name, $node->source ) ) {
                $this->target[]     = $name ;
            }
        }
        
        if( empty($this->target) ) {
            $this->target = false ;
        }
    }

    public function compile() {
        // $admin_class    = $this->parent->admin_object->getCompileClass() ;
    }

    public function getCompileValue(){
        $o  = array(
            'name'  => $this->name ,
            'value' => $this->value ,
            'internal' => $this->internal  ,
            'domain'    => $this->label->getDomain() ,
            'label' => $this->label->getPath() ,
            'action' => $this->action ? $this->action->getPath() : $this->label->getPath() ,
            
            'list'  => $this->list ,
            'source'  => $this->source , 
            'target'  => $this->target , 
            'btn'  => $this->btn ,
            
            'role'  => $this->role ,
            'duty'  => $this->duty ,
            
            'properties'  => $this->parent->fixProperties($this->properties) ,
        ) ;
        
        // \Dev::debug($this->name, $this->properties,  $this->parent->fixProperties($this->properties) );
        
        if( $this->action ) {
            $o['action'] = $this->action->getPath() ;
        }
        
        if( $this->update ) {
            $o['update'] = $this->update->getPath() ;
        }
        
        if( $this->message ) {
            $o['message']   = $this->message->getPath() ;
        }
        
        return $o ;
    }
}
