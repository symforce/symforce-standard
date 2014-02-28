<?php

namespace App\AdminBundle\DependencyInjection\Composer;

use Composer\Script\Event;

class Script
{

    public static function Install(Event $event) {
        
        exec('./app/console app:admin:generate');
        exec('./app/console assets:install --symlink');
        exec('./app/console app:admin:generate --force');
        
        return true;
    }
    
}