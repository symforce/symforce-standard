<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\Type\HiddenType ;

class RefererType extends HiddenType {
    
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
    
    public function getName(){
        return 'appreferer' ;
    }
    
    public function getParent()
    {
        return 'hidden' ;
    }
    
    
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(\Symfony\Component\Form\FormEvent $evt) use ($options) {
            $request    = $options['referer_url_request'] ;
            $matcher    = $options['referer_url_matcher'] ;
            
            $url   = $evt->getData() ;
            
            if( $url ) {
                $path   = parse_url($url, PHP_URL_PATH) ;
                
                if( $options['referer_base_url'] && substr( $path , 0, strlen($options['referer_base_url'])) === $options['referer_base_url'] ) {
                      $path     = substr($path, strlen($options['referer_base_url'])) ;
                }
                
                try{
                    $parameters = $matcher->match($path);
                }catch(\Symfony\Component\Routing\Exception\ResourceNotFoundException $e){ 

                }
                
                if( $parameters['_route'] === $options['referer_url_route'] ) {
                    $url    = null ;
                }
            }
            if( !$url ) {
                $url    =  $options['referer_url_default']  ;
            }
            $evt->setData($url) ;
        });
    }

    
    /**
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options) {
        if( empty($view->vars['value']) ) {
            $view->vars['value']    = $options['referer_url_default'] ;
        }
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver);
        
        $resolver->setRequired(array(
             'referer_url_default' ,
             'referer_url_route' ,
             'referer_url_request' ,
             'referer_url_matcher' ,
             'referer_base_url' ,
        ));
        
        $resolver->setDefaults(array(
            'mapped'   => false ,
        ));
    }
}