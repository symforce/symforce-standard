<?php

namespace App\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Symfony\Component\DependencyInjection\Definition;

/**
 * router.default.class   = Symfony\Bundle\FrameworkBundle\Routing\Router
 * routing.loader.class   = Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader
 */

class ChainRouterPass implements CompilerPassInterface
{
    
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('app.compile.state', 0 );
    }
}