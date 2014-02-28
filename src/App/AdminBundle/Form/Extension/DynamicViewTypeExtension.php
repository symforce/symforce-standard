<?php

namespace App\AdminBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Exception\InvalidArgumentException;

class DynamicViewTypeExtension extends AbstractTypeExtension
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
     * @var array
     */
    protected $options = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(\Symfony\Component\Form\FormBuilderInterface $builder, array $options)
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if( isset( $options['appform_type'] ) ) {
            
            $view->vars['widget_form_group_attr']['appform_name'] = $form->getName() ; 
            $view->vars['widget_form_group_attr']['appform_type'] = $options['appform_type'] ; 
          
            if( isset($options['dynamic_show_on']) ) {
                if( !isset($view->vars['widget_form_group_attr']) ) {
                    throw new \Exception("big error, mopa code must changed");
                }
                $show_on    = $options['dynamic_show_on'] ;
                if( !is_array($show_on) ) {
                    $show_on = array( $show_on ) ;
                }
                foreach($show_on as $and_i => $and ) {
                    foreach($and as $when_i => $values ) {
                        if( !is_array($values) ) {
                            $values = explode(',', trim($values) ) ;
                        }
                        foreach($values as $_value_i => $when_value ) {
                            $values[ $_value_i ] = (string) trim($when_value) ;
                        }
                        $show_on[$and_i][$when_i] = $values ;
                    }
                }
                $view->vars['widget_form_group_attr']['class'] .= ' form-group-hide' ;
                $view->vars['widget_form_group_attr']['form_dynamic_show_on'] = json_encode( $show_on ) ; 
            }
        } else {
            
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(array(
            'appform_type' ,
            'dynamic_show_on' ,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'form';
    }
}