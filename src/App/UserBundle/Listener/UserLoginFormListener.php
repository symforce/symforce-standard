<?php

namespace App\UserBundle\Listener ;

use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderAdapter;
use Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Session\SessionAuthenticationStrategyInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
 
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener ;
use Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener ;

use App\UserBundle\Exception\CaptchaException ;
 
/**
 * Description of UserLoginFormListener
 *
 * @author loong
 */
class UserLoginFormListener extends AbstractAuthenticationListener {
    
    private $csrfTokenManager;

    /**
     * {@inheritdoc}
     */
    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionAuthenticationStrategyInterface $sessionStrategy, HttpUtils $httpUtils, $providerKey, AuthenticationSuccessHandlerInterface $successHandler, AuthenticationFailureHandlerInterface $failureHandler, array $options = array(), LoggerInterface $logger = null, EventDispatcherInterface $dispatcher = null, $csrfTokenManager = null)
    {
        if ($csrfTokenManager instanceof CsrfProviderInterface) {
            $csrfTokenManager = new CsrfProviderAdapter($csrfTokenManager);
        } elseif (null !== $csrfTokenManager && !$csrfTokenManager instanceof CsrfTokenManagerInterface) {
            throw new InvalidArgumentException('The CSRF token manager should be an instance of CsrfProviderInterface or CsrfTokenManagerInterface.');
        }
        parent::__construct($securityContext, $authenticationManager, $sessionStrategy, $httpUtils, $providerKey, $successHandler, $failureHandler, array_merge(array(
            'username_parameter' => '_username',
            'password_parameter' => '_password',
            'csrf_parameter'     => '_csrf_token',
            'captcha'            => 'login[captcha]' ,
            'intention'          => 'authenticate',
            'post_only'          => true,
        ), $options), $logger, $dispatcher);

        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function requiresAuthentication(Request $request)
    {
        if ($this->options['post_only'] && !$request->isMethod('POST')) {
            return false;
        }

        return parent::requiresAuthentication($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function attemptAuthentication(Request $request)
    {
        if (null !== $this->csrfTokenManager) {
            $csrfToken = $request->get($this->options['csrf_parameter'], null, true);

            if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken($this->options['intention'], $csrfToken))) {
                throw new InvalidCsrfTokenException('Invalid CSRF token.');
            }
        }
        
        if ($this->options['post_only']) {
            $username = trim($request->request->get($this->options['username_parameter'], null, true));
            $password = $request->request->get($this->options['password_parameter'], null, true);
        } else {
            $username = trim($request->get($this->options['username_parameter'], null, true));
            $password = $request->get($this->options['password_parameter'], null, true);
        }
        $request->getSession()->set(SecurityContextInterface::LAST_USERNAME, $username);
        
        $captcha_data    = $request->request->get($this->options['captcha'], null , true) ;
        if( !is_array($captcha_data) || !isset($captcha_data['key']) || !isset($captcha_data['code']) ) {
            throw new CaptchaException('Invalid captcha code', __LINE__ );
        }
        $captcha_key     = $captcha_data['key'] ;
        $captcha_code     = $captcha_data['code'] ;
        if( empty($captcha_key) ) {
            throw new CaptchaException('Invalid captcha code', __LINE__ );
        }
        $captcha_options = $request->getSession()->get($captcha_key, array());
        
        if( !isset($captcha_options['time']) ) {
            throw new CaptchaException('Invalid captcha code', __LINE__ );
        }
        $captcha_time   = time() - $captcha_options['time'] ;
        if( $captcha_time > 300 ) {
            throw new CaptchaException('Invalid captcha code', __LINE__ );
        }
        
        if( $captcha_time < 1 ) {
            throw new CaptchaException('Invalid captcha code', __LINE__ );
        }
        
        if( !isset($captcha_options['ip']) || $captcha_options['ip'] !== $request->getClientIp() ) {
            throw new CaptchaException('Invalid captcha code', __LINE__ );
        }
        
        if( !isset($captcha_options['phrase']) || !$this->compare($captcha_options['phrase'],$captcha_code) ) {
            if( !isset($captcha_options['bypass_code']) || !$this->compare($captcha_options['bypass_code'], $captcha_code) ) {
                throw new CaptchaException('Invalid captcha code', __LINE__ );
            }
        }
        
        $returnValue = $this->authenticationManager->authenticate(new UsernamePasswordToken($username, $password, $this->providerKey));
        
        $request->getSession()->remove( $captcha_key );
        if ( $request->getSession()->has( $captcha_key . '_fingerprint')) {
            $request->getSession()->remove( $captcha_key . '_fingerprint') ;
        }
        
        return $returnValue ;
    }
    
    
    
    /**
     * Process the codes
     *
     * @param $code
     *
     * @return string
     */
    private function niceize($code)
    {
        return strtr(strtolower($code), 'oil', '01l');
    }

    /**
     * Run a match comparison on the provided code and the expected code
     *
     * @param $code
     * @param $expectedCode
     *
     * @return bool
     */
    private function compare($code, $expectedCode)
    {
        return ($expectedCode && is_string($expectedCode) && $this->niceize($code) == $this->niceize($expectedCode));
    }
    
}
