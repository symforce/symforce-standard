<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace App\AdminBundle\Command;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Util\VarUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps assets to the filesystem.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class WorkflowCommand extends ContainerAwareCommand
{
    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader
     */
    private $loader ;
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminCache
     */
    private $admin ;
    
    private $convert = array() ;
    
    private $force ;
    private $dev ;
    
    protected function configure()
    {
        $this
            ->setName('app:workflow:status')
            ->setDescription('Dumps all assets to the filesystem')
            ->addOption('admin', null, InputOption::VALUE_OPTIONAL, 'for admin')
            ->addOption('convert', null, InputOption::VALUE_OPTIONAL, 'for admin status change')
            ->addOption('force', null, InputOption::VALUE_NONE, 'run the sql')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'debug')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->loader   = $this->getContainer()->get('app.admin.loader') ;
        $this->force    = $input->getOption('force') ;
        $this->dev    = $input->getOption('dev') ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        $admin      = $input->getOption('admin') ;
        $convert  = $input->getOption('convert') ;
        if( $admin ) {
            if( !$this->loader->hasAdminName( $admin) ) {
                throw new \InvalidArgumentException(sprintf("`%s` is not valid admin ", $admin));
            }
            $this->admin = $this->loader->getAdminByName($admin) ;
            if( ! $this->admin->workflow ) {
                throw new \InvalidArgumentException(sprintf("admin `%s` has no workflow ", $admin));
            }
            if( $convert ) {
                $this->parse($convert, $this->admin, $input, $output) ;
            }
            $this->check( $this->admin, $input, $output);
            return ;
        }
        
        if( $convert ) {
            $this->parse_all($convert, $input, $output) ;
        }
        $tree   = $this->loader->getAdminTree() ;
        $this->tree($tree, $input, $output) ;
    }
    
    private function getWorkflowSteps(\App\AdminBundle\Compiler\Cache\AdminCache $admin){
        $steps  = array() ;
        foreach( $admin->workflow['status'] as $step_name => $step ) {
            if( $step['internal'] ) continue ;
            $steps[ $step_name ] = $step['value'] ;
        }
        return $steps ;
    }
    
    private function parse($convert, \App\AdminBundle\Compiler\Cache\AdminCache $admin, InputInterface $input, OutputInterface $output) {
        
        $steps  = $this->getWorkflowSteps($admin) ;
        $step_help  = json_encode( $steps) ;
        
        if( !preg_match('/^(\d+)\:(\d+)$/', $convert, $ms) ) {
            throw new \InvalidArgumentException(sprintf("admin `%s` convert `%s` invalid, steps:%s ",  $admin->getName(), $convert, $step_help ));
        }
        $from   = $ms[1] ;
        $to = $ms[2] ;
        if( $from === $to ) {
            throw new \InvalidArgumentException(sprintf("admin `%s` convert `%s` same status, steps:%s",  $admin->getName(), $convert, $step_help ));
        }
        if( isset($admin->workflow['value'][$from]) ) {
            throw new \InvalidArgumentException(sprintf("admin `%s` can not convert `%s` from exists status `%s`, steps:%s ",  $admin->getName(), $convert, $admin->workflow['value'][$from], $step_help ));
        }
        
        if( !isset($admin->workflow['value'][$to]) ) {
            throw new \InvalidArgumentException(sprintf("admin `%s` can not convert `%s` to unknow status `%s`, steps:%s",  $admin->getName(), $convert, $to, $step_help ));
        }
        $_to  = $admin->workflow['value'][$to] ;
        if(  $admin->workflow['status'][$_to]['internal'] ) {
            throw new \InvalidArgumentException(sprintf("admin `%s` can not convert `%s` to internal status `%s`, steps:%s",  $admin->getName(), $convert, $_to, $step_help ));
        }
        
        $this->convert[ $admin->getName() ][ $from ] = $to ;
    }

    private function parse_all($convert, InputInterface $input, OutputInterface $output) {
        $list   = preg_split('/\s*\,\s*/', trim($convert) ) ;
        
        foreach($list as $_convert){
            if( !preg_match('/^(\w+)\:(.+)$/', $_convert, $ms) ) {
                throw new \InvalidArgumentException(sprintf("invalid convert `%s` ", $_convert));
            }
            $admin_name = $ms[1] ;
            if( !$this->loader->hasAdminName($admin_name) ) {
                throw new \InvalidArgumentException(sprintf("admin `%s` is exists for convert `%s` ", $admin_name, $_convert ));
            }
            $admin  = $this->loader->getAdminByName($admin_name) ;
            if( ! $admin->workflow ) {
                throw new \InvalidArgumentException(sprintf("admin `%s` no workflow for convert `%s` ", $admin_name, $_convert ));
            }
            $config = $ms[2] ;
            $this->parse( $config, $admin, $input, $output) ;
        }
    }


    private function tree(array & $tree, InputInterface $input, OutputInterface $output){
        foreach($tree as $admin_name => $children ) {
            $admin  = $this->loader->getAdminByName($admin_name) ;
            if( $admin->workflow ) {
                $this->check($admin, $input, $output);
            }
            if( $children ) {
                $this->tree($children, $input, $output) ;
            }
        }
    }
    
    
    private function check(\App\AdminBundle\Compiler\Cache\AdminCache $admin, InputInterface $input, OutputInterface $output){
        
        $steps  = $this->getWorkflowSteps($admin) ;
        
        $dql   = sprintf("SELECT a FROM %s a WHERE a.%s NOT IN (%s)", $admin->getClassName(), $admin->workflow['property'], join(',', $steps));
        $em = $admin->getManager();
        $query = $em->createQuery($dql);
        $list = $query->getResult();
        if( empty($list) ) {
            return ;
        }
        $admin_name = $admin->getName() ;
        
        $output->writeln(sprintf("%s , %s count(%d) \n \t steps:%s", $admin_name , $admin->getClassName(), count($list) , json_encode($steps) ));
        
        if( $this->force ) {
            $em     = $admin->getManager() ;
        }
        
        $changed    = false ;
        
        foreach($list as $object) {
            $id     = $admin->getId($object) ;
            $prod = $admin->getReflectionProperty( $admin->workflow['property'] );
            $value = $prod->getValue($object) ;
            
            $_to    = null ;
            if( isset($this->convert[$admin_name][$value]) ) {
                
                $to     = $this->convert[$admin_name][$value] ;
                $_to    = $admin->workflow['value'][ $to ] ;
                $prod->setValue($object, $to ) ;
            }
            
            if( $_to ) {
                $output->writeln(sprintf("  id(%d) => %d  ===> %s(%d) ", $id, $value, $_to, $to ));
                if( $this->force ) {
                    $em->persist( $object ) ;
                    $changed    = true ;
                }
            } else {
                $output->writeln(sprintf("  id(%d) => %d ", $id, $value ));
            }
        }
        
        if( $changed ) {
            $em->flush();
        }
        
        
    }
}
