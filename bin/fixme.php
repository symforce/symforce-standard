#!/usr/bin/env php
<?php

	$dir	= dirname(__DIR__);
	chdir( $dir );
        
	$longopts  = array(
		'env:' ,
		'user::' , 
		'group::' ,
		'app' ,
		'version' ,
	);
	$options = getopt('e:u::g::x::v', $longopts);
	$getarg	= function($s, $l, $v = null, $match = null , $error = null ) use($options) {
		if( isset($options[$s] ) ) $v = $options[$s] ;
		else if( isset($options[$l] ) ) $v = $options[$l] ;
		if( $match && !preg_match( $match, $v ) ) {
			$msg	= sprintf( $error, $v);
			echo $msg, "\n";
			exit;
		} else if( false === $v ) {
                    $v  = true ;
                }
		return $v ;
	};
        $web_user   = null ;
        $web_group  = null ;
        $read_user_group   = function( & $var , $match ) use ( $dir ){
             if( null !== $var ) return ;
             $files = array( $dir . '/app/config/parameters.yml' , $dir . '/app/config/parameters.yml.dist' ) ;
             foreach($files as $file ) {
                 if( file_exists($file) ) {
                    $data  = file_get_contents($file) ;
                    preg_match('/web_app_' . $match . ':\s*(.+)\s/', $data, $ls);
                    if( $ls && isset($ls[1]) ) {
                        $var    =  $ls[1] ;
                        break ;
                    }
                 } 
             }
        };
        $read_user_group( $web_user , 'user' );
        $read_user_group( $web_group , 'group' );
        if( !$web_user ) $web_user  = 'www-data' ;
        if( !$web_group ) $web_group  = 'www-data' ;
        
	$env	= $getarg('e', 'env', 'dev', '/^(prod|dev|test)$/',  'env:`%s` must be one of (dev, prod, test)' );
	$user   = $getarg('u', 'user', $web_user , '/^[\w\-\_]+$/',  'user:`%s` is valid' );
	$group	= $getarg('g', 'group', $web_group, '/^[\w\-\_]+$/',  'group:`%s` is valid');
	$app	= $getarg('x', 'app' );
	$ver	= $getarg('v', 'version' );
        $is_dev = 'dev' === $env ;
        
        $exec   = function($cmd){
            echo ">>>: ", $cmd, "\n" ;
            passthru($cmd) ;
        };
        
	$exec("rm -rf app/cache/$env/profiler");
	
        if( $ver || !$is_dev ) {
            $file   = $dir . '/app/config/parameters.yml' ;
            if( file_exists($file) ) {
                $data  = file_get_contents($file) ;
                $_data  = preg_replace_callback('/(\s+app\.version\s*\:).*/', function($ms){
                    return $ms[1] . date(' YmdHis') ;
                } , $data) ;
                file_put_contents($file, $_data); 
            }
            $exec("rm -rf app/cache/$env/*.* app/cache/$env/assetic") ;
        }
        
        if( !$is_dev ) {
            $exec("rm -rf app/cache/$env/annotations app/cache/$env/assetic app/cache/$env/doctrine app/cache/$env/profiler app/cache/$env/translations app/cache/$env/twig") ;
            $exec("rm -rf app/Resources/views/* app/Resources/AppAdminBundle/src/AppAdminCache/*") ;
        }
        
        if( $app || !$is_dev ) {
            $exec("rm -rf web/bundles/js/public*.js web/bundles/css/public*.css");
            $exec("rm -rf app/cache/$env/AppLoader*") ;
            $exec("./app/console app:admin:generate -v");
	}
        
        if( !$is_dev ) {
            $exec("./app/console route:debug -v") ;
            $exec("./app/console cache:warmup --env=$env -v");
        
            $exec("./app/console assets:install --symlink web -v");
            $exec("./app/console assetic:dump --env=$env --no-debug -v");
            
        }
        
	$exec("chown -R $user:$group app/ bin/ src/ web/ composer.*");

        // composer.phar dumpautoload -o
        //./app/console app:dump --watch --no-dump-main --force -vvv