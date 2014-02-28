<?php

namespace App\UserBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;

/**
 *
 * @author loong
 */
class AjaxAuthenticationSuccessHandler extends \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler {
    
    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        if( $request->isXmlHttpRequest() ) {
            $json = array(
                'ok'    => true ,
                'username' => $token->getUsername() ,
            );
            return new \Symfony\Component\HttpFoundation\JsonResponse($json) ;
        }
        return parent::onAuthenticationSuccess($request, $token) ;
    }
}
