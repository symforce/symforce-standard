<?php

namespace App\AdminBundle\Compiler\Cache ;

trait AdminWorkflow {
    
    /**
     * @var array
     */
    public $workflow ;
    
    /**
     * @var array
     */
    public $workflow_permertions ;
    /**
     * @var array
     */
    public $workflow_auth_permertions ;
    
    protected $_route_workflow_status ;
    
    public function setRouteWorkflow( $status ){
        if( !isset($this->workflow) ) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        if( !isset($this->workflow['status'][$status]) ) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        $this->_route_workflow_status   = $status ;
    }
    
    public function getRouteWorkflowValue(){
        if( !$this->_route_workflow_status ) {
            throw new \Exception(sprintf('big error, admin `%s` has no status', $this->name ));
        }
        return $this->workflow['status'][$this->_route_workflow_status]['value'] ;
    }
    
    public function getRouteWorkflow(){
        if( $this->_route_workflow_status ) {
            return $this->_route_workflow_status ;
        }
        foreach($this->workflow['status'] as $name => & $config ) {
            if( !$config['list'] ) {
                continue ;
            }
            if( isset($config['role'])  ) {
                // check
            }
            return $name ;
        }
        // @todo @fixme
    }
    public function getRouteWorkflowStatus(){
        $status = $this->getRouteWorkflow() ;
        return $this->workflow['status'][$status] ;
    }
    
    
    public function hasWorkflowStatus( $status ){
        return isset($this->workflow['status'][$status]) ;
    }
    
    public function getRouteWorkflowCount( $status ) {
        if( !$this->workflow ) {
            throw new \Exception(sprintf('big error, admin `%s` has no status', $this->name ));
        }
        if( !isset($this->workflow['status'][$status]) ) {
            throw new \Exception("big error") ;
        }
        $value  = $this->workflow['status'][$status]['value'] ;
        $em = $this->getManager() ;
        $dql   = sprintf("SELECT count(a.%s) FROM %s a WHERE a.%s='%s'", $this->property_id_name, $this->class_name, $this->workflow['property'], $value );
        
        if( $this->route_parent ) {
            $parent_object  = $this->route_parent->getRouteObject() ;
            $dql    .= sprintf(' AND a.%s=%d', $this->route_parent_property , $this->route_parent->getId( $parent_object ) ) ;
        }
        
        $query  = $em->createQuery( $dql ) ;
        return $query->getSingleScalarResult();
    }
    
    public function getObjectWorkflowStatus( $object ){
        if( !($object instanceof $this->class_name ) ) {
            throw new \Exception(sprintf("big error, expect `%s` but get `%s`", $this->class_name, is_object($object)? get_class($object): gettype($object) )) ;
        }
        if( !$this->workflow ) {
            throw new \Exception(sprintf('big error, admin `%s` has no status', $this->name ));
        }
        $id = $this->getReflectionProperty( $this->property_id_name )->getValue( $object ) ;
        if( !$id ) {
            $status = 'none' ;
        } else {
            $value = $this->getReflectionProperty( $this->workflow['property'] )->getValue( $object ) ;
            if( !isset( $this->workflow['value'][$value] ) ) {
                throw new \Exception("big error") ;
            }
            $status = $this->workflow['value'][$value] ;
        }
        return $this->workflow['status'][$status] ;
    }
    
    public function getObjectWorkflowLabel( $object ){
        $status = $this->getObjectWorkflowStatus( $object ) ;
        return $this->trans( $status['label'] , null,  $status['domain'] );
    }
    
    
    public function getWorkflowFormChoices( $object ) {
        $config = $this->getObjectWorkflowStatus( $object ) ;
        $tr     = $this->container->get('translator');
        $choices = array() ;
        
        // if allow not update status
        if( ! $config['internal'] ) {
            $auth = $this->admin_loader->getCurrentLoginSecurityAuthorize( $this->name ) ;
            
            if( 
                    $auth && isset($auth['workflow'][ $config['name']]['action']['update']) 
                    || $this->container->get('security.context')->isGranted('ROLE_SUPER_ADMIN') 
            ) {
                if( '2' !== $auth['workflow'][ $config['name']]['action']['update'] ) {
                   $_value = $config['value'] ;
                   $_label = $tr->trans( $config['action'],  array(), $config['domain'] ) ;
                   $choices[ $_value ] = $_label  ;
                }
            }
        }
        
        if($config['target']) foreach($config['target'] as $_status ) {
            $_config    = $this->workflow['status'][$_status] ;
            if( $_config['internal'] ) {
                 continue ;
            }
            $_value = $_config['value'] ;
            $_label = $tr->trans( $_config['action'],  array(), $_config['domain'] );
            $choices[ $_value ] = $_label ;
        }
        return $choices ;
    }
    
    public function checkWorkflowPermission($object, $target) {
        $status = $this->getObjectWorkflowStatus( $object ) ;
        if( $target === $status['name'] ) {
            return false ;
        }
        if( !$status['target'] ) {
            return false ;
        }
        return in_array( $target, $status['target']) ;
    }
    
    public function getWorkflowActionPath($object, $target) {
        $status = $this->getObjectWorkflowStatus( $object ) ;
        $id = $this->getId( $object ) ;
        return $this->container->get('router')->generate('app_admin_workflow_action', array(
            'admin_name'    => $this->name ,
            'target'    => $target ,
            'id'    => $id , 
        ));
    }
    
    public function getWorkflowUpdateLabel($object, $action_name) {
        $action = $this->getAction($action_name) ;
        if( $action->isFormAction() ) {
            $_config = $this->getObjectWorkflowStatus( $object ) ;
            if( isset($_config['update']) ) {
                $tr     = $this->container->get('translator');
                return $tr->trans( $_config['update'],  array(), $_config['domain'] );
            }
        } 
        return $action->getLabel() ;
    }
    
    public function getStatusValueByName( $status ) {
        if( !$this->workflow ) {
            throw new \Exception("big error") ;
        }
        if( !isset($this->workflow['status'][$status]) ) {
            throw new \Exception(sprintf("status(%s) not exists, all status is(%s)", $status, join(',', array_keys($this->workflow['status']) )  )) ;
        }
        return $this->workflow['status'][$status]['value'] ;
    }
    
    
    public function getStatusLabelByName( $status ){
        if( !$this->workflow ) {
            throw new \Exception("big error") ;
        }
        if( !isset($this->workflow['status'][$status]) ) {
            throw new \Exception(sprintf("status(%s) not exists, all status is(%s)", $status, join(',', array_keys($this->workflow['status']) )  )) ;
        }
        $config = $this->workflow['status'][$status] ;
        return $this->trans( $config['label'] , null,  $config['domain'] );
    }
    
    public function getListByParentAndWofkflow( $parent_object , $status, $count = false ){
        if( !$this->workflow ) {
            throw new \Exception("big error") ;
        }
        if( !isset($this->workflow['status'][$status]) ) {
            throw new \Exception(sprintf("status(%s) not exists, all status is(%s)", $status, join(',', array_keys($this->workflow['status']) )  )) ;
        }
        
        $value  = $this->workflow['status'][$status]['value'] ;
        $where  = array(
            'a.'. $this->workflow['property'] . '=' . $value 
        ) ;
        return $this->getListByParents($parent_object, $count, $where );
    }
    
    
    public function onWorkflowValueChange($object, $new_value, $old_value) {
        if( !isset($this->workflow['value'][ $new_value ])) {
            throw new \Exception(sprintf("invalid workflow new value(%s)", $new_value));
        }
        $new_step   = $this->workflow['value'][ $new_value ] ;
        /**
         * @fixme make is work on debug mode
         */
        if( $old_value ) {
            if( !isset($this->workflow['value'][ $old_value ])) {
                throw new \Exception(sprintf("invalid workflow old value(%s)", $old_value));
            }
            $old_step   = $this->workflow['value'][ $old_value ] ; 
        } else {
            $old_step   = 'none' ;
        }
        
        $this->onWorkflowStatusChange($object, $new_step, $old_step );
    }
    
    public function onWorkflowStatusChange($object, $new_step, $old_step) {
        $_new_value =  $this->getReflectionProperty( $this->workflow['property'])->getValue($object);
        if( !isset($this->workflow['value'][ $_new_value ])) {
            throw new \Exception(sprintf("invalid workflow new value(%s)", $new_value));
        }
        $_new_step   = $this->workflow['value'][ $_new_value ] ;
        if( $_new_step !== $new_step ) {
            throw new \Exception(sprintf("workflow new step not match(%s,%s)", $new_step, $_new_step ));
        }
    }
    
    public function revertWorkflowStatus($object, $old_status = null ) {
        $prop = $this->getReflectionProperty( $this->workflow['property']) ;
        if( $old_status ) {
            if( !isset( $this->workflow['status'][$old_status]) ) {
                throw new \Exception(sprintf('status `%s` for admin `%s` not exists', $new_status, $this->name));
            }
            if( $this->workflow['status'][$old_status]['internal'] ) {
                throw new \Exception(sprintf('status `%s` for admin `%s` is intelnal', $new_status, $this->name));
            }
            $old_value  = $this->workflow['status'][$old_status]['value'] ;
        } else {
            $_object    = $this->getFormOriginalObject() ;
            if( !$_object ) {
                throw new \Exception("no old status");
            }
            $old_value  = $prop->getValue( $_object ) ;
        }
        if( $old_value !== $prop->getValue($object) ) {
            $prop->setValue($object,  $old_value );
            $em = $this->getManager() ;
            $em->persist($object);
            $em->flush();
        }
    }
}