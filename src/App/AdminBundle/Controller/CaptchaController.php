<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route
 */
class CaptchaController extends Controller
{
 
   /**
     * @Route("/generate-captcha/{key}", name="gregwar_captcha.generate_captcha")
     */
    public function generateCaptchaAction(Request $request, $key)
    {
        $options = $this->container->getParameter('gregwar_captcha.config');
        $session = $this->get('session');
        $whitelistKey = $options['whitelist_key'];
        $isOk = false;

        if ($session->has($whitelistKey)) {
            $keys = $session->get($whitelistKey);
            if (is_array($keys) && in_array($key, $keys)) {
                $isOk = true;
            }
        }

        if (!$isOk) {
            throw $this->createNotFoundException('Unable to generate a captcha via an URL with this session key.');
        }

        /* @var \Gregwar\CaptchaBundle\Generator\CaptchaGenerator $generator */
        $generator = $this->container->get('gregwar_captcha.generator');

        $persistedOptions = $session->get($key, array());
        $options = array_merge($options, $persistedOptions);

        $phrase = $generator->getPhrase($options);
        $generator->setPhrase($phrase);
        $persistedOptions['phrase'] = $phrase;
        $session->set($key, $persistedOptions);

        $response = new Response($generator->generate($options));
        $response->headers->set('Content-type', 'image/jpeg');

        return $response;
    }
}

