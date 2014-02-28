<?php

namespace App\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class FormCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        
        $resources = $container->getParameter('twig.form.resources') ;
         // var_dump($resources);exit;
        
        
        $loaderChain = $container->getDefinition('validator.mapping.loader.loader_chain');
        
        $arguments = $loaderChain->getArguments();
        array_push($arguments[0], new Reference('app.validator.loader'));
        $loaderChain->setArguments($arguments);
        
        
        // $loaderChain->addMethodCall('addLoader', array( new Reference('app.validator.loader') ) ); 
        
    }
}
