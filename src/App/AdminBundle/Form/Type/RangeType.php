<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType ;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer ;


class RangeType extends TextType {
    
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
        parent::buildForm($builder, $options) ;
        if( isset($options['float_option']) ) {
            $builder
                ->addViewTransformer(new MoneyToLocalizedStringTransformer(
                    $options['float_option']['precision'],
                    $options['float_option']['grouping'],
                    null,
                    $options['float_option']['divisor']
                ))
            ;
        }
    }
    
    public function getName(){
        return 'apprange' ;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $choices    = null ;
        if( isset($options['choices'] ) ) {
            $choices    = array() ;
            foreach($options['choices'] as $value) {
                $choices[] = $value ;
            }
        }
        $unit   = null ;
        if( isset($options['unit']) ) {
            $unit   = $this->container->get('translator')->trans( $options['unit'][0],  array(), $options['unit'][1] ) ;
        }
        
        if( isset($options['unit_icon']) ) {
            $unit_icon   = $this->container->get('translator')->trans( $options['unit_icon'][0],  array(), $options['unit_icon'][1] ) ;
        } else {
            $unit_icon  = $unit ;
        }
        
        $range_options  = array(
            'max'   => $options['max'] ,
            'min'   => $options['min'] ,
            'unit'   => $unit ,
            'unit_icon'   => $unit_icon ,
            'choices'   => $choices ,
        ); 
        $view->vars['range_options'] = $range_options ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired( array(
             'max' ,
             'min' ,
        ));
        $resolver->setOptional(array(
           'float_option' , 
           'unit' ,
           'unit_icon' ,
           'choices' ,
        ));
    }
} 