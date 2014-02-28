<?php

namespace App\UserBundle\Exception ;

/**
 * Description of CaptchaException
 *
 * @author loong
 */
class CaptchaException extends \Symfony\Component\Security\Core\Exception\AuthenticationException {
    
    /**
     * {@inheritDoc}
     */
    public function getMessageKey()
    {
        return 'Invalid captcha code.';
    }
    
}
