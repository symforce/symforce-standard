<?php

namespace App\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/admin")
 */
class LocaleController extends Controller
{

   /**
     * @Route("/locale/{inline}", name="app_admin_locale", requirements={"inline"="(0|1)"})
     * @Template()
     */
    public function localeAction(Request $request, $inline = 0 )
    {
        // AppAdminBundle:Locale:locale.html.twig
        
        $service    = $this->container->get('app.locale.listener');
        $form   = $service->getForm($request, $inline ) ;
        
        if( 'POST' === $request->getMethod() && !$inline ) {
        	$form->bind( $request ) ; 
        	if ($form->isValid()) { 
                    $locale = $form->getData() ;
                    $request->getSession()->set( 'app_locale' ,  $locale->getLocale() ) ;
                    
                    return $this->redirect(  $locale->getRedirectUrl() ) ;
        	} 
        }
        
        return array(
            'form' => $form->createView(),
        ) ;
    } 
}