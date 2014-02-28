<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType ;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\AdminBundle\Form\DataTransformer\ViewTransformer ;

class ViewType extends AbstractType {
    
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     *
     */
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer    = new ViewTransformer() ;
        $builder->addModelTransformer( $transformer ) ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT , function(FormEvent $event) use ($transformer){
                $transformer->setReverseData( $event->getForm()->getData() ) ;
            }) ;
        parent::buildForm($builder, $options ) ;
    }
    
    public function getName(){
        return 'appview' ;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options) ;
        $view->vars['_object']   = $view->parent->vars['data'] ;
        $view->vars['property_name']   = $form->getName() ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }
}
