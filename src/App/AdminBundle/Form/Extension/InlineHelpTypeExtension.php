<?php

namespace App\AdminBundle\Form\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Exception\InvalidArgumentException;

class InlineHelpTypeExtension extends AbstractTypeExtension
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
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if( null === $options['input_width'] ) {
            if( $options['inline_help'] ) {
                $options['input_width']    = 60 ;
            } else {
                $options['input_width']    = 100 ;
            }
        } else if( false === $options['input_width'] ) {
            $options['input_width']    = 60 ;
        }
        $view->vars['input_width'] = $options['input_width'];
        $view->vars['inline_help'] = $options['inline_help'];
        
        if( !$form->getParent() ) {
            if( isset($view->vars['legend_tag']) ) {
                if(  'legend' == $view->vars['legend_tag']  ) {
                    $view->vars['legend_tag']   = 'h2' ;
                }
                $view->vars['render_fieldset'] = false ;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'inline_help' => null,
            'input_width' => null,
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