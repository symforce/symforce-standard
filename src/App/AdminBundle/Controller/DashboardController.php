<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/admin")
 */
class DashboardController extends Controller
{
    
    /**
     * @return \App\AdminBundle\Compiler\Loader\AdminLoader
     */
    private function getLoader() {
        return $this->get('app.admin.loader') ;
    }

    private function getDutyCount(array & $tree, array & $duty_count, \App\AdminBundle\Compiler\Loader\AdminLoader $loader){
        
        foreach($tree as $admin_name => $child ) {
            $admin  = $loader->getAdminByName($admin_name) ;
            
            if( $admin->workflow ) {
                foreach($admin->workflow['status'] as $step_name => $step ) {
                    if( ! $step['duty'] ) {
                        continue ;
                    }
                    if( $step['role'] && ! $this->get('security.context')->isGranted(  $step['role'] ) ) {
                        continue ;
                    }
                    // count the status
                    $count  = $admin->getRouteWorkflowCount( $step_name ) ;
                    if( $count < 1 ){
                        continue ;
                    }
                    $duty_count[ $admin_name ][ $step_name ] = $count ;
                }
                
            }
            
            if( $child ) {
                $this->getDutyCount( $child, $duty_count, $loader ) ;
            }
        }
    }

    /**
     * @Route("/", name="app_admin_dashboard")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $loader = $this->getLoader() ;
        
        $tree   = $loader->getAdminTree() ;
        $duty_tasks   = array() ;
        
        $this->getDutyCount($tree, $duty_tasks, $loader );
       
        return array(
            'apploader' => $loader ,
            'admin' => null ,
            'action' => null ,
            'duty_tasks' => $duty_tasks ,
            'dashboard_groups' => $loader->getDashboard() ,
        );
    }
    
    /**
     * @Route("/workflow/{admin_name}/{target}/{id}", name="app_admin_workflow_action")
     */
    public function workflowAction(Request $request)
    {
        
    }
}
