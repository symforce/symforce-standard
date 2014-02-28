<?php

namespace App\AdminBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

use Symfony\Component\Yaml\Parser as YamlParser ;


// use Symfony\Component\PropertyAccess\PropertyAccess ;


class AppAdminExtension extends Extension
{
    /**
     *
     * @var YamlParser 
     */
    private $yamlParser ;
    
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_services.yml');
        
        $processor = new Processor();
        $configs = $processor->processConfiguration( new Configuration() , $configs);
        
        $this->yamlParser = new YamlParser() ;
        
        //$access = PropertyAccess::getPropertyAccessor() ;
        
        $this->setForm($configs, $container);
        $this->setEntityLoader($configs, $container ) ;
        
        $this->setParameters($container, 'app.admin.route', $configs['route'] );
        $this->setParameters($container, 'app.admin', $configs['admin'] );
        
        if( !$container->hasParameter('mopa_bootstrap.form.templating') ||
                "MopaBootstrapBundle:Form:fields.html.twig" == $container->getParameter('mopa_bootstrap.form.templating') 
        ) {
            // $container->setParameter('mopa_bootstrap.form.templating', 'AppAdminBundle:Form:fields.html.twig' ) ;
        }
        
        // add validator.constraint_validator for form
        
    }
    
    private function setParameters(ContainerBuilder $container, $path, array $list ){
        foreach($list as $key => $value ) {
            $container->setParameter( $path . '.' . $key ,  $value );
        }
    }
    
    private function setEntityLoader(array & $configs, ContainerBuilder $container){
         
         $generator  = $container->getDefinition('app.admin.generator') ;
         
         if( isset($configs['menu']) ) {
             $generator->replaceArgument(2, $configs['menu'] ) ;
             unset($configs['menu']) ;
         }
         
         if( isset($configs['dashboard']) ) {
             $generator->replaceArgument(3, $configs['dashboard'] ) ;
             unset($configs['dashboard']) ;
         }
         
         $admin_loader   = $container->getDefinition('app.admin.loader') ;
         $route_loader   = $container->getDefinition('app.route.loader') ;
         
         $cache_dir = $container->getParameter('kernel.cache_dir') ;
         $admin_cache_file = $cache_dir . '/AppLoaderAdminCache.php' ;
         $admin_expired_file = $cache_dir . '/AppLoaderExpiredCache.php' ;
         $admin_route_file = $cache_dir . '/AppLoaderRouteCache.php' ;
         $admin_loader->replaceArgument(1, $admin_cache_file) ;
         $admin_loader->replaceArgument(2, $admin_expired_file ) ;
         $generator->replaceArgument(4, $admin_cache_file) ;
         $generator->replaceArgument(5, $admin_expired_file) ;
         $route_loader->replaceArgument(1, $admin_route_file) ;
         unset($configs['cache']) ;
         
         $locale = $container->getParameter('locale') ;
         if(  !isset($configs['language'][ $locale ] ) ) {
             throw new \Exception(sprintf("default locale `%s` is not find in app.admin.language: %s", $locale, json_encode( $configs['language'] ) ) );
         }
         $locale_listener = $container->getDefinition('app.locale.listener') ;
         $locale_listener->replaceArgument(1, $locale ) ;
         $locale_listener->replaceArgument(2, $configs['language'] ) ;
         unset( $configs['language'] ) ;
    }
    
    private function setForm(array & $configs, ContainerBuilder $container) {
        $config   = $this->yamlParser->parse(file_get_contents( __DIR__.'/../Resources/config/form.yml' )) ;
        $this->merge_recursive($config, $configs['form'] );  
        
        $form_factory  = $container->getDefinition('app.admin.form.factory') ;
        $form_factory->replaceArgument(2, $config['type'] ) ;
    }
    
    private function merge_recursive( array & $a1, array & $a2){
        foreach( $a2 as $key => & $value ) {
            if( !isset($a1[$key]) ) {
                $a1[$key]   = $value ;
            } else {
                if( is_array($value) ) {
                    if( !is_array($a1[$key]) ) {
                        $a1[$key]   = $value ; 
                    } else {
                        $this->merge_recursive($a1[$key], $value);
                    }
                } else {
                   $a1[$key]   = $value ; 
                }
            }
        }
    }

    public function getAlias()
    {
        return 'app_admin';
    }
}
