<?php

namespace App\AdminBundle\Compiler\Loader ;

/**
 * Description of WebPageLoader
 *
 * @author loong
 */
class RouteCacheLoader {
    
    /**
     * @var AdminLoader
     */
    private $loader ;
    
    /**
     * @var array
     */
    protected $config ;

    /**
     * @var array
     */
    private $page_action_alias = array() ;
    
    /**
     * @var array
     */
    private $route_file_resources = array() ;
    
    /**
     * @var array
     */
    private $page_generators  = array() ;
    
    /**
     * @var array
     */
    private $page_actions_map  = array() ;
    /**
     * @var array
     */
    private $page_path_map  = array() ;
    
    /**
     * @var array
     */
    private $page_route_map  = array() ;
    
    /**
     * @var array
     */
    private $page_dispatch_map  = array() ;
    
    /**
     * @var array
     */
    private $controler_owner  = array() ;
    
    /**
     * @var array
     */
    private $controler_owners  = array() ;
    
    /**
     * @var array
     */
    private $other_controler_actions  = array() ;
    
    /**
     * @var \Symfony\Component\Routing\RouteCollection 
     */
    private $route_page_collection ;

    /**
     * @var \App\AdminBundle\Compiler\Generator\PhpClass
     */
    private   $_compile_class ;
    
    
    /**
     * @var array
     */
    private $admin_route_generators  = array() ;
    
    /**
     * @var string
     */
    private $route_cache_path ;
    
    public function __construct(AdminLoader $loader, $route_cache_path ) {
        $this->loader   = $loader ;
        $this->route_cache_path = $route_cache_path ;
    }
    
    /**
     * @param string $file
     */
    public function addFileResource( $file ) {
        if( !isset($this->route_file_resources[$file]) ) {
            $this->route_page_collection->addResource( new \Symfony\Component\Config\Resource\FileResource( $file ) ) ;
            $this->route_file_resources[$file]  = true ;
        }
    }
    
    /**
     * @param string $admin_name
     * @return \App\AdminBundle\Compiler\Generator\RouteWebPageGenerator
     */
    public function getPageGeneratorByName( $admin_name ) {
        if( !$this->loader->hasAdminName($admin_name) ) {
            return null ;
        }
        return $this->getPageGeneratorByClass( $this->loader->getClassByAdminName($admin_name) ) ; 
    }
    
    /**
     * @param string $admin_name
     * @return \App\AdminBundle\Compiler\Generator\RouteWebPageGenerator
     */
    public function getPageGeneratorByClass( $admin_class ) {
        if( !isset($this->page_generators[$admin_class]) ) {
            if( null === $this->route_page_collection ) {
                throw new \Exception('should call getRouteCollection first');
            }
            if( !isset( $this->config[$admin_class]) ) {
                return null ;
            }
            $class  = $this->config[ $admin_class ] ;
            $this->page_generators[$admin_class] = new $class( $this , $this->loader ) ;
        }
        return $this->page_generators[$admin_class] ;
    }
    
    public function getControlerOwnerName(\ReflectionMethod $m){
        $controller = $m->getDeclaringClass()->getName() ;
        $_controller = strtolower($controller) ;
        if( isset($this->controler_owner[$_controller]) ) {
            return $this->controler_owner[$_controller] ;
        }
        if( !isset($this->controler_owners[$_controller]) ) {
            throw new \Exception(sprintf("%s->%s has big error, no owners record", $controller, $m->getName()));
        }
        $owners = $this->controler_owners[$_controller] ;
        if( count($owners) > 1 ) {
            throw new \Exception(sprintf("%s->%s has multi owner(%s)", $controller, $m->getName(), join(',', $owners) ));
        }
        return $owners[0] ;
    }
    
    public function addOtherControlerAction($admin_name, $action_name, \ReflectionMethod $m){
        $fn     = $m->getDeclaringClass()->getName() . '->' . $m->getName() ;
        if( isset($this->other_controler_actions[$admin_name][$action_name] ) && $fn !== $this->other_controler_actions[$admin_name][$action_name]  ) {
            $_fn = $this->other_controler_actions[$admin_name][$action_name]  ;
            throw new \Exception(sprintf("`%s:%s` duplicate(%s,%s)", $admin_name, $action_name, $fn, $_fn));
        }
        $this->other_controler_actions[$admin_name][$action_name] = $fn ;
    }
    
    public function addPageAction($admin_name, $action_name, \ReflectionMethod $m) {
        $fn = $m->getDeclaringClass()->getName() . ':' . $m->getName() ;
        $name   = $admin_name . ':' . $action_name ;
        if( isset($this->page_actions_map[ $name ]) ) {
            throw new \Exception(sprintf("page action `%s` duplicate(%s,%s)", $name, $fn ,  $this->page_actions_map[ $name ] ));
        }
        $this->page_actions_map[$name] = $fn ; 
    }
    
    public function addPageAlias( $key , $value ){
        $this->page_action_alias[$key] = $value ;
    }
    
    public function getPageAction($admin_name, $action_name){
        $name   = $admin_name . ':' . $action_name ;
        if( isset($this->page_actions_map[ $name ]) ) {
            return $this->page_actions_map[ $name ] ;
        }
    }
    
    public function addPageRoute($admin_name, $action_name, \Symfony\Component\Routing\Route $route, \ReflectionMethod $m, \App\AdminBundle\Compiler\Annotation\Route $annot ) {
        if( null === $this->route_page_collection ) {
             throw new \Exception('should call getRouteCollection first');
        }
        
        $_path   = array(
            's' => $route->getSchemes() ,
            'h' => $route->getHost() ,
            'p' => $route->getPath() ,
            'm' => $route->getMethods() ,
        );
        
        $path   = json_encode($_path) ;
        
        $admin_action   = $admin_name . ':' . $action_name ;
        
        if( isset($this->page_path_map[$path]) ) {
            $_admin_action  = $this->page_path_map[$path] ;
            throw new \Exception( sprintf( "web page path:`%s` duplicate(%s,%s)", $route->getPath() , $this->page_actions_map[$admin_action], $this->page_actions_map[$_admin_action] ) );
        }
        $this->page_path_map[$path] = $admin_action ;
        
        
        if( isset($this->page_route_map[ $annot->name ]) ) {
            $_admin_action  = $this->page_route_map[ $annot->name ] ;
            throw new \Exception( sprintf( "web page route name:`%s` duplicate method(%s,%s), action(%s,%s)", $annot->name , 
                    $this->page_actions_map[$admin_action], $this->page_actions_map[$_admin_action],
                    $admin_action, $_admin_action
                    ) );
        }
        $this->page_route_map[ $annot->name ] = $admin_action ;
        
        $this->route_page_collection->add($annot->name, $route );
        
        $this->page_dispatch_map[ $admin_action ] = array(
            /*
            'controller'    => $m->getDeclaringClass()->getName() ,
            'method'    => $m->getName() ,
            */
            'name'    => $annot->name ,
            'path'    => $route->getPath() ,
            'requirements'   => $route->getRequirements() ,
            'entity'    => $annot->entity ,
            'template'    => $annot->template ,
            'generator'    => null ,
            'dispatcher'    => null ,
        );
    }
    
    /**
     * @return bool
     */
    private function loadFromCache() {
        if( !file_exists($this->route_cache_path) ) {
            return __LINE__ ;
        }
        $cache  = include($this->route_cache_path) ;
        if( !$cache || !isset($cache[2])) {
            return __LINE__;
        }
        if( $this->loader->getCacheExpiredTime() > $cache[0] ) {
            return __LINE__ ;
        }
        foreach($cache[1] as $filename ) {
            if( !file_exists($filename) ) {
                return __LINE__ ;
            }
            if( filemtime($filename) > $cache[0] ) {
                return __LINE__ ;
            }
        }
        $this->route_page_collection = $cache[2] ;
        return 0 ; 
    }

    /** 
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection() {
        if( null === $this->route_page_collection ) {
            $cache_expired  = $this->loadFromCache() ;
            if( ! $cache_expired ) {
                return $this->route_page_collection ;
            }
            //\Dev::dump($cache_expired); exit;
            
            $this->loader->getService('app.admin.compiler')->set( \App\AdminBundle\Compiler\Loader\Compiler::STAT_ROUTE );
            
            $this->config   = array();
            if( $this->loader->hasConfig('web_page_class') ) {
                $this->loader->getConfig('web_page_class') ;
            }
            $this->route_page_collection = new \Symfony\Component\Routing\RouteCollection() ;
            
            $route_admin_collection = new \Symfony\Component\Routing\RouteCollection() ;
            
            $class  = $this->getCompileClass() ;
            
            if( $this->config ) {
                foreach($this->config as $admin_class => $page_class) {
                    $page_generator = $this->getPageGeneratorByClass($admin_class) ;
                    $admin_name = $page_generator->getAdminName() ;
                    $controller = strtolower( $page_generator->getController() ) ;
                    if( $page_generator->isOwnController() ) {
                        if( isset($this->controler_owner[ $controller ]) ) {
                            throw new \Exception(sprintf("`%s` owner duplicate (%s,%s)", $controller, $admin_class, $this->controler_owner[ $controller ]) );
                        }
                        $this->controler_owner[ $controller ] = $admin_name ;
                    }
                    if( !isset($this->controler_owners[$controller]) ) {
                        $this->controler_owners[$controller] =  array() ;
                    }
                    $this->controler_owners[$controller][] = $admin_name ;
                }
            }
            
            foreach($this->page_generators as $page_generator) {
                 $page_generator->generate() ;
            }
            
            foreach($this->page_generators as $page_generator) {
                $page_generator->fixAlias() ;
            }
            
            foreach($this->other_controler_actions as $admin_name => $action_list ) {
                foreach($action_list as $action_name => $fn ) {
                    $admin_action   = $admin_name . ':' . $action_name ;
                    if( !isset($this->page_actions_map[ $admin_action ]) ) {
                        throw new \Exception(sprintf("%s not loaded for %s", $fn , $admin_action )) ;
                    }
                }
            }
            
            $default_controller  = new \ReflectionClass( \App\AdminBundle\Compiler\MetaType\Admin\Page::PAGE_CONTROLLER_CLASS );
            foreach($this->page_generators as $page_generator) {
                $admin_name = $page_generator->getAdminName() ;
                $admin_action = $admin_name . ':index' ;
                if( !isset($this->page_actions_map[ $admin_action ]) && !isset($this->page_action_alias[$admin_action]) ) {
                    $annot = new \App\AdminBundle\Compiler\Annotation\Route(array(
                        'admin' => $admin_name ,
                        'action' => 'index' ,
                        'template' => 'AppAdminBundle:WebPage:index.html.twig' ,
                    ));
                    $page_generator->addRoute( $default_controller->getMethod('defaultIndexAction'), $annot); 
                }
                $admin_action = $admin_name . ':view' ;
                if( !isset($this->page_actions_map[ $admin_action ]) && !isset($this->page_action_alias[$admin_action]) ) {
                    $annot = new \App\AdminBundle\Compiler\Annotation\Route(array(
                        'admin' => $admin_name ,
                        'action' => 'view' ,
                        'entity'   => true ,
                        'template' => 'AppAdminBundle:WebPage:view.html.twig' ,
                    ));
                    $page_generator->addRoute( $default_controller->getMethod('defaultViewAction'), $annot); 
                }
            }
            
            $class->addProperty('route', $this->page_route_map );
            $class->addProperty('alias', $this->page_action_alias );
            $class->addProperty('cache', $this->page_dispatch_map );
            
            // add admin route 
            foreach($this->loader->getAdminMaps() as $admin_class => $admin_cache_calss ){
                $admin  = $this->loader->getAdminByClass( $admin_class ) ;
                $this->route_page_collection->addResource( new \Symfony\Component\Config\Resource\FileResource( $admin->getReflectionClass()->getFileName() ) ) ;
                $this->admin_route_generators[ $admin->getName() ] = new \App\AdminBundle\Compiler\Generator\RouteAdminGenerator( $this, $admin ) ;
            }
            
            foreach($this->admin_route_generators as $admin_name => $admin_route_generator){
                $admin_route_generator->generate($route_admin_collection) ;
            }
            
            $route_admin_collection->addPrefix('/admin/app') ;
            $this->route_page_collection->addCollection($route_admin_collection) ; 
            
            $class->writeCache() ;
            
            $this->loader->getService('app.admin.compiler')->set( \App\AdminBundle\Compiler\Loader\Compiler::STAT_OK );
            
            $content_cache  = array( time(), array_keys($this->route_file_resources), $this->route_page_collection) ;
            $content   = '<' . '?php return unserialize(' . var_export(serialize($content_cache), 1) . ');' ;
            \Dev::write_file($this->route_cache_path, $content) ;
        }
        return $this->route_page_collection ;
    }
    
    
    
    public function getAdminRouteGenerator( $admin_name ) {
        if(  !is_string($admin_name) || !isset($this->admin_route_generators[ $admin_name ] ) ) { 
            throw new \Exception(sprintf("`%s` not exists", $admin_name));
        }
        return $this->admin_route_generators[ $admin_name ] ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function getCompileClass() {
        if( null === $this->_compile_class ) {
            $class = new \App\AdminBundle\Compiler\Generator\PhpClass() ;
            $class
                ->setName( 'AppAdminCache\WebPageService' )
                ->setParentClassName( '\App\AdminBundle\Compiler\Cache\WebPageCache' )
                ->addUseStatement('Symfony\Component\PropertyAccess\PropertyAccessorInterface')
                ->addUseStatement('Symfony\Component\HttpFoundation\Request')
                ->addUseStatement('App\AdminBundle\Compiler\Loader\AdminLoader')
                ->addUseStatement('App\AdminBundle\Controller\AdminController')
                ->setFinal(true)
                ; 
            $this->_compile_class  = $class ;
        }
        return  $this->_compile_class ;
    }
    
    public $_compile_app_admin_writer = null ;
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    public function getCompileAppAdminWriter() {
        if( null === $this->_compile_app_admin_writer ) {
            $class  = $this->getCompileClass() ;
            $fn  = $class->addMethod('loadAppAdminRoute')
                ->setVisibility('public')
                ;
            $this->_compile_app_admin_writer    = $fn->getWriter() ;
        } 
        return $this->_compile_app_admin_writer ;
    }
    
}
