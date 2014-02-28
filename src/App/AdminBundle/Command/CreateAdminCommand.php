<?php

namespace App\AdminBundle\Command ;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem ;

class CreateAdminCommand extends ContainerAwareCommand
{


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:admin:generate')
            ->setDescription('Generate the admin cache object')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOT

EOT
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->file_system  = new Filesystem();
        
        $source_dir = __DIR__ . '/../Resources/public/' ;
        
        if( !file_exists($source_dir) ) {
            throw new \Exception( sprintf("`%s` is not exists!",  $source_dir ) ) ;
        }
        $source_dir = realpath($source_dir) ;
        
        $target_dir    = dirname( $this->getContainer()->getParameter('kernel.root_dir') ) . '/web/bundles'  ;
       
        
        $this->ensureDirectoryExists( $target_dir . '/js'  );
        $this->ensureDirectoryExists( $target_dir . '/css' );
        
        $this->ensureDirectoryExists( $target_dir . '/img' );
        $this->ensureDirectoryExists( $target_dir . '/fonts' );
        $this->ensureDirectoryExists( $target_dir . '/font' );
        
        
        $this->linkDir( $source_dir. '/bootstrap-colorpicker/img', $target_dir . '/img'  );
        $this->linkDir( $source_dir. '/bootstrap/fonts', $target_dir . '/fonts' );
        $this->linkDir( $source_dir. '/font-awesome4/fonts', $target_dir . '/fonts' );
        
        $this->symlink( $source_dir. '/jcrop/css/Jcrop.gif', $target_dir . '/css/Jcrop.gif'  );
	
        $loader = $this->getContainer()->get('app.admin.generator') ;
        
        return 0 ;
    }
    
    
    /**
     * @var Filesystem 
     */
    private $file_system  ;

    private function readlink($link) {
        $_target    = readlink($link) ;
        if(is_link( $_target) ) {
            return $this->readlink( $_target ) ;
        }
        return $_target ;
    }
    
    private function isDir( $dir ) {
        if( is_dir($dir) ) {
            return true ;
        }
        if( is_link($dir) ) {
            $_dir  = $this->readlink($dir) ;
            if( is_dir($_dir) ) {
                return true ;
            }
        }
        return false ;
    }
    
    private function getRealFileType( $file ) {
        if(is_link($file) ) {
            $file   = $this->readlink( $file ) ;
        }
        return filetype( $file ) ;
    }
    
    private function ensureDirectoryExists( $dir , $mode = 0755 ){
        $exists = null ;
        if( is_link($dir) ) {
            $exists = false ;
            if( !@unlink( $dir ) ) {
                throw new \Exception( sprintf("delete link file `%s` error: `%s`!",  $dir, json_encode( error_get_last() ) ) ) ;
            }
        }
        
        if( null === $exists ) {
            if( !file_exists( $dir) ) {
                $exists = false ;
            } else if( !is_dir($dir) ) {
                throw new \Exception( sprintf("expect dir, not `%`:`%s` !", filetype($dir), $dir ) ) ;
            } else {
                $exists = true ;
            }
        }
        
        if( !$exists ) {
            $_parents   = array( $dir ) ;
            for( $_dir = dirname( $dir) ; !file_exists( $_dir ) ;  $_dir = dirname( $_dir) ) {
                array_unshift($_parents, $_dir) ;
            }
           
            if( ! $this->isDir($_dir) ) { 
                $_file_type = $this->getRealFileType($_dir) ;
                throw new \Exception( sprintf("expect dir, not `%s`:`%s` !",  $_file_type, $_dir ) ) ;
            }
            
            foreach($_parents as $_dir ) {
                if( is_link($_dir) ) {
                    $_real_dir  = $this->readlink( $_dir ) ;
                    throw new \Exception( sprintf("the link `%s` point to not exist target `%s`",  $_dir, $_real_dir ) ) ;
                }
                if( ! @mkdir( $_dir, $mode ) ) {
                    throw new \Exception( sprintf("mkdir `%s` error: `%s`!",  $_dir, json_encode( error_get_last() ) ) ) ;
                }
            }
        }
    }
    
    private function linkDir($from, $to ) {
        if( !file_exists($from) ) {
            throw new \Exception( sprintf("`%s` is not exists!",  $from ) ) ;
        }
        
        for( $h = dir($from); $f = $h->read();  ) {
            if( $f == '.' || $f == '..' ) {
                continue;
            }
            $this->symlink($from . '/' . $f , $to . '/' . $f ) ;
        }
        $h->close() ;
    }
    
    private function symlink($target , $link ) {
       // echo __LINE__, "\n", $target, " ==> " , $link, "\n" ; 
        if( !file_exists($target) ) {
            throw new \Exception( sprintf("`%s` is not exists", $target ) ) ;
        }
        if( is_link($link) ) {
            $_target    = readlink( $link ) ;
            if(realpath($_target) != realpath($target) ) {
                 // throw new \Exception( sprintf("`%s` should be a link point to `%s`, but it point to `%s`",  $link , $target, $_target ) ) ;
                unlink($link) ;
            } else {
                 return ;
            }
        } else if( file_exists($link)  ) {
            throw new \Exception( sprintf("`%s` should be a link point to `%s`",  $link , $target ) ) ;
        }
        $_path  = $this->file_system->makePathRelative( $target , dirname( $link )  ) ;
        $_path  = rtrim( $_path , '/' ) ;
        symlink($_path, $link) ;
        if( !file_exists($link) ) {
            throw new \Exception( sprintf("`%s` is not exists, link to `%s` -> `%s`", $link , $_path, $target ) ) ;
        }
    }
}
