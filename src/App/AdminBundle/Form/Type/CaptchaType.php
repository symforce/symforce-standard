<?php

namespace App\AdminBundle\Form\Type;


use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;

use Gregwar\CaptchaBundle\Generator\CaptchaGenerator;

/**
 * Captcha type
 *
 * @author Gregwar <g.passault@gmail.com>
 */
class CaptchaType extends AbstractType
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     *
     */
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    /**
     * The session key
     * @var string
     */
    protected $key = null;

    /**
     * @var \Gregwar\CaptchaBundle\Generator\CaptchaGenerator
     */
    protected $generator;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Options
     * @var array
     */
    private $options = array();

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Gregwar\CaptchaBundle\Generator\CaptchaGenerator $generator
     * @param array $options
     */
    public function __construct(SessionInterface $session, CaptchaGenerator $generator, TranslatorInterface $translator, $options)
    {
        $this->session      = $session;
        $this->generator    = $generator;
        $this->translator   = $translator;
        $this->options      = $options;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $validator = new \App\AdminBundle\Form\Validator\CaptchaValidator (
            $this->translator,
            $this->session,
            $options['invalid_message'],
            $options['bypass_code'],
            $options['humanity'] ,
            $this->container 
        );

        $builder->addEventListener(FormEvents::POST_BIND, array($validator, 'validate'));
    }

    /**
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $isHuman = false;

        if ($options['reload'] && !$options['as_url']) {
            throw new \InvalidArgumentException('GregwarCaptcha: The reload option cannot be set without as_url, see the README for more information');
        }

        $session_key = 'gcb_'. $form->getName() ;
        
        if ($options['humanity'] > 0) {
            $humanityKey = $session_key . '_humanity';
            if ($this->session->get($humanityKey, 0) > 0) {
                $isHuman = true;
            }
        }

        if ($options['as_url']) {
            $keys = $this->session->get($options['whitelist_key'], array());
            if (!in_array( $session_key, $keys)) {
                $keys[] = $session_key;
            }
            $this->session->set($options['whitelist_key'], $keys);
            $options['session_key'] = $session_key;
        }

        $view->vars = array_merge($view->vars, array(
            'captcha_width'     => $options['width'],
            'captcha_height'    => $options['height'],
            'reload'            => $options['reload'],
            'image_id'          => uniqid('captcha_'),
            'captcha_code'      => $this->generator->getCaptchaCode($options),
            'value'             => '',
            'captcha_key'       => $session_key ,
            'is_human'          => $isHuman
        ));

        $persistOptions = array();
        foreach (array('phrase', 'width', 'height', 'distortion', 'length', 'quality', 'whitelist_key', 'bypass_code') as $key) {
            $persistOptions[$key] = $options[$key];
        }
        $persistOptions['time'] = time() ;
        $persistOptions['ip'] = $this->container->get('request')->getClientIp() ;
        
        if( !$this->container->getParameter('kernel.debug') ) {
            $persistOptions['bypass_code']  = null ;
        }
       
        $this->session->set( $session_key , $persistOptions);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->options['mapped'] = false;
        $resolver->setDefaults($this->options);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'appcaptcha' ;
    }
}
