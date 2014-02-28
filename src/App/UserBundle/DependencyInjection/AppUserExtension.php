<?php

namespace App\UserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

use Symfony\Component\Yaml\Parser as YamlParser ;


// use Symfony\Component\PropertyAccess\PropertyAccess ;


class AppUserExtension extends Extension
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
        
        $processor = new Processor();
        $configs = $processor->processConfiguration( new Configuration() , $configs);
  
        if( !$container->hasParameter('mopa_bootstrap.form.templating') ||
                "MopaBootstrapBundle:Form:fields.html.twig" == $container->getParameter('mopa_bootstrap.form.templating') 
        ) {
            // $container->setParameter('mopa_bootstrap.form.templating', 'AppAdminBundle:Form:fields.html.twig' ) ;
        }
        
        $container->setParameter('security.authentication.success_handler.class', 'App\UserBundle\Security\AjaxAuthenticationSuccessHandler' ) ;
        $container->setParameter('security.authentication.failure_handler.class', 'App\UserBundle\Security\AjaxAuthenticationFailureHandler' ) ;
    }
    
    public function getAlias()
    {
        return 'app_user';
    }
}
