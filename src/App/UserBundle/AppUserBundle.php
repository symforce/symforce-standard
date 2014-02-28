<?php

namespace App\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AppUserBundle extends Bundle
{
    
     /**
     * Build this
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DependencyInjection\Compiler\FormPass());
    }
    
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
