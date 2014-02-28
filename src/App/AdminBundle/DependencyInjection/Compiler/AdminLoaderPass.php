<?php

namespace App\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AdminLoaderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    { 
        $this->addResource($container);
        
        /*
        foreach ($container->findTaggedServiceIds('appmeta.loader') as $id => $attributes) {
		
            if( isset($attributes[0]) ) {
                $attributes = $attributes[0] ;
            }
            if( !isset($attributes['dir']) ) {
                throw new \Exception(sprintf(" service `%s` tag `appmeta.loader` need `dir` attribute", $id) );
            }
            if( !preg_match('/^\w+$/', $attributes['dir'])){
                throw new \Exception(sprintf(" service `%s` tag `appmeta.loader` attribute `dir` is invalid", $id) ); 
            }
            if( !isset($attributes['file']) ) {
                $attributes['file'] = '*.php' ;
            }
            $this->addResource($container, $definition, $id, $attributes['dir'], $attributes['file']  ) ;
        }
        */
	
    }
    
    private function addResource(ContainerBuilder $container){
        
	$pattern    = '*.php' ;
        
        $classes    = array() ;
        
        foreach ($container->getParameter('kernel.bundles') as $bundle_name => $bundle_class ) {
          
            $reflection = new \ReflectionClass( $bundle_class );
            
            $_dir    = dirname($reflection->getFilename()). '/Entity'  ;
            if ( is_dir( $_dir ) ) {
                $_pattern = rtrim(realpath($_dir), '/') . '/' . $pattern ;
                foreach(glob($_pattern) as $_file) {
                    $class  =$this->findClass($_file);
                    if( $class ) {
                        $classes[$bundle_name][]    = $class ;
                    }
                }
            }
        }
        
        $generator  = $container->getDefinition('app.admin.generator') ;
        $generator->replaceArgument(1, $classes ) ; 
    }
    
    
    /**
     * Returns the full class name for the first class in the file.
     *
     * @param string $file A PHP file path
     *
     * @return string|false Full class name if found, false otherwise
     */
    protected function findClass($file)
    {
        $class = false;
        $namespace = false;
        $tokens = token_get_all(file_get_contents($file));
        for ($i = 0, $count = count($tokens); $i < $count; $i++) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            if (true === $class && T_STRING === $token[0]) {
                return $namespace.'\\'.$token[1];
            }

            if (true === $namespace && T_STRING === $token[0]) {
                $namespace = '';
                do {
                    $namespace .= $token[1];
                    $token = $tokens[++$i];
                } while ($i < $count && is_array($token) && in_array($token[0], array(T_NS_SEPARATOR, T_STRING)));
            }

            if (T_CLASS === $token[0]) {
                $class = true;
            }

            if (T_NAMESPACE === $token[0]) {
                $namespace = true;
            }
        }

        return false;
    }
}
