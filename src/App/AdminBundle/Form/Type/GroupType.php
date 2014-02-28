<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Type for FormTab handling.
 *
 * @author phiamo <phiamo@googlemail.com>
 */
class GroupType extends AbstractType
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
        $this->container = $container ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'virtual'   => true ,
            'label_render' => false ,
            'horizontal_input_wrapper_class' => '',
            'horizontal_label_class' => '',
            'horizontal_label_offset_class' => '',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'appgroup';
    }
}
