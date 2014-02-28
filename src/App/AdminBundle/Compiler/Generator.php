<?php

namespace App\AdminBundle\Compiler ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use App\AdminBundle\Compiler\Generator\TransGenerator ;
use App\AdminBundle\Compiler\Generator\TransGeneratorNode ;
use App\AdminBundle\Compiler\Generator\TransGeneratorValue ;
use App\AdminBundle\Compiler\Generator\ActionTwigGenerator ;

use App\AdminBundle\Compiler\FormFactory ;

use App\AdminBundle\Compiler\MetaType\Entity\Entity ;
use App\AdminBundle\Compiler\MetaType\Entity\Action ;

/**
 * Description of $twig
 *
 * @author loong
 */
class Generator {
    
    /**
     * @var ContainerInterface 
     */
    protected $app ;

    /** @var MetaFormFactory */
    public $form_factory ;
    
    /** @var array */
    public $as_cache = array() ;
    
    /** @var string */
    public $app_domain ;
    
    /**
     * @var array 
     */
    protected $trans_generators = array() ;
    
    /**
     * @var TransGeneratorNode  
     */
    protected $trans_app_node ;
    
    /**
     * @var array 
     */
    protected $trans_app_values = array() ;
    
    /**
     * @var array 
     */
    public $admin_generators = array() ;
    
    /**
     * @var array 
     */
    public $admin_alias = array() ;
    
    /**
     * @var array 
     */
    public $admin_unmaps = array() ;
    
    /**
     * @var array 
     */
    protected $cached_generators = array() ;
    
    /**
     * @var array 
     */
    protected $doctrine_config = array() ;
    
    /**
     * @var array 
     */
    public $column_config = array() ;
    
    private $expire_check_resources = array() ;
    
    private $loader_cache = array() ;
    
    public $admin_tree_order   = array() ;
    public $admin_tree  = array() ;
    
    public function __construct( ContainerInterface $app, array $resources , array $menu_config , array $dashboard_config, $admin_cache_path, $admin_expired_file ){
        $this->app  = $app ;
        $app->get('app.admin.compiler')->set( \App\AdminBundle\Compiler\Loader\Compiler::STAT_ADMIN );
        
        $this->app_domain = $app->getParameter('app.admin.domain') ;
        $this->form_factory   = $app->get('app.admin.form.factory') ;
        $this->form_factory->setGenerator( $this ) ;
        
        // echo "\n",__FILE__, ":", __LINE__, "\n";  exit;
        
        $this->trans_app_node = $this->getTransNodeByPath( $this->app_domain , 'app') ;
        $this->trans_app_node->set('admin.brand', $this->app->getParameter('app.admin.brand') ) ;
        if( $this->app->hasParameter('app.admin.title') ) {
            $this->trans_app_node->set('admin.title', $this->app->getParameter('app.admin.title') ) ;
        } 
        
        $doctrine  = $app->get('doctrine') ;
        $reader    = $app->get('annotation_reader') ;
        $admin_maps = array() ;
        foreach($resources as $bundle_name =>  $bundle_classes ) {
            foreach($bundle_classes as $class_name ) {
                $om     = $doctrine->getManagerForClass( $class_name ) ;
                if( !$om ) {
                    continue;
                }
                $meta   = $om->getClassMetadata( $class_name ) ;
                foreach($meta->fieldMappings as $property_name => & $config ) {
                    if( 'uuid' === $config['type'] ) {
                        $this->setDoctrineConfig( $class_name , 'uuid', $property_name );
                    }
                }
                $cache  = new \App\AdminBundle\Compiler\Generator\AnnotationCache($reader, $meta ) ;
                $this->as_cache[ $class_name ] = $cache ;
                if( !isset($cache->class_annotations['App\AdminBundle\Compiler\Annotation\Entity']) ) {
                    continue ;
                }
                
                $object = new \App\AdminBundle\Compiler\MetaType\Admin\Entity($cache, $bundle_name, $meta, $this ) ;
                $this->admin_generators[ $class_name ] = $object ;
                
                $admin_name = $object->name  ;
                if(  isset( $this->admin_alias[ $admin_name ]) ) {
                    $_object    = $this->admin_generators [ $this->admin_alias[ $admin_name ]  ] ;
                    throw new \Exception(sprintf(  "`%s:%s` with `%s:%s` has same name `%s` " , $object->getClassName(), $object->getFileName() , $_object->getClassName() , $_object->getFileName() , $admin_name ) );     
                } 
                $this->admin_alias[ $admin_name ] = $class_name ; 
                $admin_maps[ $class_name ] = $object->_compile_class_name ; 
                $this->doctrine_config[ $class_name ]['id'] = $object->property_id_name ;
            }
        }
        
        foreach($this->admin_generators as $admin ) {
            $admin->lazyInitialize() ;
        }
        
        
        $this->sortAdmin() ;
        
        foreach($this->admin_generators as $class_name => $object ) {
             $object->compile() ;
        }
        
        foreach($this->trans_app_values as $group => $values ) {
            foreach($values as $name => $value ) {
                if( $value->isNull() ) {
                    if( preg_match('/\.label$/', $name) ) {
                        $_value   = str_replace('.label', '', $name) ;
                        $_value   = $this->humanize($_value) ;
                    } else {
                        $_value = $this->humanize($name) ;
                    }
                    $value->setValue( $_value ) ;
                }
                if( $value->isChanged() ) {
                    $tr     = $this->getTransGenerator( $value->getDomain() ) ;
                    $tr->set( $value->getPath(), $value->getValue() );
                }
            }
        }
        
        foreach($this->cached_generators as $it){
             $it->flush( $this ) ;
        }
        
        // $route_generator        = new Generator\RouteGenerator() ;
        $dashboard_generator    = new Generator\DashboardGenerator() ;
        $menu_generator         = new Generator\MenuGenerator() ;
        
        // \Dev::dump($this->column_config);
        
        $this->addLoaderCache('entity_alias', $this->admin_alias) ;
        $this->addLoaderCache('admin_tree', $this->admin_tree) ;
        $this->addLoaderCache('admin_maps', $admin_maps) ; 
        $this->addLoaderCache('admin_unmaps', $this->admin_unmaps) ;
        
        $this->addLoaderCache('doctrine_config', $this->getDoctrineConfig() ) ;  
        $this->addLoaderCache('role_hierarchy', $this->compileHierarchyRoles() ) ; 
        
        $this->addLoaderCache('menu', $menu_generator->buildMenuTree($this, $menu_config), true ); 
        $this->addLoaderCache('dashboard', $dashboard_generator->buildDashboardGroups($this, $dashboard_config) , true ) ; 
        // $this->addLoaderCache('route', $route_generator->getRouteCollection($this) , true ); 
        
        foreach($this->loader_cache[1] as $key => $serialize_loader_cache ) {
            $this->loader_cache[1][$key] = serialize($serialize_loader_cache) ;
        }
        
        \Dev::write_file($admin_cache_path , '<' . '?php return ' .var_export( $this->loader_cache  , 1 ) . ';' ) ;
    
        $this->generateExpireCheckCache( $admin_expired_file ) ; 
        
        $locale = $this->app->getParameter('locale')  ;
        $tr_cache   = array() ;
        foreach($this->trans_generators as $it) {
            $it->flush( $this, $locale, $tr_cache ) ; 
        }
        register_shutdown_function( function($ca) {
             foreach($ca as $filename => $data ) {
                 \Dev::write_file($filename, $data) ;
             } 
        }, $tr_cache ); 
        
        $app->get('app.admin.compiler')->set( \App\AdminBundle\Compiler\Loader\Compiler::STAT_OK );
    }
    
    private function sortAdminTree( $parent, array & $node ,  array & $attached, $check_inversed ){
        foreach($this->admin_generators as $object ) {
            $name   = $object->name ;
            if( isset($attached[$name]) ) {
                continue ;
            }
            if( isset( $node[ $name ] ) ) {
                throw new \Exception("big error");
            }
                
            foreach($object->_route_assoc->_parents as $parent_property => $parent_name ) {
                if( $parent_name === $parent ) {
                    if( $check_inversed ) {
                        $map    = $object->getPropertyDoctrineAssociationMapping( $parent_property ) ;
                        if( !isset($map['inversedBy']) || !$map['inversedBy'] ) {
                            continue ;
                        }
                    }
                    $attached[$name]   = true ;
                    $node[ $name ] = array() ; 
                    break ;
                }
            }
        }
        foreach($node as $child_name => $_node ) {
            $this->sortAdminTree( $child_name, $node[$child_name], $attached, $check_inversed ) ;
        }
    }
    
    private function setAdminTreePath( $parent_name , array & $node, array $path ){
        
        if( empty($node) ) {
            $node   = false ;
            return ;
        }
        
        $path[ $parent_name ] = true ;
        
        foreach($node as $child_name => $_node ) {
            $child = $this->getAdminByName( $child_name ) ;
            $child->_route_assoc->_tree_deep = count( $path ) ;
            $child->_route_assoc->_tree_path = $path ;
            $child->_route_assoc->_tree_parent = $parent_name ;
            
            $this->admin_deep_order[]   = $child_name ;
            $this->setAdminTreePath( $child_name, $node[$child_name], $path ) ; 
        }
        
    }
    
    private function sortAdmin(){
        
        foreach($this->admin_generators as $admin ) {
            $admin->_route_assoc->parentInitialize() ;
        }
        
        $tree   = array() ;
        $attached   = array() ;
        foreach($this->admin_generators as  $object ) {
             if( empty($object->_route_assoc->_parents) ) {
                 $tree[ $object->name ] = array() ;
                 $attached[ $object->name ] = true ;
             }
        }
        
        foreach($tree as $root => $node ) {
            $this->sortAdminTree( $root, $tree[$root], $attached , true ) ;
        }
        
        foreach($tree as $root => $node ) {
            $this->sortAdminTree( $root, $tree[$root], $attached , false ) ;
        }
        
        $this->admin_deep_order    = array() ;
        foreach($tree as $root_name => $node ) {
            $root = $this->getAdminByName( $root_name ) ;
            $root->_route_assoc->_tree_deep = 0 ;
            $root->_route_assoc->_tree_path = null  ;
            $root->_route_assoc->_tree_parent = null ;
            $this->admin_deep_order[]   = $root_name ;
            $this->setAdminTreePath( $root_name, $tree[$root_name], array() ) ;
        }
        
        $this->admin_tree   = $tree ;
        
        foreach($this->admin_generators as  $object ) {
            if( !isset( $attached[$object->name]) ) {
                throw new \Exception("big error") ;
            }
            if( !in_array( $object->name , $this->admin_deep_order) ) {
                throw new \Exception("big error") ;
            }
        }
        
        foreach($this->admin_generators as $admin ) {
            $admin->_route_assoc->routeInitialize() ;
        }
        
        foreach($this->admin_generators as $admin ) {
            $admin->childrenInitialize() ;
        }
        
    }
    
    private function compileHierarchyRoles() {
        $roles  = $this->app->getParameter('security.role_hierarchy.roles');
        
        $get    = null ;
        $get    = function($role, array & $visited ) use( & $roles, & $get ){
            if( !isset($roles[$role]) ) {
                $visited[$role]   = true ;
                return ;
            }
            foreach($roles[$role] as $_role ) {
                if( isset($visited[$_role]) ) {
                    continue ;
                }
                $visited[$_role]    = true ;
                $get($_role, $visited );
            }
        };
        $_roles = array() ;
        foreach($roles as $key => $value ) {
            $visited    = array() ;
            $get($key, $visited ) ;
            $_roles[ $key ] = array_keys($visited) ;
        }
        
        return $_roles ;
    }


    public function addLoaderCache($key, $value, $serialize = false ){
        if( $serialize ) {
            $this->loader_cache[1][$key]   = $value ;
        } else {
            $this->loader_cache[0][$key]   = $value ;
        }
    }
    
    public function addLazyLoaderCache($type, $key, $value , $serialize = false ){
        if( $serialize ) {
            $this->loader_cache[1][$type][$key]   = $value ;
        } else {
            $this->loader_cache[0][$type][$key]   = $value ;
        }
    }
    
    public function addLazyLoaderValue($type, $value , $serialize = false ){
        if( $serialize ) {
            $this->loader_cache[1][$type][]   = $value ;
        } else {
            $this->loader_cache[0][$type][]   = $value ;
        }
    }
    
    /**
     * 
     * @param string $class_name
     * @return \App\AdminBundle\Compiler\Generator\AnnotationCache
     */
    public function getAnnotationCache( $class_name ) {
        if( !isset($this->as_cache[$class_name])) {
            throw new \Exception( sprintf("%s", $class_name) );
        }
        return $this->as_cache[$class_name] ;
    }
    
    /**
     * 
     * @param string $class_name
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getMetadataForClass( $class_name ) {
        $om     = $this->app->get('doctrine')->getManagerForClass( $class_name ) ;
        if( $om ) {
            return $om->getClassMetadata( $class_name ) ;
        }
    }
    
    /**
     * @return \App\AdminBundle\Compiler\MetaType\Entity\Entity
     */
    public function getAdminByName( $admin_name ) {
        if( !isset( $this->admin_alias[$admin_name] ) ) {
            throw new \Exception( sprintf("`%s` is not a admin object name", $admin_name) ) ;
        }
        return $this->getAdminByClass( $this->admin_alias[$admin_name] ) ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\MetaType\Entity\Entity
     */
    public function getAdminByClass( $class_name ) {
        if( !isset( $this->admin_generators[$class_name] ) ) {
            throw new \Exception( sprintf("`%s` is not a admin class name", $class_name) ) ;
        }
        return  $this->admin_generators[$class_name] ;
    }
    
    public function hasAdminName( $admin_name ) {
        return isset( $this->admin_alias[ $admin_name ]) ;
    }
    
    public function hasAdminClass( $class_name ) {
        return isset( $this->admin_generators[ $class_name ]) ;
    }

    public function get( $name ) {
        return $this->app->get( $name ) ;
    }
    
    public function getParameter( $name ) {
        return $this->app->getParameter($name) ;
    }
    
    public function getAppDomain(){
        return $this->app_domain ;
    }
    
    /**
     * @return string
     */
    public function getTemplateCacheDir() {
        return $this->app->getParameter('kernel.root_dir') . '/../src/App/AdminBundle/Resources/views/Cache/' ;
    }
    
    /**
     * @param string $domain
     * @return ActionGenerator 
     */
    private function getActionTwigGenerator(\App\AdminBundle\Compiler\MetaType\Action\AbstractAction $action ) {
        $_key   = 'twig.'. $action->admin_object->name . '.' . $action->name ;
        if( isset($this->cached_generators[ $_key]) ) {
            return $this->cached_generators[ $_key] ;
        }
        $gen    = new ActionTwigGenerator( $action ) ;
        $this->cached_generators[ $_key]    = $gen ;
        return $gen ;
    }
    
    /**
     * @param string $domain
     * @return ActionGenerator 
     */
    public function getActionPhpGenerator(\App\AdminBundle\Compiler\MetaType\Action\AbstractAction $action ) {
        $_key   = 'action.' .$action->admin_object->name . '.' . $action->name ;
        if( isset($this->cached_generators[ $_key]) ) {
            return $this->cached_generators[ $_key] ;
        } 
        $class = new \App\AdminBundle\Compiler\Generator\PhpClass() ;
        $class
            ->setName( $action->_compile_class_name )
            ->setParentClassName( '\\'. $action->parent_class_name )
            ->setFinal(true)
            ; 
            if( $action->isCreateTemplate() ) {
                $action->_twig    = $this->getActionTwigGenerator( $action ) ; 
                $action->template = $action->_twig->getTemplateName() ;
            }
        $this->cached_generators[ $_key ]    = $class ;
        return $class ;
    }
    
    /**
     * @param string $domain
     * @return ActionGenerator 
     */
    public function getAdminPhpGenerator(\App\AdminBundle\Compiler\MetaType\Admin\Entity $admin ) {
        $_key   = 'admin.' . $admin->name  ;
        if( isset($this->cached_generators[ $_key]) ) {
            return $this->cached_generators[ $_key] ;
        }
        $class = new \App\AdminBundle\Compiler\Generator\PhpClass() ;
        
        $rc = new \ReflectionObject( $admin ) ;
        $default_properties    = $rc->getDefaultProperties() ;
        if(  $default_properties['parent_class_name']  === $admin->parent_class_name ) {
            $parent_name    = preg_replace('/\\\\Entity\\\\(\w+)$/', '\\\\Admin\\\\\\1Admin', $admin->class_name ) ;
            if( class_exists($parent_name) ) {
                if( !is_subclass_of($parent_name, "\App\AdminBundle\Compiler\Cache\AdminCache") ) {
                    throw new \Exception(sprintf("%s admin class %s exists, but not extends from App\AdminBundle\Compiler\Cache\AdminCache",  $admin->class_name , $parent_name ));
                }
                $admin->parent_class_name   = $parent_name ;
            }
        }
        
        $this->admin_unmaps[ $admin->_compile_class_name ] = $admin->class_name ;
        if( 'App\\AdminBundle\\Compiler\\Cache\\AdminCache' !== $admin->parent_class_name ) {
            if( isset($this->admin_unmaps[ $admin->parent_class_name ]) ) {
                throw new \Exception(sprintf("%s,%s,%s", $admin->parent_class_name, $this->admin_unmaps[ $admin->parent_class_name ], $admin->class_name ));
            }
            $this->admin_unmaps[ $admin->parent_class_name ] = $admin->class_name ;
        }
        
        $class
            ->setName( $admin->_compile_class_name )
            ->setParentClassName( '\\' . $admin->parent_class_name )
            ->setFinal(true)
            ;
            // $class->addUseStatement('Symfony\Component\Validator\Constraints', 'Assert') ;
            // $class->addUseStatement('Symfony\Bridge\Doctrine\Validator\Constraints', 'DoctrineAssert') ;
        $this->cached_generators[ $_key]    = $class ;
        return $class ;
    }
    
    /**
     * @param string $domain
     * @return TransGenerator 
     */
    public function getTransGenerator( $domain ) {
        $_domain    = strtolower($domain) ;
        if( !isset($this->trans_generators[ $_domain ]) ) {
            $this->trans_generators[ $_domain ] = new TransGenerator( $this, $domain ) ;
        }
        return $this->trans_generators[ $_domain ] ;
    }
    
    
    public function getTransValue( $type, $name , $value = null ) {
        if( isset( $this->trans_app_values[$type][$name]) ) {
            if( null !== $value && $this->trans_app_values[$type][$name]->isNull() ) {
                $this->trans_app_values[$type][$name]->setValue( $value ); 
            }
        } else {
            $this->trans_app_values[$type][$name] = $this->trans_app_node->createValue( $type . '.' . $name , $value ) ;
        }
        return $this->trans_app_values[$type][$name] ;
    }
    
    public function trans( $path , $options = array(), $domain = null ){
        if( !$domain ) {
            $domain = $this->app->getParameter('app.admin.domain') ;
        }
        $locale =  $this->app->getParameter('locale') ;
        $tr = $this->app->get('translator') ;
        return $tr->trans( $path, $options, $domain , $locale ) ;
    }
    
    /**
     * @param string $domain
     * @param array $path
     * @return \App\AdminBundle\Compiler\Generator\TransGeneratorNode
     */
    public function getTransNode( $domain , array $path ) {
        $tr     = $this->getTransGenerator($domain) ;
        return $tr->getNodeByRef( $path ) ;
    }
    
    /**
     * @param string $domain
     * @param string $path
     * @return TransGeneratorNode
     */
    public function getTransNodeByPath( $domain , $path ) {
        $tr     = $this->getTransGenerator($domain) ;
        return $tr->getNodeByPath( $path ) ;
    }
    
    public function setDoctrineConfig($class, $type, $property, $config = true ){
        if( $config === true && is_array($property) ) {
            $this->doctrine_config[$class][$type] = $property ;
        } else {
            $this->doctrine_config[$class][$type][$property] = $config ;
        }
    }
    
    public function getDoctrineConfig() {
        $om     = $this->app->get('doctrine')->getManager() ;
        foreach($this->doctrine_config as $class => & $config ) {
            if( !isset($config['id']) ) {
                $meta   = $om->getClassMetadata($class) ;
                $id     = $meta->getIdentifier() ;
                $config['id']   = $id[0] ;
            }
        }
        return $this->doctrine_config ;
    }
    
    /**
     * @dep
     */
    public function setAuthorizeConfig1(\App\AdminBundle\Compiler\MetaType\Admin\Entity $admin){
        
        $admin_name = $admin->name ;
        $node = array(
                'name'  => array( $admin->label->getPath(), $admin->label->getDomain() ) ,
                'action'    => array() ,
            ) ;
        
        foreach( $admin->action_collection->children as $action ) {
            $action_name    = $action->name ;
            $node['action'][$action_name] = array( 
                    $action->label->getPath(),  //0
                    $action->label->getDomain(), //1
                    $action->isPropertyAuth(), //2
                    $action->isOwnerAuth(),  //3
                    $action->isCreateForm(), //4
                    $action->isWorkflowAuth() , //5
                   ) ;
        }
        
        if( $admin->workflow ) {
            $node['workflow'] = array() ;
            foreach( $admin->workflow->children as $name => $_node ) {
                if( false === $_node->list ) {
                    continue ;
                }
                $node['workflow'][ $name ] = array( 
                        $_node->label->getPath() , 
                        $_node->label->getDomain() , 
                        $_node->action ? $_node->action->getPath() : null  , 
                    ) ;
            }
        } 
        
        if( $admin->owner ) {
            $node['owner']   = true ;
        }
        
        foreach( $admin->form->children->properties as $child ) {
            if( ! $child->auth_node ) {
                continue ;
            }
            if( !isset($node['property']) ) {
                $node['property']   = array() ;
            }
            $property_name  = $child->class_property ;
            $node['property'][$property_name]   = array( $child->label->getPath(), $child->label->getDomain() ) ;
        }
        
        $this->authorize_config[$admin_name]    = $node ;
    }
    
    private $_twig_parser   = null ;
    private $_twig_locator   = null ;
    public function loadTwigTemplatePath( $path ) {
        if( null === $this->_twig_parser ) {
            $this->_twig_parser   = $this->app->get('templating.name_parser');
        }
        if( null === $this->_twig_locator  ) {
            $this->_twig_locator   = $this->app->get('templating.locator');
        }
        return $this->_twig_locator->locate( $this->_twig_parser ->parse( $path ) ); 
    }
    
    public function addExpireCheckResource( $path ) {
        if( !file_exists($path) ) {
            throw new \Exception(sprintf("file:%s not exists", $path));
        }
        $this->expire_check_resources[] = $path ;
    }
    
    public function addExpireCheckClass( $class ) {
        $rc = new \ReflectionClass($class);
        $this->addExpireCheckResource( $rc->getFileName()  );
    }
    
    private function generateExpireCheckCache( $expire_check_path ){
        
        $root_dir   = dirname( $this->app->getParameter('kernel.root_dir') ) ;
        $fs = new \Symfony\Component\Filesystem\Filesystem() ;
        
        $bundles    = array() ;
        foreach( $this->admin_generators as $key => $admin ) {
            if( isset($bundles[$admin->bundle_name]) ) {
                continue ;
            }
            $file   = $admin->getFilename() ;
            $file  = trim($fs->makePathRelative( $file, $root_dir ) , '/' );
            
            $bundles[$admin->bundle_name]   = dirname($file) . '/' ;
            
            continue ;
            
            if( !isset($dirs[$_dir]) ) {
                $dirs[$_dir]    = array() ;
            } 
            $_entity_file = basename($file) ;
            $dirs[$_dir][ $_entity_file ] = filemtime( $admin->getFilename() ) ;
        }
        $dirs   = array() ;
        
        foreach($bundles as $entity_dir ) {
            $finder     = new \Symfony\Component\Finder\Finder() ;
            $finder->name('*.php') ;
            foreach( $finder->in( $root_dir . '/' . $entity_dir  )  as $file ) {
                $dirs[$entity_dir][ $file->getRelativePathname()  ]  = filemtime( $file->getRealpath() ) ;
            }
        } 
        $default_resources = array(
            'app/config/app/admin.yml' ,
        );
        foreach($default_resources as $file ) {
            $dirs[0][$file] = filemtime( $root_dir . '/' . $file ) ; 
        }
        
        foreach($this->expire_check_resources  as $file ) {
            $_file  = trim($fs->makePathRelative( $file, $root_dir ) , '/' );
            if( !in_array($_file, $dirs[0] ) ) {
                $dirs[0][$_file] = filemtime( $file ) ; 
            }
        }
        
        /**
         * @todo add yml configure check 
         */
        \Dev::write_file($expire_check_path , '<'.'?php return ' . var_export($dirs, 1) .';') ;
    }
    
    public function camelize($string)
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) { return ('.' === $match[1] ? '_' : '').strtoupper($match[2]); }, $string);
    }
    
    public function humanize($text)
    {
        // Symfony\Component\Form\FormHelper::humanize
        return ucfirst(trim(strtolower(preg_replace(array('/([A-Z])/', '/[_\s]+/'), array('_$1', ' '), $text))));
    }
    
}
