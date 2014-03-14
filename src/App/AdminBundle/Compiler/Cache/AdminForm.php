<?php

namespace App\AdminBundle\Compiler\Cache ;

trait AdminForm {
    
    public $auth_properties ;
    
    public function buildFormElement(\Symfony\Bundle\FrameworkBundle\Controller\Controller $controller, \Symfony\Component\Form\FormBuilder $builder, AdminCache $admin, ActionCache $action, $object, $property_name, $parent_property ) {
        if( $object ) {
            if( !($object instanceof $admin->class_name) ) {
                throw new \Exception("bigger error") ;
            }
            if( $admin->workflow ) {
                if( $admin !== $this ) {
                    throw new \Exception("bigger error") ;
                }
                $status = $admin->getObjectWorkflowStatus( $object ) ;
                if( !isset($status['properties'][$property_name]) ) {
                    return ;
                }
                $flag   = $status['properties'][$property_name] ;
                $readable   = \App\AdminBundle\Compiler\MetaType\Admin\Workflow::FLAG_VIEW & $flag ;
                $editable   = \App\AdminBundle\Compiler\MetaType\Admin\Workflow::FLAG_EDIT & $flag ;
                if( \App\AdminBundle\Compiler\MetaType\Admin\Workflow::FLAG_AUTH & $flag ) {
                    if( $readable || $editable ) {
                        throw new \Exception("big error");
                    }
                    $securityContext = $this->container->get('security.context');
                    $user   = $securityContext->getToken()->getUser() ;
                    $group  = $user->getUserGroup() ;
                    if( $group ) {
                        $flag   = $group->getWorkflowPropertyVisiable($this->name, $property_name, $status['name']); 
                        if( '1' === $flag ) {
                            $editable   = true ;
                        } else if ( '2' === $flag ) {
                            $readable   = true ;
                        }
                    }
                }
                if( $readable ) {
                    $options    = array(
                        
                    ) ;
                    $_options    = $admin->getFormBuilderOption( $property_name, $action, $object ) ;
                    if( $_options ) {
                        $options['label'] = $_options['label']; 
                    }
                    $builder->add( $property_name, 'appview', $options ) ;
                    return ;
                }
                if( ! $editable ) {
                    return ;
                }
            }
        }
        $options    = $admin->getFormBuilderOption( $property_name, $action, $object ) ;
        $type       = $options['appform_type'] ;
        if( isset($options['read_only']) && $options['read_only'] ) {
            if( in_array($type, array('appowner', 'appentity', 'appworkflow', 'choice', 'checkbox', 'appfile', 'appimage', 'apphtml', 'money' )) ) {
                $options    = array(
                    
                ) ;
                $_options    = $admin->getFormBuilderOption( $property_name, $action, $object ) ;
                if( $_options ) {
                    $options['label'] = $_options['label']; 
                }
                $builder->add( $property_name, 'appview', $options ) ;
                return ;
            }
        }
        $this->adjustFormOptions($object, $property_name, $options);
        $subscribers = null ;
        if( isset($options['subscribers']) ) {
            $subscribers = $options['subscribers'] ;
            unset($options['subscribers']) ;
        }
        
        $builder->add( $property_name, $type, $options ) ;
        if( $subscribers ) {
            $_builder   = $builder->get( $property_name ) ;
            foreach($subscribers as $subscriber ){
                $events = $subscriber->getSubscribedEvents() ;
                foreach($events as $_event => $method  ) {
                    $_builder->addEventListener($_event, array($subscriber, $method) ) ;
                }
            }
        }
    }
    
    public function adjustFormOptions($object, $property, array & $options){
        
    }


    public function addFormElement($builder, $property_name, array $_options = null , $type = null ){
        $options    = $this->getFormBuilderOption( $property_name ) ;
        if( !$options ) {
            throw new \Exception( sprintf("%s->%s not exists", $this->name , $property_name) ) ;
        }
        if( null === $type ) {
            $type   = $options['appform_type'] ;
        } else {
            if( $type === 'appview' ) {
                $options    = array(
                    'label' => $options['label'] ,
                );
            }
        }
        $subscribers = null ;
        if( isset($options['subscribers']) ) {
            $subscribers = $options['subscribers'] ;
            unset($options['subscribers']) ;
        }
        
        $constraints    = null  ;
        if( isset($options['constraints']) && isset($_options['constraints']) ) {
            $constraints    = array() ;
            foreach($options['constraints'] as $constraint ) {
                $constraints[get_class($constraint) ] = $constraint ;
            }
            foreach($_options['constraints'] as $constraint ) {
                $constraints[get_class($constraint) ] = $constraint ;
            }
            unset($options['constraints']) ;
            unset($_options['constraints']) ;
        }
        
        if( $_options ) {
            \Dev::merge($options , $_options ) ;
        }
        
        if( $constraints ) {
            $options['constraints'] = array_values($constraints) ;
        }
        $builder->add( $property_name, $type, $options ) ;
        if( $subscribers ) {
            $_builder   = $builder->get( $property_name ) ;
            foreach($subscribers as $subscriber ){
                $events = $subscriber->getSubscribedEvents() ;
                foreach($events as $_event => $method ) {
                    $_builder->addEventListener($_event, array($subscriber, $method) ) ;
                }
            }
        }
    }
    
    public function buildForm(\Symfony\Bundle\FrameworkBundle\Controller\Controller $controller, \Symfony\Component\Form\FormBuilder $builder, ActionCache $action, $object) {
        if( !($object instanceof $this->class_name) ) {
            throw new \Exception("bigger error") ;
        }
    }
    
    public function getChoiceText( $name, $value ) {
        if( null === $this->translator ) {
            $this->translator   = $this->container->get('translator') ;
        }
        if( $value instanceof $this->class_name ) {
            $value = $this->getReflectionProperty( $name )->getValue( $value ) ;
        }
        if( !isset($this->form_choices[ $name ][ $value ]) ) {
            return $value ; 
        }
        $path   = $this->form_choices[ $name ][ $value ] ;
        return $this->translator->trans( $path[0], array(),  $path[1] ? $this->app_domain : $this->tr_domain ) ;
    }
    
    public function getChoicesText( $name, $value ) {
        if( null === $this->translator ) {
            $this->translator   = $this->container->get('translator') ;
        }
        if( !$value instanceof $this->class_name ) {
            throw new \Exception("bigger error") ;
        }
        $values = $this->getReflectionProperty( $name )->getValue( $value ) ;
        $_values = array() ;
        foreach($values as $value ) {
            $path   = $this->form_choices[ $name ][ $value ] ;
            $_value = $this->translator->trans( $path[0], array(),  $path[1] ? $this->app_domain : $this->tr_domain ) ;
            $_values[ $value ] = $_value ;
        }
        return join(', ', $_values ) ;
    }
    
    public function getFormOption($name) {
        if( isset($this->form_elements[$name]) ) {
            return $this->form_elements[$name] ;
        }
    }
    
    public function getPropertyLabel($name) {
        if( isset($this->properties_label[$name]) ) {
            $config = $this->properties_label[$name] ; 
            return $this->trans( $config[0] , null, $config[1]  );
        }
        return $name ;
    }
}