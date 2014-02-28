<?php

namespace App\UserBundle\Security ;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\HttpUtils;

use Symfony\Component\Security\Core\Exception\AuthenticationException ;

/**
 *
 * @author loong
 */
class AjaxAuthenticationFailureHandler extends \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler {
    
    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if( $request->isXmlHttpRequest() ) {
            $error  = explode( '\\', get_class($exception)) ;
            $error  = strtolower( array_pop( $error ) ) ;
            $json = array(
                'ok'    => false ,
                'errno' => $exception->getCode() ,
                'error' => $error ,
                'message' => $exception->getMessage() ,
                'type'  => 'login[username]' ,
            );
            if( $exception instanceof \App\UserBundle\Exception\CaptchaException ) {
                $json['type']   = 'login[captcha][code]' ;
            }
            return new \Symfony\Component\HttpFoundation\JsonResponse($json) ;
        }
        return parent::onAuthenticationFailure($request, $exception);
    }
}
