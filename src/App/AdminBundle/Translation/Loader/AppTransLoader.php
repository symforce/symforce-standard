<?php

namespace App\AdminBundle\Translation\Loader;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\YamlFileLoader ;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Description of AppTransLoader
 *
 * @author loong
 */
class AppTransLoader extends YamlFileLoader
{
    public function setMetaLoader( $loader ) {
        
    }
}