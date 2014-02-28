<?php

namespace App\AdminBundle\Compiler\Cache ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

/**
 * @author loong
 */
abstract class WebPageCache {
    
     /** @var array */
    protected $admin ;
    
     /** @var array */
    protected $cache ;
    
     /** @var array */
    protected $route ;
    
     /** @var array */
    protected $alias ;
    
    /**
     * @var ContainerInterface 
     */
    protected $container ;
    
    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader 
     */
    protected $loader ;
    
    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface
     */
    protected $access ;
    
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Route
     */
    protected $router ;
    
    abstract protected function __wakeup();

    public function __construct() {
        $this->__wakeup() ;
        $this->loadAppAdminRoute() ;
    }
    
    public function setContainer(ContainerInterface $app){
        $this->container = $app ;
        $app->get('app.admin.compiler')->set( \App\AdminBundle\Compiler\Loader\Compiler::STAT_PASS );
    }
    
    public function getOption( $name ) {
        if( isset($this->cache[$name]) ) {
            return $this->cache[$name] ;
        }
    }
    
    public function getAdminOption( $name ) {
        if( isset($this->admin[$name]) ) {
            return $this->admin[$name] ;
        }
    }
    
    private function getActionByObject( & $action, $object) {
        $pos = strpos($action, ':') ;
        if( false === $pos ) {
            if( !$object ) {
                throw new \Exception( sprintf("can not find web page `%s`", $action) ) ;
            }
            if( $object instanceof \Doctrine\ORM\Proxy\Proxy ) {
                $class = get_parent_class($object);
            } else {
                $class  = get_class($object) ;
            }
            if( !$this->loader->hasAdminClass($class) ){
                throw new \Exception( sprintf("`%s` is not admin class", $class) ) ;
            }
            return $this->loader->getNameByClass( $class ) ;
        }
        $admin  = substr( $action, 0, $pos );
        if( !$this->loader->hasAdminName($admin) ){
            throw new \Exception( sprintf("`%s` is not admin", $admin) ) ;
        }
        $action = substr( $action, $pos+1 );
        return $admin ;
    }

    public function path( $action, $object = null , array $option = array() ){
        if( null === $this->loader ) {
            $this->loader = $this->container->get('app.admin.loader') ;
            $this->router = $this->container->get('router') ;
            $this->access = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor() ;
        }
        if( empty($option) && is_array($object) && !empty($object) ) {
            $option = $object ;
            $object = null ;
        }
        if( null !== $object ) {
            if( !is_object($object) ) {
                throw new \Exception( sprintf("secend parameter has be null or object, you set(%s)", gettype($object)) ) ;
            }
        }
        $admin = $this->getActionByObject($action, $object) ;
        $admin_action   = $admin . ':' . $action ;
        if( !isset($this->cache[$admin_action]) ) {
            if(!isset($this->alias[$admin_action]) ) {
                throw new \Exception( sprintf("`%s` is not page route", $admin_action) ) ;
            }
            $admin_action   = $this->alias[$admin_action] ;
        }
        $cache =& $this->cache[$admin_action] ;
        if( $object ) {
            $option = $cache['generator']($this->access, $object, $option) ;
        } else {
            $option = $cache['generator']($this->access, $option) ;
        }
        
        return $this->router->generate( $cache['name'], $option ) ;
    }
    
    
    public function appPathWithObject($name, $object, array $options = array() ){
        if( null === $this->loader ) {
            $this->loader = $this->container->get('app.admin.loader') ;
            $this->router = $this->container->get('router') ;
            $this->access = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor() ;
        }
        if( !isset($this->admin[$name]) ) {
            throw new \Exception(sprintf("route name `%s` not exists!", $name));
        }
        $options = $this->admin[$name]["generator"]($this->access, $this->loader, $object, $options);
        
        return $this->router->generate($name, $options ) ;
    }
    
    public function appPathWithoutObject($name, array $options = array() ){
        if( null === $this->loader ) {
            $this->loader = $this->container->get('app.admin.loader') ;
            $this->router = $this->container->get('router') ;
            $this->access = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor() ;
        }
        if( !isset($this->admin[$name]) ) {
            throw new \Exception(sprintf("route name `%s` not exists!", $name));
        }
        $options = $this->admin[$name]["generator"]($this->access, $this->loader, $options);
        
        return $this->router->generate($name, $options ) ;
    }
}
