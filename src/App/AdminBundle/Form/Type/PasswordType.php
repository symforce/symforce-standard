<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\AdminBundle\Form\DataTransformer\PasswordTransformer ;

class PasswordType extends \Symfony\Component\Form\Extension\Core\Type\PasswordType {
    
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
        $transformer    = new PasswordTransformer( $this->container, $options['password_property'], $options['salt_property'] ) ;
        $builder->addEventListener(FormEvents::PRE_SUBMIT , function(FormEvent $event) use ($transformer ){
            $parent = $event->getForm()->getParent() ;
            $object = $parent->getData() ;
            $property   = $event->getForm()->getName() ;
            $transformer->setReverseData( $object , $property ) ;
        });
        $builder->addModelTransformer( $transformer ) ;
        parent::buildForm($builder, $options) ;
    }
    
    public function getName(){
        return 'apppassword' ;
    }
    
    public function getParent()
    {
        return 'password';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired( array(
              'salt_property' ,
              'password_property' ,
        ));
    }
}