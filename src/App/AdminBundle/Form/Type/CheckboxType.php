<?php

namespace App\AdminBundle\Form\Type;

class CheckboxType extends \Symfony\Component\Form\Extension\Core\Type\CheckboxType {
    
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'appcheckbox';
    }
    
    public function getExtendedType()
    {
        return 'checkbox' ;
    }
    
        /**
     * {@inheritDoc}
     */
    public function buildView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options) ;
        $view->vars['value_text']    = $options['value_text'] ;
    }
    
    public function setDefaultOptions(\Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired( array(
             'value_text' ,
        ));
        /*
        $resolver->setDefaults( array(
            'horizontal'    => true ,
        ));
         */
    }
}
