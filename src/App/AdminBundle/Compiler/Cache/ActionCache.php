<?php

namespace App\AdminBundle\Compiler\Cache ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use App\AdminBundle\Compiler\Loader\AdminLoader ;

abstract class ActionCache {
 
    /**
     * @var string
     */
    protected $name ;
    
    /** @var string */
    protected $action_name ;
    
    /**
     * @var string
     */
    protected $label ;
    
    /**
     * @var string
     */
    protected $label_domain ;
    
    /** @var string */
    protected $tr_domain ;
    
    /** @var string */
    protected $app_domain ;
    
    /**
     * @var string
     */
    protected $icon ;
    
    /**
     * @var string
     */
    protected $template  ;
    
    /**
     * @var AdminCache
     */
    protected $admin ;
    
    /**
     * @var string
     */
    protected $admin_name ;
    
    /** @var string */
    protected $admin_class ;
    
    /**
     * @var AdminLoader
     */
    protected $admin_loader ;
    
    /**
     * @var object
     */
    protected $translator ;
    
    protected $form_elements = array () ;
    
    public function __construct( AdminCache $admin, AdminLoader $loader ) {
        $this->admin    = $admin ;
        $this->admin_loader    = $loader ;
        $this->__wakeup() ;
    }
    
    protected function __wakeup() {
        
    }
    
    /**
     * @return AdminCache
     */
    public function getAdmin(){
        return $this->admin ;
    }
    
    public function getName() {
        return $this->name ;
    }
    
    public function getLabelPath() {
        return $this->label ;
    }
    
    public function getLabel() {
        return $this->admin->getTranslator()->trans( $this->label , array(), $this->label_domain ) ;
    }
    
    public function getActionLabel( $object = null ) {
        if( !$object && $this->isRequestObject() ) {
              $object = $this->admin->getRouteObject() ; 
        } 
        return $this->admin->getTranslator()->trans( $this->action_label , array(
            '%admin%'   => $this->admin->getLabel() ,
            '%object%'   =>  $object ? $this->admin->string($object) : null ,
        ), $this->action_label_domain ) ;
    }
    
    public function getTitleLabel( $object = null ) {
        if( !$object && $this->isRequestObject() ) {
               $object = $this->admin->getRouteObject() ; 
        }
        return $this->admin->getTranslator()->trans( $this->title_label , array(
             '%admin%'   => $this->admin->getLabel() ,
             '%object%'   =>  $object ? $this->admin->string($object) : null ,
         ), $this->title_label_domain ) ;
    }
    
    public function getFormLabel( $object = null ) {
        if(  !$object && $this->isRequestObject() ) {
            $object = $this->admin->getRouteObject() ; 
        }
        
        return $this->admin->getTranslator()->trans( $this->form_label , array(
            '%admin%'   => $this->admin->getLabel() ,
            '%object%'   =>  $object ? $this->admin->string($object) : null ,
        ), $this->form_label_domain ) ;
    }
    
    public function getIcon() {
        return $this->icon ;
    }
    
    public function getDomain(){
        return $this->tr_domain ;
    }
    
    public function getAppDomain(){
        return $this->app_domain ;
    }
    
    public function isRequestObject() {
        return false ;
    }
    
    public function isCreateAction() {
        return false ;
    }
    
    public function isListAction() {
        return false ;
    }
    
    public function isViewAction() {
        return false ;
    }
    
    public function isBatchAction() {
        return false ;
    }
    
    public function isPageAction() {
        return false ;
    }
    
    public function isDeleteAction() {
        return false ;
    }
    
    public function isFormAction() {
        return false ;
    }
    
    public function isWorkflowAction() {
        return false ;
    }
    
    public function isPropertyAction() {
        return $this->isFormAction() ;
    }
    
    public function isOwnerAction() {
        return $this->isPropertyAction() ;
    }
    
    private $_is_route_action ;
    public function isRouteAction() {
        if( null !== $this->_is_route_action ) {
            return $this->_is_route_action ;
        }
        if( $this->admin->isRouteAdmin() ) {
            $this->_is_route_action = $this->name === $this->admin->getAdminLoader()->getRouteAction() ;
        } else {
            $this->_is_route_action   = false ;
        }
        return $this->_is_route_action ;
    }
    
    public function getAdminRouteName(){
        $path   = array( $this->action_name ) ;
        $this->admin->getAdminParentRoutePath($path) ;
        return 'admin:' . join(':' , $path ) ;
    }
    
    public function path( $object = null , $options = array() ) {
        
        if( $object && !($object instanceof $this->admin_class) ) {
            if( !$this->admin->tree || 0 !== $object ) {
                throw new \Exception(sprintf("expect `%s`, get `%s` ", $this->admin->getClassName(), is_object($object) ? get_class($object) : gettext($object) ));
            }
        }
        
        if( null === $object ) {
            if( $this->isRequestObject() ) {
                $object = $this->admin->getRouteObject() ;
            } else if($this->admin->tree && $this->admin->getTreeObjectId() ){
                $object = $this->admin->getTreeObject() ;
            }
        }
        
        if( $this->admin->tree ) {
            if( $this->isRequestObject() ) {
                $parent = $this->admin->getReflectionProperty( $this->admin->tree['parent'] )->getValue( $object ) ;
                if( $parent ) {
                    $options['admin_tree_parent']   = $this->admin->getReflectionProperty( $this->admin->getPropertyIdName() )->getValue( $parent );
                } else {
                    $options['admin_tree_parent']   = 0 ;
                }
                // todo check if it match $this->admin->getTreeObjectId(
            } else {
                if( $object ) {
                    $options['admin_tree_parent']   = $this->admin->getReflectionProperty( $this->admin->getPropertyIdName() )->getValue( $object );
                } else if( 0 === $object) {
                    $options['admin_tree_parent']   = 0 ;
                } else {
                    $options['admin_tree_parent']   = $this->admin->getTreeObjectId() ;
                }
            }
        }
        
        if( $this->admin->workflow ) {
            if( !isset($options['admin_route_workflow']) ) {
                $options['admin_route_workflow']   = $this->admin->getRouteWorkflow() ;
                if( !$options['admin_route_workflow'] ) unset($options['admin_route_workflow']) ;
            }
        }
        
        if( $this->isRequestObject() ) {
            return $this->admin_loader->appPathWithObject( $this->getAdminRouteName() , $object, $options ) ;
        }
        
        return $this->admin_loader->appPathWithoutObject( $this->getAdminRouteName() , $options ) ;
    }
    
    public function onController(Controller $controller, Request $request) {
        throw new \Exception(sprintf("%s:%s action %s:onController is not implemented", $this->admin->getName(), $this->admin->getClassName(), $this->name ));
    }
    
    public function getTranslator(){
        if( null === $this->translator ) {
            $this->translator   = $this->admin->getTranslator() ;
        }
        return $this->translator ;
    }
    
    public function trans($path, $options = array(), $domain = null ){
        if( null === $this->translator ) {
            $this->translator   = $this->admin->getTranslator() ;
        }
        if( 0 === strpos( $path, '.') ) {
            $path   = $this->admin_name . '.' . $this->name . $path ;
            if( null === $domain ) {
                $domain = $this->tr_domain ;
            }
        } else {
            if( null === $domain ) {
                if( 0 === strpos( $path, $this->admin_name . '.' ) ) {
                    $domain = $this->tr_domain ;
                } else {
                    $domain = $this->app_domain ;
                }
            }
        }
        if( is_object($options) ) {
            $options    = array( '%object%' => $this->admin->string($options) ) ;
        }
        return $this->translator->trans( $path , $options, $domain ) ; 
    }
    
    public function getButton( $name ) {
        if( null === $this->translator ) {
            $this->translator   = $this->admin->getTranslator() ;
        }
        return $this->translator->trans( 'app.button.' . $name , array(), $this->app_domain );
    }
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    public function configureMenu(\Knp\Menu\MenuItem $menu, $text = false  ){
        
        $_menu      = null ;
        $options    = array() ;
        
        $auth_object = null ;
        if( $this->isRequestObject()  ) {
            $auth_object  = $this->admin->getRouteObject() ; 
        } else if($this->admin->tree && $this->admin->getTreeObjectId() ){
            $auth_object = $this->admin->getTreeObject() ;
        }
        
        
        if( !$this->admin->auth($this->name, $auth_object ) ) {
            if( $this->isDeleteAction() ) {
                return null ;
            }
            if( $text ) {
               if( $this->isRequestObject()  ) {
                      $label  = $this->admin->string( $auth_object ) ;
               } else {
                      $label  = $this->getActionLabel() ;
               }
               $_menu = $menu->addChild( $label, $options );
            }
        } else {
            $label  = null ;
            if( $this->isRequestObject()  ) {
                  $label  = $this->getActionLabel( $auth_object ) ;
            } else {
                $label  = $this->getActionLabel() ;
            }

            $options['uri']  = $this->path() ;
            $_menu  = $menu->addChild( $label , $options );
        }
        
        if( $_menu && $this->isRouteAction() ) {
            $_menu->setCurrent( true ) ;
        }
        
        return $_menu ;
    }
    
    protected function buildFormReferer(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\Form\FormBuilder $builder, $object, $url = null ){
        
        $matcher = $this->admin->getService('router')->getMatcher();
        
        $referer = parse_url( $request->headers->get('referer'));
        $referer_parameters = $matcher->match($referer['path']);
        
        $parameters = $matcher->match($request->getPathInfo());
        
        if( $parameters['_route'] !== $referer_parameters['_route'] ) {
            $url    = $referer['path'] . ( isset($referer['query']) ? '?' . $referer['query'] : '' ) ;
        }
        
        $builder->add('app_admin_form_referer', 'appreferer', array(
            'referer_url_default'   => $url ,
            'referer_url_route'     => $parameters['_route'] ,
            'referer_url_request'   => $request ,
            'referer_url_matcher'   => $matcher ,
        ));
        
    }
    
    public function getFormReferer($form){
        $url    = $form->get('app_admin_form_referer')->getData()  ;
        return $url ;
    }
    
}