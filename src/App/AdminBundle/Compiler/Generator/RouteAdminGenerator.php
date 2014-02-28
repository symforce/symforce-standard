<?php

namespace App\AdminBundle\Compiler\Generator ;

use Symfony\Component\Routing\RouteCollection ;

/**
 * @author loong
 */
class RouteAdminGenerator {
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminCache
     */
    protected $admin ;
    
    /**
     * @var \App\AdminBundle\Compiler\Loader\RouteCacheLoader
     */
    protected $loader ;
    
    /**
     * @var string
     */
    protected $admin_name ;
    
    /**
     * @var array
     */
    protected $children ;
    
    /**
     * @var array
     */
    protected $tree ;
    
    public function __construct(\App\AdminBundle\Compiler\Loader\RouteCacheLoader $loader, \App\AdminBundle\Compiler\Cache\AdminCache $admin) {
        $this->loader   = $loader ;
        $this->admin    = $admin ;
        $this->admin_name    = $admin->getName() ;
        
        $route_children    = self::getPropertyValue($this->admin , '_admin_route_children') ;
        if( $route_children ) {
            foreach($route_children as $child_admin_name => $config ) {
                if( $config[0] ) {
                    // multi , with property
                    foreach($config[1] as $_config ) {
                        $child_admin_property  = $_config[0] ;
                        $this_property  = $_config[1] ;
                        $this->children[$child_admin_name][]  = array( $child_admin_property, $this_property,  1 );
                    }
                } else {
                    $_config    = $config[1] ;
                    $child_admin_property  = $_config[0] ;
                    $this_property  = $_config[1] ;
                    $this->children[$child_admin_name][]  = array($child_admin_property, $this_property,  0 );
                }
            }
        }
    }
    
    static private function getPropertyValue($object, $property ){
        static $cache   = array() ;
        $class  = get_class($object) ;
        if( !isset($cache[$class]) ) {
            $cache[$class]  = new \ReflectionClass( $class ) ;
        }
        $prop   = $cache[$class]->getProperty($property) ;
        $prop->setAccessible(true);
        return $prop->getValue($object) ;
    }


    public function generate(\Symfony\Component\Routing\RouteCollection $route_admin_collection) {
        $route_root = self::getPropertyValue($this->admin , '_admin_route_root') ;
        
        if( !$route_root ) {
            return ;
        }
        
        $this->admin->setRouteParent() ;
        $this->generatePath($route_admin_collection) ;
        
        $writer  = $this->loader->getCompileAppAdminWriter() ;
        
        if( count($this->children) ) {
            $writer->indent() ;
            foreach($this->children as $child_admin_name => $list ) {
                foreach($list as $config ) {
                    $child_admin_property   = $config[0] ;
                    // $this_property  = $config[1] ;
                    $child = $this->loader->getAdminRouteGenerator( $child_admin_name ) ;
                    $child->admin->setRouteParent( $this->admin ) ;
                    $child->admin->setRouteParentProperty( $child_admin_property ) ;
                    $child->generatePath($route_admin_collection) ;
                }
            }
            $writer->outdent() ;
        }
    }
    
    private function isSingleChild($name) {
        if( isset( $this->children[$name]) ) {
            return  1 === count($this->children[$name]) ;
        }
        throw new \Exception(sprintf(" admin `%s` do not has admin child `%s`", $this->admin_name, $name )) ;
    }

    public function generatePath(\Symfony\Component\Routing\RouteCollection $route_admin_collection) {
        $writer  = $this->loader->getCompileAppAdminWriter() ;
        
        $writer->writeln(sprintf("// %s,    %s", $this->admin_name, $this->admin->getClassName() )) ;
        $writer->indent() ;
        $action_maps = self::getPropertyValue($this->admin , 'action_maps') ;
        foreach( $action_maps as $action_name => $action_cache_name ) {
            $action = $this->admin->getAction( $action_name ) ;
            $this->generateActionPath( $action , $route_admin_collection );
        }
        $writer->outdent() ;
        
        if( count($this->children) ) {
            $writer->indent() ;
            foreach($this->children as $child_admin_name => $list ) {
                foreach($list as $config ) {
                    $child_admin_property   = $config[0] ;
                    $child = $this->loader->getAdminRouteGenerator( $child_admin_name ) ;
                    $child->admin->setRouteParent( $this->admin ) ;
                    $child->admin->setRouteParentProperty( $child_admin_property ) ;
                    $child->generatePath($route_admin_collection) ;
                }
            }
            $writer->outdent() ;
        }
    }
    
    public function generateActionPath(\App\AdminBundle\Compiler\Cache\ActionCache $action, \Symfony\Component\Routing\RouteCollection $route_admin_collection ) {
        $class  = $this->loader->getCompileClass() ;
        $writer  = $this->loader->getCompileAppAdminWriter() ;
        
        $route_name     = $action->getAdminRouteName() ;
        $admin_name     = $this->admin->getName();
        $admin_class     = $this->admin->getClassName() ;
        $generator      = new \App\AdminBundle\Compiler\Generator\PhpWriter();
        $dispatcher     = new \App\AdminBundle\Compiler\Generator\PhpWriter();
        
        $requirement    = array() ;
        
        $generator->write('function(PropertyAccessorInterface $accessor, AdminLoader $loader, ');
        if( $action->isRequestObject() ) {
            $generator->write( sprintf('%s $%s_object, ', '\\' . $admin_class , $admin_name ) ) ;
        }
        $generator->writeln('array $options = array()){')->indent() ;
        
        $dispatcher->writeln('function(AdminLoader $loader, Request $request){')->indent();
        $dispatcher->writeln( sprintf('$loader->setRouteAdminAction("%s", "%s");', $admin_name, $action->getName() ) ) ;
        $dispatcher->writeln('$_app_admin_route_parameters = array();');
        $dispatcher->writeln('$_app_admin_route_parents = array();');
        
        $path   = array() ;
    
        $route_generator_parents  = array() ;
        $this->getRoutePatentGenerator( $route_generator_parents ) ;
        
        foreach($route_generator_parents as $route_parent_generator) {
            $route_parent_generator->generateRouteDispatcher($dispatcher, $requirement, $path );
        }
        $dispatcher->writeln( sprintf('$%s = $loader->getAdminByClass("%s");', $admin_name , $admin_class) ) ;
        
        $dispatcher->writeln( sprintf('$%s->setRouteParameters($_app_admin_route_parameters);', $admin_name) ) ;
        $dispatcher->writeln( sprintf('$%s->setRouteParents($_app_admin_route_parents);', $admin_name) ) ;
        
        $route_parent = $this->admin->getRouteParent() ;
        if( $route_parent ) {
            $admin_name     = $this->admin->getName();
            $dispatcher->writeln( sprintf('$%s->setRouteParent($%s);', $admin_name , $route_parent->getName() ) ) ;
            $dispatcher->writeln( sprintf('$%s->setRouteParentProperty("%s");', $admin_name , $this->admin->getRouteParentProperty() ) ) ;
        }
        
        if( $action->isRequestObject() ) {
            $dispatcher->writeln( sprintf('$%s->setRouteObjectId( $request->get("%s_id") );', $admin_name , $admin_name ) ) ;
        }
        $dispatcher->writeln( sprintf('$action = $%s->getAction("%s");', $admin_name, $action->getName() ) ) ;
        
        if( $action->isRequestObject() ) {
            $generator->writeln( sprintf('$options["%s_id"] = $accessor->getValue($%s_object, "%s") ;', $admin_name, $admin_name , $this->admin->getPropertyIdName() ) ) ;
        }
        
        $this->generateRouteGenerator($generator, $action->isRequestObject() , true ) ;
        
        if( $route_parent ) {
            $route_parent_renerator = $this->loader->getAdminRouteGenerator( $route_parent->getName() ) ;
            if( !$route_parent_renerator->isSingleChild( $this->admin_name ) ) {
                $path[] = 'of_' . $this->admin->getRouteParentProperty() ;
            }
        }
        
        $path[] = $admin_name ;
        if( $this->admin->tree ) {
            $id = 'admin_tree_parent' ;
            $requirement[$id]   = '\d+' ;
            $generator->writeln(sprintf('if( !isset($options["%s"]) ) $options["%s"] = 0;', $id, $id));
            $path[] = sprintf('{%s}', $id) ;
            $dispatcher->writeln(sprintf('$%s->setTreeObjectId( $request->get("%s") );', $admin_name, $id ));
        }
        
        if( $this->admin->workflow ) {
            $id = 'admin_route_workflow' ;
            $requirement[$id]   = '\w+' ;
            $path[] = sprintf('{%s}', $id) ;
            $dispatcher->writeln(sprintf('$%s->setRouteWorkflow( $request->get("%s") );', $admin_name, $id ));
            $generator->writeln(sprintf('if( !isset($options["%s"]) ) $options["%s"] = $loader->getAdminByClass("%s")->getRouteWorkflow();', $id, $id, $admin_class )) ;
        }

        $owner  = self::getPropertyValue($this->admin, 'property_owner_name');
        if( $owner ) {
            
            $id = 'admin_route_owner_identity' ;
            $requirement[$id]   = '\w+' ;
            $path[] = sprintf('{%s}', $id) ;
            $dispatcher->writeln(sprintf('$%s->setRouteOwnerIdentity( $request->get("%s") );', $admin_name, $id ));
            $generator->writeln(sprintf('if( !isset($options["%s"]) ) $options["%s"] = $loader->getAdminByClass("%s")->getRouteOwnerIdentity();', $id, $id, $admin_class )) ;
            
            $id = 'admin_route_owner_id' ;
            $requirement[$id]   = '\d+' ;
            $path[] = sprintf('{%s}', $id) ;
            $dispatcher->writeln(sprintf('$%s->setRouteOwnerId( $request->get("%s") );', $admin_name, $id ));
            $generator->writeln(sprintf('if( !isset($options["%s"]) ) $options["%s"] = $loader->getAdminByClass("%s")->getRouteOwnerId();', $id, $id, $admin_class )) ;
            
        }
        
        $path[] = $action->getName() ;
        if( $action->isRequestObject() ) {
            $path[] = sprintf('{%s_id}', $admin_name) ;
        }
        
        if( $action->isListAction() ) {
            
            $id = 'admin_list_page' ;
            $requirement[$id]   = '\d+' ;
            $generator->writeln(sprintf('if( !isset($options["%s"]) ) $options["%s"] = 1;', $id, $id));
            $path[] = sprintf('{%s}.html', $id) ;
            $dispatcher->writeln(sprintf('$action->setPageNumber( $request->get("%s") );', $id ));
            
        }
        $path   = '/' . join('/', $path) ;
        
        
        $route_config  = array(
            '_controller'   => 'App\AdminBundle\Controller\AdminController::adminAction' ,
            '_app_route_name'  => $route_name , 
        );
        
        $route = new \Symfony\Component\Routing\Route( $path , $route_config , $requirement ) ;
        $route_admin_collection->add($route_name, $route );
        
        
        
        
        $dispatcher->writeln('return $action;');
        $generator->writeln(sprintf('// path = %s ', $path));
        $generator->writeln('return $options;');
        
        $writer
                ->write( sprintf('$this->admin["%s"]["generator"] = ', $route_name) ) 
                ->indent()
                 ->write( $generator->getContent() )
                ->outdent()
                ->writeln("};");
                ;
        $writer
                ->write( sprintf('$this->admin["%s"]["dispatcher"] = ', $route_name) ) 
                ->indent()
                 ->write( $dispatcher->getContent() )
                ->outdent()
                 ->writeln( '};')
                ;
    }
    
    public function getRoutePatentGenerator( array & $parents ) {
        $route_parent = $this->admin->getRouteParent() ;
        if( $route_parent ) {
            $route_parent_generator = $this->loader->getAdminRouteGenerator( $route_parent->getName()) ;
            array_unshift($parents, $route_parent_generator );
            $route_parent_generator->getRoutePatentGenerator( $parents ) ;
        }
    }
    
    public function generateRouteDispatcher(\App\AdminBundle\Compiler\Generator\PhpWriter $dispatcher, array & $requirement, array & $path ) {
        $admin_name = $this->admin->getName() ;
        $admin_object_id  = $admin_name . '_id' ;
        
        $dispatcher->writeln( sprintf('$%s = $loader->getAdminByClass("%s");', $admin_name , $this->admin->getClassName() ) ) ;
        $dispatcher->writeln( sprintf('$_app_admin_route_parents["%s"] = $%s;', $admin_name , $admin_name, $admin_name ) ) ;
        
        $dispatcher->writeln( sprintf('$object_id   = $request->get("%s") ;', $admin_object_id ) ) ;
        $dispatcher->writeln( sprintf('$_app_admin_route_parameters["%s"] = $object_id ;', $admin_object_id) ) ;
        $dispatcher->writeln( sprintf('$%s->setRouteObjectId($object_id);', $admin_name , $admin_name ) ) ;
        $dispatcher->writeln( sprintf('$%s->setRouteParameters($_app_admin_route_parameters);', $admin_name) ) ;
        
        $route_parent = $this->admin->getRouteParent() ;
        if( $route_parent ) {
            $dispatcher->writeln( sprintf('$%s->setRouteParent($%s);', $admin_name , $route_parent->getName() ) ) ;
            $dispatcher->writeln( sprintf('$%s->setRouteParentProperty("%s");', $admin_name , $this->admin->getRouteParentProperty() ) ) ;
            
            $route_parent_generator = $this->loader->getAdminRouteGenerator( $route_parent->getName() ) ;
            if( ! $route_parent_generator->isSingleChild( $this->admin_name ) ) {
                $path[] = 'of_' . $this->admin->getRouteParentProperty() ;
            }
        }
        
        $path[] = $admin_name ;
        $path[] = '{' . $admin_object_id . '}' ;
        $requirement[ $admin_object_id ] = '\d+' ;
    }
    
    public function generateRouteGenerator(\App\AdminBundle\Compiler\Generator\PhpWriter $generator, $use_object, $is_first = false ) {
        $admin_name = $this->admin->getName() ;
        $route_parent = $this->admin->getRouteParent() ;
        if( $route_parent ) {
            if( $is_first ) {
                $generator->writeln(sprintf('$%s = $loader->getAdminByClass("%s");', $admin_name , $this->admin->getClassName() ) ) ;
                $generator->writeln(sprintf('$_app_admin_route_parameters = $%s->getRouteParameters();', $admin_name));
            }
            $route_parent_name = $route_parent->getName() ;
            $parent_property    = $this->admin->getRouteParentProperty() ;
            
            $generator->writeln(sprintf('if( !isset($options["%s_id"] )) {', $route_parent_name))
                      ->indent()
                    ;
            if( $use_object ) {
                $generator
                        ->writeln( sprintf('$%s_object = $%s_object ? $accessor->getValue($%s_object, "%s") : null ;', $route_parent_name, $admin_name , $admin_name, $parent_property ) ) 
                        ->writeln( sprintf('if( $%s_object ) {', $route_parent_name) )
                            ->indent()
                            ->writeln( sprintf('$options["%s_id"] = $accessor->getValue($%s_object, "%s") ;', $route_parent_name, $route_parent_name, $route_parent->getPropertyIdName() ) ) 
                            ->outdent()
                        ->writeln( sprintf('} else if( isset($_app_admin_route_parameters["%s_id"] )) {', $route_parent_name) )
                            ->indent()
                            ->writeln( sprintf('$options["%s_id"] = $_app_admin_route_parameters["%s_id"] ;', $route_parent_name, $route_parent_name ) )
                            ->outdent()
                        ->writeln( '} else {' )
                            ->indent()
                            ->writeln( sprintf(' $options["%s_id"] = $loader->getAdminByClass("%s")->getRouteObjectId() ;', $route_parent_name, $route_parent->getClassName() ) )
                            ->outdent()
                        ->writeln( '}' )
                        ;
                
            } else {
                 $generator
                        ->writeln( sprintf('if( isset($_app_admin_route_parameters["%s_id"] )) {', $route_parent_name) )
                            ->indent()
                            ->writeln( sprintf(' $options["%s_id"] = $_app_admin_route_parameters["%s_id"] ;', $route_parent_name, $route_parent_name ) )
                            ->outdent()
                        ->writeln( '} else {' )
                            ->indent()
                            ->writeln( sprintf(' $options["%s_id"] = $loader->getAdminByClass("%s")->getRouteObjectId() ;', $route_parent_name, $route_parent->getClassName() ) )
                            ->outdent()
                         ->writeln( '} ' )
                        ;
            }
            
              $generator
                      ->outdent()
                      ->writeln('}');
            
            $route_parent_generator = $this->loader->getAdminRouteGenerator( $route_parent_name ) ;
            $route_parent_generator->generateRouteGenerator($generator, $use_object ) ;
        }
    }
}

