<?php

namespace App\AdminBundle\Compiler\Cache ;

use Symfony\Component\DependencyInjection\ContainerAware ;
use App\AdminBundle\Compiler\Loader\AdminLoader ;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

abstract class AdminCache extends ContainerAware {
    
    use AdminRoute ;
    use AdminTree ;
    use AdminWorkflow ;
    use AdminOwner ;
    use AdminPage ;
    use AdminMenu ;
    use AdminForm ;
    use AdminSecurity ;
    
    /** @var string */
    protected $name;

    protected $property_id_name;
    protected $property_value_name;
    protected $property_slug_name;
    protected $property_slug_unique ;
    protected $property_slug_nullable ;

    /** @var string */
    protected $class_name ;
    
    /** @var string */
    protected $bundle_name ;
    
    /** @var string */
    protected $tr_domain ;
    
    /** @var string */
    protected $app_domain ;
    
    /** @var array */
    protected $action_maps = null ;
    
    /** @var array */
    protected $admin_maps = null ;
    
    /**
     * @var AdminLoader
     */
    protected $admin_loader ;
    
    /**
     * @var object
     */
    protected $translator ;
    
    protected $form_choices = array() ;
    protected $form_elements = array () ;
    protected $copy_properties ;

    /**
     * @var array
     */
    protected $events = array() ;


    public function __construct( AdminLoader $loader  ) {
        $this->admin_loader = $loader ;
        $this->__wakeup() ;
    }
    
    abstract protected function __wakeup() ;
    
    public function getFormBuilderOption($property, ActionCache $action = null, $object = null ) {
        if( $object ) {
            if( !($object instanceof $this->class_name ) ) {
                throw new \Exception("big error");
            }
        }
    }
    
    public function addEvent($type, \Closure $fn){
        if( isset($this->events[$type]) ) {
            if( in_array($fn, $this->events[$type] ) ) {
                return ;
            }
        } else {
            $this->events[$type]    = array() ;
        }
        $this->events[$type][]  = $fn ;
    }

    public function fireEvent($type, $evt = null ){
        if( isset($this->events[$type]) ) {
            foreach($this->events[$type] as  $i => $fn ) {
                if( !$fn( $evt , $this ) ) {
                    unset( $this->events[$type][$i] ) ;
                }
            }
        }
    }
    
    public function removeEvent($type, \Closure $fn = null ){
        if( isset($this->events[$type]) ) {
            if( $fn ) {
                foreach($this->events[$type] as $i => $_fn ) {
                    if( $_fn === $fn ) {
                        unset($this->events[$type][$i]);
                    }
                }
            } else {
                unset($this->events[$type]) ;
            }
        }
    }
    
    /**
     * @var AdminLoader
     */
    public function getAdminLoader() {
        return $this->admin_loader ;
    }
    
    /**
     * @return string
     */
    public function getPropertyIdName(){
        return $this->property_id_name ;
    }
    
    /**
     * @return string
     */  
    public function getPropertySlugName(){
        return $this->property_slug_name ;
    }
    
   /**
     * @return bool
     */
    public function isPropertySlugUnique() {
        return $this->property_slug_unique  ;
    }
    
    /**
     * @return bool
     */
    public function isPropertySlugNullable() {
        return $this->property_slug_nullable  ;
    }
    
    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer(){
        return $this->container ;
    }
    
    /** @return string */
    public function getName(){
        return $this->name;
    }
    
    public function getBundleName(){
        return $this->bundle_name ;
    }
    
    public function getLabel() {
        if( null === $this->translator ) {
            $this->translator   = $this->container->get('translator') ;
        }
        return $this->translator->trans( $this->name . '.label', array(), $this->tr_domain ) ;
    }

    public function getActionLabel($action_name = null , $object = null ) {
        if( null === $action_name ) {
            $action_name    = $this->getRouteAction()  ;
            $object = $this->getRouteObject() ;
        } 
        $action = $this->getAction($action_name) ;
        return $action->getActionLabel($object) ;
    }
    
    public function getTitleLabel($action_name = null , $object = null ) {
        if( null === $action_name ) {
            $action_name    = $this->getRouteAction()  ;
            $object = $this->getRouteObject() ;
        } 
        $action = $this->getAction($action_name) ;
        return $action->getTitleLabel($object) ;
    }
    
    public function getFormLabel($action_name = null , $object = null ) {
        if( null === $action_name ) {
            $action_name    = $this->getRouteAction()  ;
            $object = $this->getRouteObject() ;
        } 
        $action = $this->getAction($action_name) ;
        return $action->getFormLabel($object) ;
    }
    
    /** @return string */
    public function getClassName(){
        return $this->class_name;
    }
    
    public function getDomain(){
        return $this->tr_domain ;
    }
    
    public function getAppDomain(){
        return $this->app_domain ;
    }
    
    public function getId( $object ) {
        if( !$object || !($object instanceof $this->class_name) ) {
            throw new \Exception(sprintf("expect argument is type:%s, but get %s", $this->class_name, is_object($object)?get_class($object): gettype($object) ));
        }
        $prop   = $this->getReflectionProperty( $this->property_id_name ) ;
        return $prop->getValue( $object ) ;
    }
    
    public function newObject() {
        $class_name = $this->class_name ;
        $object = new $class_name ;
        $this->fixRouteObject($object) ;
        return $object ;
    }
    
    public function getObjectById( $id ) {
        $object = $this->getRepository()->find($id) ;
        return $object ;
    }
    
    public function hasAction( $action_name ){
        return isset( $this->action_maps[ $action_name ] ) ;
    }
    
    /**
     * @param string $action_name
     * @return ActionCache
     * @throws \Exception
     */
    public function getAction( $action_name ){
        if( !isset( $this->action_maps[ $action_name ] )  ) {
            throw new \Exception( sprintf("`%s`:`%s` dose not have action `%s`", $this->name, $this->class_name, $action_name ) ) ;
        }
        
        if( isset( $this->action_objects[ $action_name ] ) ) {
            return $this->action_objects[ $action_name ] ;
        }
        
        $action_class   = $this->action_maps[ $action_name ] ;
        
        $action     = new $action_class( $this , $this->admin_loader ) ;
        $this->action_objects[ $action_name ] = $action ;
        
        return $action ;
    }
    
    private $_init_actions ;
    public function getActions() {
        if( null !== $this->_init_actions  ) {
            return $this->_init_actions ; 
        }
        $this->_init_actions  = array() ;
        foreach($this->action_maps as $action_name => $none ) {
            $this->_init_actions[$action_name ] = $this->getAction( $action_name ) ;
        }
        return $this->_init_actions ;
    }
    
    private  $_em = null ;
    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getManager() {
        if( null === $this->_em ) {
            $this->_em  = $this->container->get('doctrine')->getManager() ;
        }
        return $this->_em  ;
    }
    
    private  $_repository = null ;
    
    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository() {
        if( null === $this->_repository ) {
            $this->_repository  = $this->getManager()->getRepository( $this->class_name ) ;
        }
        return $this->_repository ;
    }
    
    public function update( $object ){
        $em = $this->getManager() ;
        if( $this->property_page_name && $this->page_one2one_map ) {
            $page   = $this->getReflectionProperty( $this->property_page_name )->getValue(  $object ) ;
            if( $page ){
                $this->getPageAdmin()->getManager()->persist( $page ) ;
            }
        }
        
        $this->fireEvent( 'update', $object ) ;
        $em->persist( $object ) ;
        $em->flush() ;
        $this->fireEvent( 'flushed', $object ) ;
        
    }
    
    public function remove( $object ) {
        $em = $this->getManager() ;
        $em->remove( $object ) ;
        $em->flush() ;
    }
    
    public function string( $object ) {
        if( !is_object($object) ) {
            throw new \Exception(sprintf("expect class(%s) get type(%s = %s)",  $this->class_name, gettype($object), var_export($object, 1))) ;
        }
        if( method_exists($object, '__toString') ) {
            return $object->__toString() ;
        }
        if( $this->property_value_name ) {
            $value  = $this->getReflectionProperty($this->property_value_name )->getValue( $object ) ;
            return $value ;
        }
        $id = (string) $this->getReflectionProperty($this->property_id_name )->getValue( $object ) ; 
        return $id ;
    }
    
    public function getDoctrineConfigBy( $type, $property ) {
        return $this->admin_loader->getDoctrineConfigBy( $this->class_name, $type, $property ) ;
    }
    
    /**
     * @var \ReflectionClass
     */
    protected $_reflection_class    = null ;
    
    
    public function getReflectionClass(){
        if( null === $this->_reflection_class ) {
            $this->_reflection_class = new \ReflectionClass( $this->class_name );
        }
        return $this->_reflection_class ;
    }
    
    /**
     * @var array
     */
    protected $_reflection_properties   = array() ;
    
    /**
     * 
     * @param string $name
     * @return \﻿ReflectionProperty 
     */
    public function getReflectionProperty( $name ){
        if( !isset($this->_reflection_properties[$name]) ) {
            if( null === $this->_reflection_class ) {
               $this->_reflection_class = new \ReflectionClass(  $this->class_name );
            }
            $this->_reflection_properties[$name]    = $this->_reflection_class->getProperty( $name ) ;
            $this->_reflection_properties[$name]->setAccessible( true ) ;
        }
        return $this->_reflection_properties[$name] ;
    }
    
    public function getTranslator(){
        if( null === $this->translator ) {
            $this->translator   = $this->container->get('translator') ;
        }
        return $this->translator ;
    }
    
    public function trans($path, $options = array(), $domain = null ){
        if( null === $this->translator ) {
            $this->translator   = $this->container->get('translator') ;
        }
        if( 0 === strpos( $path, '.') ) {
            $path   = $this->name . $path ;
            if( null === $domain ) {
                $domain = $this->tr_domain ;
            }
        } else {
            if( null === $domain ) {
                if( 0 === strpos( $path, $this->name . '.' ) ) {
                    $domain = $this->tr_domain ;
                } else {
                    $domain = $this->app_domain ;
                } 
            }
        }
        if( is_object($options) ) {
            $options    = array( '%object%' => $this->string($options) ) ;
        } else if( !$options ) {
            $options    = array() ;
        }
        return $this->translator->trans( $path , $options, $domain ) ;
    }
    
    public function getService($name){
        return $this->admin_loader->getService($name) ;
    }
    
    public function onUpdate(Controller $controller, Request $request, ActionCache $action, $object, \Symfony\Component\Form\Form $form ){
        
        if( $this->copy_properties ) {
            $em     = $this->getManager() ;
            foreach($this->copy_properties as $property_name => $config ) {
                $prop   = $this->getReflectionProperty( $property_name ) ;
                $value  = $prop->getValue( $object ) ;
                // \Dev::dump($value);
                $admin  = $this->admin_loader->getAdminByClass( get_class($value) ) ;
                foreach ( $config  as $from_property => $to_property ) {
                    $from_prod  = $this->getReflectionProperty( $from_property ) ;
                    $to_prop  = $admin->getReflectionProperty( $to_property ) ;
                    $to_prop->setValue( $value, $from_prod->getValue($object) );
                }
                // \Dev::dump($value); exit;
                $em->persist($value);
            }
        }
        
    }
    
    public function getListDQL(){
        
        $dql   = sprintf("SELECT a FROM %s a", $this->class_name );
        $where  = array() ;
        $parent_property    = $this->getRouteParentProperty() ;
        if( $parent_property ) {
            $parent = $this->getRouteParent() ;
            $parent_object  = $parent->getRouteObject() ;
            if( $parent_object ) {
                $where[]    = 'a.' . $parent_property .  '=' . $parent_object->getId() ;
            } else {
                $where[]    = 'a.' . $parent_property .  '=' . (int) $parent->getRouteObjectId() ;
            }
        }
        
        if( $this->tree ) {
            $tree_parent_id = $this->getTreeObjectId() ;
            if( $tree_parent_id ) {
                $where[]     = sprintf("a.%s='%s'", $this->tree['parent'], $tree_parent_id ) ;
            } else {
                $where[]     = sprintf("(a.%s='0' OR a.%s IS NULL)", $this->tree['parent'] , $this->tree['parent'] ) ;
            }
        }
        
        if( $this->workflow ) {
            $step = $this->getRouteWorkflowValue() ;
            if(\App\AdminBundle\Compiler\MetaType\Admin\Workflow::NO_FILTER !== $step ) {
                $where[]     = sprintf("a.%s='%s'", $this->workflow['property'] , $step ) ;
            }
        }
        
        if( !empty($where) ) {
            $dql    .= ' WHERE ' . join(' AND ', $where) ;
        }
        
        return $dql ;
    }

    public function afterUpdate(Controller $controller, Request $request, ActionCache $action, $object, \Symfony\Component\Form\Form $form ){
         $request->getSession()->getFlashBag()->add('info',
                     $this->trans( 'app.action.update.finish' , $object )
                 ) ;
         return $controller->redirect( $action->getFormReferer($form) ) ;
     }

    public function afterCreate(Controller $controller, Request $request, ActionCache $action, $object, \Symfony\Component\Form\Form $form ){
         $request->getSession()->getFlashBag()->add('info', 
                                    $this->trans( 'app.action.create.finish' , $object )
                                 );
         return $controller->redirect( $action->getFormReferer($form) ) ;
    }
   
    private $_form_original_object = null ;
    public function setFormOriginalObject($object){
        $this->_form_original_object   = clone $object ;
    }
    
    public function getFormOriginalObject(){
        return $this->_form_original_object ;
    }
    
    public function isActionFormDebug(){
        $action = $this->getRouteAction() ;
        if( !$action ) {
            return ;
        }
        $_action    = $this->getAction($action) ;
        $form   = $_action->getForm() ;
        if( !$form ) {
            return ;
        }
        return $_action->getFormDebug( $form ) ;
    }
}