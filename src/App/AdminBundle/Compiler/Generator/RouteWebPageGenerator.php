<?php

namespace App\AdminBundle\Compiler\Generator ;


use App\AdminBundle\Compiler\CacheObject\Menu ;

/**
 *
 * @author loong
 */
class RouteWebPageGenerator {
    //put your code here
    
    const ROUTE_ANNOT_CLASS   = 'App\AdminBundle\Compiler\Annotation\Route' ;
    const SYMFONY_ROUTE_ANNOT_CLASS = 'Sensio\Bundle\FrameworkExtraBundle\Configuration\Route' ;
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminCache
     */
    protected $admin ;
    
    /**
     * @var string
     */
    protected $admin_name ;
    
    /**
     * @var string
     */
    protected $admin_class ;
    
    /**
     * @var string
     */
    protected $controller ;
    
    /**
     * @var string
     */
    protected $controller_admin_name ;

    /**
     * @var string
     */
    protected $property_page_name ;
    
    /**
     * @var bool
     */
    protected $page_one2one_map ;

    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminCache
     */
    protected $parent_admin ;
    
    /**
     * @var string
     */
    protected $page_parent_entity ;
    
    /**
     * @var string
     */
    protected $page_title_property ;
    /**
     * @var string
     */
    protected $page_description_property ;
    
    /**
     * @var string
     */
    protected $page_keywords_property ;
    
    /**
     * @var string
     */
    protected $route_path ;
    
    /**
     * @var array
     */
    protected $action_alias = array() ;
    
    /**
     * @var array
     */
    protected $action_cache = array() ;
    
    /**
     * @var string
     */
    protected $eneity_id_name ;
    
    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader 
     */
    protected $loader ;
    
     /**
     * @var \App\AdminBundle\Compiler\Loader\RouteCacheLoader
     */
    protected $page_loader ;
    
    protected $err_msg ;

    public function __construct(\App\AdminBundle\Compiler\Loader\RouteCacheLoader $page_loader, \App\AdminBundle\Compiler\Loader\AdminLoader $loader) {
        
        $this->page_loader  = $page_loader ;
        $this->loader   = $loader ;
        $this->admin    = $loader->getAdminByClass( $this->admin_class ) ;
        
        if( !$this->route_path ) {
            $this->route_path   = $this->admin_name ;
        }
        
        if( $this->page_parent_entity ) {
            $this->parent_admin = $loader->getAdminByClass( $this->page_parent_entity ) ;
        }
        
        if( $this->controller ) {
            $controller_entity_name    = preg_replace('/\\\\Controller\\\\(\w+)Controller$/', '\\\\Entity\\\\\\1', $this->controller ) ;
            if( $this->loader->hasAdminClass($controller_entity_name) ) {
                $this->controller_admin_name    = $this->loader->getAdminByClass($controller_entity_name)->getName() ;
            }
        }
        $this->eneity_id_name   = $this->admin_name .'_id' ;
        
        /*
        $this->page_loader->addFileResource( $this->admin->getReflectionClass()->getFileName() ) ;
        */
    }
    
    public function getAdminName(){
        return $this->admin_name ;
    }
    
    public function getController(){
        return $this->controller ;
    }
    
    public function isOwnController(){
        return $this->controller_admin_name === $this->admin_name ;
    }
    
    public function generate() {
        /**
         * @var \Doctrine\Common\Annotations\FileCacheReader
         */
        $reader    =  $this->admin->getService('annotation_reader') ;
        if( $this->controller ) {
            $rc = new \ReflectionClass($this->controller); 
            foreach($rc->getMethods() as $m) if( $m instanceof \ReflectionMethod ) {
                if( $m->isAbstract() || $m->isAbstract() || $m->isConstructor()|| $m->isDestructor () || $m->isStatic() ) {
                    continue ;
                }
                $_as = $reader->getMethodAnnotations($m) ;
                $as  = array() ;
                foreach($_as as $annot ) {
                    $_class = get_class($annot) ;
                    if( isset($as[$_class]) ) {
                        if( is_array($as[$_class]) ) {
                            $as[$_class][]  = $annot ;
                        } else {
                            $as[$_class]  = array( $as[$_class], $annot ) ;
                        }
                    } else {
                        $as[$_class]  = $annot ;
                    }
                }
                if( !isset($as[self::ROUTE_ANNOT_CLASS]) ) {
                    continue;
                }
                if( isset($as[self::SYMFONY_ROUTE_ANNOT_CLASS]) ) {
                    throw new \Exception(sprintf("%s (AdminRoute, SymfonyRoute) duplicate ",  $this->err_msg ) );
                }
                $annot = $as[self::ROUTE_ANNOT_CLASS] ;
                if( $this->addRoute($m, $annot, $as) ) {
                    $this->page_loader->addFileResource( $rc->getFileName() ) ; 
                }
            }
        } 
        
        
    }
    
    private function addAlias($alias_action, $ref_admin, $ref_action, \ReflectionMethod $m ) {
        $fn = $m->getDeclaringClass()->getName() . '->' . $m->getName() ;
        if( isset($this->action_alias[ $alias_action ]) ) {
             $_fn = $this->page_loader->getPageAction( $ref_admin , $ref_action ) ;
             throw new \Exception(sprintf("web page action `%s:%s` duplicate(alias: %s,%s) ", $this->admin_name, $alias_action, $fn, $_fn ) );
        }
        $this->action_alias[ $alias_action ] =  array( 
            'admin' => $ref_admin ,  
            'action' => $ref_action , 
            'name'  => $ref_admin . ':' . $ref_action , 
            'controller' => $fn ) ;
    }
    
    public function fixAlias() {
        foreach($this->action_alias as $action_name => $o ) {
            $_fn    = $this->page_loader->getPageAction( $this->admin_name, $action_name) ;
            if( $_fn ) { 
                throw new \Exception(sprintf("web page action `%s:%s` duplicate(alias: %s, %s) ", $this->admin_name, $action_name , $o['controller'], $_fn ) );
            }
            $alias_admin    = $this->page_loader->getPageGeneratorByName( $o['admin'] ) ;
            $annot    = $alias_admin->action_cache[ $o['action'] ] ;
            if( $alias_admin->page_parent_entity === $this->admin_class ) {
                if( $annot->entity ) {
                    throw new \Exception(sprintf("@WebPageRoute(`%s`, entity=true) can not alias to parent @WebPageRoute(`%s:%s`, entity=true) on controller `%s`",  $o['name'], $this->admin_name , $action_name, $o['controller'] ));
                }
            } else if( $this->page_parent_entity === $alias_admin->admin_class ){
                if( !$annot->entity ) {
                    throw new \Exception(sprintf("@WebPageRoute(`%s`, entity=false) can not alias to child @WebPageRoute(`%s:%s`) on controller `%s`",  $o['name'], $this->admin_name , $action_name, $o['controller'] ));
                }
            } else {
                throw new \Exception(sprintf("@WebPageRoute(`%s`) can not alias to @WebPageRoute(`%s:%s`) on controller `%s`",  $o['name'], $this->admin_name , $action_name, $o['controller'] ));
            }
            $this->page_loader->addPageAlias($this->admin_name .':' . $action_name, $o['name'] );
        }
    }

    public function addRoute(\ReflectionMethod $m, \App\AdminBundle\Compiler\Annotation\Route $annot, array $as = null ){
        $this->err_msg = sprintf("%s->%s(@%s): ",  $m->getDeclaringClass()->getName() , $m->getName(), self::ROUTE_ANNOT_CLASS); 
                
        $admin_name = null ;
        if( $annot->admin ) {
            if( !preg_match('/^\w+$/', $annot->admin) ) {
                throw new \Exception( sprintf("%s admin `%s` invalid ", $this->err_msg,$annot->admin  ) ) ;
            }
            $admin_name = $annot->admin ;
        } else {
            $admin_name     = $this->page_loader->getControlerOwnerName($m) ;
        }
        
        $action_name    = null ;
        if( $annot->action ) {
            if( !preg_match('/^\w+$/', $annot->action) ) {
                throw new \Exception( sprintf("%s admin `%s` invalid ", $this->err_msg,$annot->action  ) ) ;
            }
            $action_name    = $annot->action ;
        } else {
            $action_name    = $annot->action ?: strtolower( preg_replace('/Action$/', '',  $m->getName()) ) ;
        }
        
        if( $admin_name !== $this->admin_name ) {
            $this->page_loader->addOtherControlerAction($admin_name, $action_name, $m ) ;
            return ;
        }
        
        $this->page_loader->addPageAction( $admin_name, $action_name, $m );
        
        $controller_name    = basename(str_replace('\\', '/', $m->getDeclaringClass()->getName() )) ;
        
        if( !$annot->name ) {
            $annot->name = $admin_name . '.' . $action_name ;
        }
        
        if( $annot->alias ) {
            if( !preg_match('/^(.+?)\s*\:\s*(.+)$/', $annot->alias, $ms) ) {
                throw new \Exception( sprintf("%s alias `%s` invalid ", $this->err_msg, $annot->alias ) ) ;
            }
            $admin_alias     = $ms[1] ;
            $action_alias    = $ms[2] ;
            if( !$this->loader->hasAdminName( $admin_alias ) ) {
                throw new \Exception(sprintf("%s admin name `%s` not exists in alias `%s`", $this->err_msg, $admin_alias, $annot->alias ) );
            }
            $page_generator_alias   = $this->page_loader->getPageGeneratorByName( $admin_alias ) ;
            $page_generator_alias->addAlias($action_alias, $admin_name, $action_name , $m );
        }
        
        if( !$annot->template ) {
            $annot->template    = $this->admin->getBundleName() . ':' . preg_replace('/Controller$/', '', $controller_name) . ':' . $admin_name . '.' . $action_name . '.html.twig' ;
        }
        
        $requirement_keys = array() ;
        
        if( !$annot->path ) {
            if( 'index' === $action_name && !$annot->entity ) {
                $annot->path = '/' . $this->route_path . '/' ;
            } else {
                if( 'view' === $action_name && null === $annot->entity ) {
                    $annot->entity  = true ; 
                } 
                if( $annot->entity ) {
                    $annot->path = '/' . $this->route_path . '/' . $action_name . '/{' . $this->eneity_id_name .'}' ;
                    $requirement_keys[$this->eneity_id_name] = true ;
                } else {
                    $annot->path = '/' . $this->route_path . '/' . $action_name . '/' ;
                }
            } 
        } else {
            $annot->path    = '/' . ltrim($annot->path, '/' ) ;
            if( preg_match_all('/\{(.+?)\}/', $annot->path, $ms) ) {
                foreach($ms[1] as $requirement_key) {
                    if( isset($requirement_keys[$requirement_key])) {
                        throw new \Exception(sprintf("%s : `%s` duplicate in path `%s`", $this->err_msg, $annot->path, $requirement_key));
                    }
                    $requirement_keys[$requirement_key] = true ;
                }
            }
            if( isset($requirement_keys['entity_id']) ) {
                $annot->path  = str_replace('{entity_id}', '{' . $this->eneity_id_name . '}', $annot->path );
                $annot->entity  = true ;
                $requirement_keys[ $this->eneity_id_name ] =  true ;
                unset($requirement_keys['entity_id']) ;
            }
            if( isset($requirement_keys[$this->eneity_id_name]) ) {
                $annot->entity  = true ;
            }
        }
        
        $generator   = new \App\AdminBundle\Compiler\Generator\PhpWriter();
        $dispatcher   = new \App\AdminBundle\Compiler\Generator\PhpWriter();
        $dispatcher_args    = array() ;
        $entity_object_name = '$' .$this->admin_name ;
        
        $generator->write('function(\Symfony\Component\PropertyAccess\PropertyAccessorInterface $accessor,');
        if( $annot->entity ) {
            $generator->write( sprintf('%s %s, ', '\\' . $this->admin_class, $entity_object_name ) ) ;
        }  else if ( $this->page_parent_entity ) {
            $entity_object_name = '$' . $this->parent_admin->getName() ;
            $generator->write( sprintf('%s %s, ', '\\' . $this->parent_admin->getClassName(), $entity_object_name ) ) ;
        }
        $generator->writeln('array $options = array() ){')->indent() ;
        
        $dispatcher->writeln('function(\Symfony\Component\PropertyAccess\PropertyAccessorInterface $accessor, \\' 
                . $m->getDeclaringClass()->getName() . ' $controller, \Symfony\Component\HttpFoundation\Request $request){')->indent();
        
        $dispatcher->writeln( sprintf('$controller->setPageAdmin("%s");', $this->admin_name ) ) ;
        
        $requirements  = array() ;
        $requirements_entitys  = array() ;
        $route_config  = array(
            '_controller'   => $m->getDeclaringClass()->getName() . '::dispatchAction' ,
            '_app_web_page'  => $admin_name . ':' . $action_name , 
        );
        
        $path   = $this->getRoutePath( $requirements_entitys, $requirements, $requirement_keys, 
                    $generator, $dispatcher , $annot->entity , $annot->path ) ;
        
        $defaults   = array() ;
        if( $annot->defaults ) {
            $defaults   = $annot->defaults ;
        }
        foreach($requirements_entitys as $requirement_key) { 
            if( isset($requirement_keys[$requirement_key])) unset($requirement_keys[$requirement_key]) ;
        }
        
        if($annot->requirements) foreach($annot->requirements as $requirement_key => $value ) {
            if( isset($requirements[$requirement_key]) ) {
                throw new \Exception( sprintf("%s `%s` duplicate requirements:%s ",  $this->err_msg, $path, $requirement_key ) ) ;
            }
            $requirements[$requirement_key] = $value ;
        }
        
        $add_default = function($i, $name, $value) use(&$dispatcher_args, $generator ){
            $dispatcher_args[$i]    = sprintf('$request->get("%s", %s)', $name , var_export($value, 1) ) ;
            $generator->writeln( sprintf(' if( !isset($options["%s"]) ) $options["%s"] = %s;', $name, $name, var_export($value, 1) )) ;
        };
        
        foreach($m->getParameters()  as $i => $p) {
            $requirement_key   = $p->getName() ;
            if( isset($requirement_keys[$requirement_key]) ) {
                if( isset($defaults[$requirement_key]) ) {
                    $add_default($i, $requirement_key, $defaults[$requirement_key]);
                    unset($defaults[$requirement_key]);
                } else if( $p->isDefaultValueAvailable() ) {
                    $add_default($i, $requirement_key, $p->getDefaultValue());
                } else {
                    $dispatcher_args[$i]    = sprintf('$request->get("%s")', $p->getName() );
                }
                unset($requirement_keys[$requirement_key]) ;
            } else {
                if( !$p->isDefaultValueAvailable() ) {
                    // check if is request
                    if( $p->getClass() ) {
                        $requirement_class  = $p->getClass()->name ;
                        if( 'Symfony\\Component\\HttpFoundation\\Request' === $requirement_class ) {
                            $dispatcher_args[$i]    = '$request' ;
                        } else if( isset($requirements_entitys[ $requirement_class])) {
                            $dispatcher_args[$i]    = '$' . $this->loader->getNameByClass( $requirement_class ) ;
                        } else if( $this->loader->hasSuperAdminClass($requirement_class) ){
                            $super_class    = $this->loader->getSuperAdminClass($requirement_class) ;
                            $dispatcher_args[$i]    = '$controller->getAdminByClass(' . var_export( $super_class , 1 ) . ')' ;
                        } else {
                            throw new \Exception( sprintf("%s parameter:(%s %s) not defined on path:%s", $this->err_msg, $requirement_class, $requirement_key,  $annot->path ) ) ;
                        }
                    } else if( isset($defaults[$requirement_key]) ) {
                        $add_default($i, $requirement_key, $defaults[$requirement_key]);
                        unset($defaults[$requirement_key]);
                    } else {
                        throw new \Exception(sprintf("`%s` no value", $requirement_key) );
                    }
                } else {
                    // maybe need add to other place
                    $dispatcher_args[$i]    = var_export( $p->getDefaultValue() , 1 ) ;
                }
            }
        }
        
        if( !empty($defaults) ) {
           throw new \Exception( sprintf("%s defaults:%s not defined on path:%s", $this->err_msg, json_encode($defaults) , $annot->path ) ) ;
        }
        
        if( !empty($requirement_keys) ) {
            throw new \Exception( sprintf("%s path option `{%s}` not defined on path:%s", $this->err_msg, json_encode($requirement_keys) , $annot->path ) ) ;
        }
        
        $this->action_cache[ $action_name ] = $annot ;
        
        $route = new \Symfony\Component\Routing\Route( $path , $route_config , $requirements ) ;
        $this->page_loader->addPageRoute($admin_name, $action_name, $route, $m, $annot );
        
        $page_class = $this->page_loader->getCompileClass() ;
        $writer = $page_class->getLazyWriter() ;
        
        $generator->writeln('return $options;');
        $dispatcher->writeln(sprintf('return $controller->%s(%s);', $m->getName(), join(', ', $dispatcher_args)));
        
        $writer
                ->write( sprintf('$this->cache["%s:%s"]["generator"] = ', $admin_name, $action_name) ) 
                ->indent()
                 ->write( $generator->getContent() )
                 ->writeln( '}')
                ->outdent()
                ->writeln(";");
                ;
        $writer
                ->write( sprintf('$this->cache["%s:%s"]["dispatcher"] = ', $admin_name, $action_name) ) 
                ->indent()
                 ->write( $dispatcher->getContent() )
                 ->writeln( '}')
                ->outdent()
                ->writeln(";");
                ;
        
        return true ;
    }
    
    public function getRoutePath(
                array & $requirements_entitys,
                array & $requirements, 
                array & $requirement_keys,
                \App\AdminBundle\Compiler\Generator\PhpWriter $generator,
                \App\AdminBundle\Compiler\Generator\PhpWriter $dispatcher,
                $with_entity , 
                $path ,
                $break_requirement_key = null ,
                $first_entity_call = true 
            ) {
        
         $entity_object_name = '$' . $this->admin_name ;
        
         if( $with_entity ) {
            $requirements_entitys[ $this->admin_class ] = $this->eneity_id_name ;
            
            if( $this->admin->getPropertySlugName() ) {
                if( $this->admin->isPropertySlugNullable() ) {
                    $requirements[ $this->eneity_id_name ] = '\w*' ;
                } else {
                    $requirements[ $this->eneity_id_name ] = '\w+' ;
                }
            } else {
                $requirements[ $this->eneity_id_name ] = '\d+' ;
            }

            $property_slug_name = $this->admin->getPropertyIdName() ;
            $property_id_name = $this->admin->getPropertySlugName() ;
            
            if( !empty($property_slug_name) ) {
                $generator->writeln( sprintf('$options["%s"] = $accessor->getValue(%s, "%s");',  $this->eneity_id_name, $entity_object_name, $property_slug_name ));
                $generator->writeln( sprintf('if( empty($options["%s"]) ) $options["%s"] = $accessor->getValue(%s, "%s");',  $this->eneity_id_name, $this->eneity_id_name, $entity_object_name, $property_id_name ));
            } else {
                $generator->writeln( sprintf('$options["%s"] = $accessor->getValue(%s, "%s");',  $this->eneity_id_name, $entity_object_name, $property_id_name ));
            }
            
            if( $first_entity_call ) {
                $first_entity_call  = false ;
                $dispatcher->writeln( sprintf('%s = $controller->getPageObject("%s", $request->get("%s") );', $entity_object_name , $this->admin_name, $this->eneity_id_name) ) ;
            } else {
                $dispatcher->writeln( sprintf('$controller->setPageObject(%s, "%s", $request->get("%s") );', $entity_object_name , $this->admin_name, $this->eneity_id_name) ) ;
            }
         }

         if( !$first_entity_call ) {
            if( !isset($requirement_keys[$this->eneity_id_name]) ) { 
                if( $break_requirement_key ) {
                    if( $with_entity ) {
                        $path   =    '/' . $this->route_path . '/' . '{' . $this->eneity_id_name . '}' . $path  ;
                    } else {
                        $path   =    '/' . $this->route_path . $path  ;
                    }
                }
            } else if ( $break_requirement_key ) {
                // throw new \Exception(sprintf("%s: `{%s}` duplicate, maybe you missing `{%s}` in path `%s`", $this->err_msg, $this->eneity_id_name, $break_requirement_key, $path ));
            }
        }
         
        if( $this->page_parent_entity ) {
            $parent_entity_object_name = '$' . $this->parent_admin->getName() ;
                
            if( $with_entity ) {
                $generator->writeln( sprintf('%s = $accessor->getValue(%s, "%s");', $parent_entity_object_name, $entity_object_name, $this->property_page_name ));
            }
            
            $parent_generator  = $this->page_loader->getPageGeneratorByClass( $this->page_parent_entity ) ;
            
            if( !$first_entity_call ) {
                $dispatcher->writeln( sprintf('%s = $accessor->getValue(%s, "%s");', $parent_entity_object_name , $entity_object_name, $this->property_page_name ) ) ;
            }
            
            if( !$break_requirement_key && !isset($requirement_keys[$this->eneity_id_name]) ) {
                $break_requirement_key = $this->eneity_id_name ;
            }
            
            $path   = $parent_generator->getRoutePath($requirements_entitys, $requirements, $requirement_keys, 
                    $generator, $dispatcher, 
                    true, $path, $break_requirement_key , $first_entity_call )  ;
        } 
        
        return $path ;
    }
}
