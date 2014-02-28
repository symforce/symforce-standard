<?php

namespace App\AdminBundle\Menu ;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;

use App\AdminBundle\Compiler\CacheParent\AdminLoader ;

class Builder extends ContainerAware
{
    
    protected $factory;
    
    /**
     * @var AdminLoader 
     */
    protected $loadder ;

    /**
     * @var object
     */
    protected $translator ;
    
    /**
     * @param \Knp\Menu\FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }
    
    
    public function createDashboardMenu(Request $request)
    {
        $menu = $this->factory->createItem('root', array(
                'childrenAttributes'   => array( 
                    'class' => 'nav  navbar-nav' ,
                ) ,
            )) ;
        
        $this->loadder = $this->container->get('app.admin.loader') ;
        $this->translator   = $this->container->get('translator') ;
        
        $tree   = $this->loadder->getMenu() ;
        $this->addMenu( $tree, $menu);
       
        return $menu;
    }
    
    private function addMenu(\App\AdminBundle\Compiler\CacheObject\Menu $tree, \Knp\Menu\MenuItem $menu, $as_root = true ){
        
        if( $as_root ) {
            $_menu  = $menu ;
        } else {
            $options    = array(
                'attributes'    => array() ,
                'childrenAttributes'    => array() ,
                'linkAttributes'    => array() ,
                'extras'    => array() ,
            ) ;
            
            $label  = $this->translator->trans($tree->getLabel(), array(), $tree->getDomain() ) ;
            
            if( $tree->admin ) {
                $admin_name = $tree->getName() ;
                if( !$this->loadder->auth($admin_name, 'list') ) {
                    return ;
                }
                $admin  = $this->loadder->getAdminByName( $admin_name ) ;
                $options['uri'] =  $admin->path('list') ;
            } else {
                if( $tree->getUrl() ) {
                    $options['uri'] =  $tree->getUrl() ;
                } else if( $tree->getRouteName() ) { 
                   $options['uri'] =   $this->container->get('router')->generate( $tree->getRouteName() ) ;
                } else {
                    $options['uri'] =  'javascript:alert(0)' ;
                }
            }
            
            if( $tree->hasChildren() ) {
                
                if( $menu->isRoot() ) {
                    $options['attributes']['class'] = 'dropdown' ;
                    $options['extras']['caret'] = true ;
                } else {
                    $options['attributes']['class'] = 'dropdown-submenu' ;
                }
                
                $options['childrenAttributes']['class'] =  'dropdown-menu bottom-down' ;
                
                $options['linkAttributes']['class'] =  'dropdown-toggle' ;
                $options['linkAttributes']['data-toggle'] =  'dropdown' ;
                
            }
            
            if( !$menu->isRoot()  ) {
                if( null !== $tree->getDivider() ) {
                    if( $tree->getDivider() ) {
                        $options['extras']['after_divider'] = true ;
                    } else {
                        $options['extras']['before_divider'] = true ;
                    }
                }
            }
            
            if( null !== $tree->getIcon()  ) {
                $options['extras']['icon'] = $tree->getIcon() ;
            }
            
            $_menu  = $menu->addChild( $label, $options ) ;
            
        }
        
        foreach($tree->getChildren() as $child) {
            $this->addMenu($child, $_menu, false ) ;
        }
    } 
}