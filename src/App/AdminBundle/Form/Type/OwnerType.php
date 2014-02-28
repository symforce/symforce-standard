<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType ;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType ;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\AdminBundle\Form\DataTransformer\OwnerTransformer ;


class OwnerType extends ChoiceType {
    
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
        $transformer    = new OwnerTransformer( $this->container->get('app.admin.loader'), $options['admin_class'] ) ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT , function(FormEvent $event) use ($transformer ){
            $parent = $event->getForm()->getParent() ;
            $object = $parent->getData() ;
            $transformer->setReverseData( $object ) ;
        });
        $builder->addModelTransformer( $transformer ) ;
        parent::buildForm($builder, $options) ;
    }
    
    public function getName(){
        return 'appowner' ;
    }
    
    public function getParent()
    {
        return 'choice';
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView_(FormView $view, FormInterface $form, array $options)
    {
        $object = $view->parent->vars['value'] ;
        $admin  = $this->container->get('app.admin.loader')->getAdminByClass( $options['admin_class'] ) ;
        $config = $admin->getObjectWorkflowStatus( $object ) ;
        $view->vars['admin']  = $admin ;
        parent::buildView($view, $form, $options) ;
    }
    
        
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired( array(
             'admin_class' ,
        ));
        
    }
}
