<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType ;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use App\AdminBundle\Form\DataTransformer\DateTimeTransformer ;

/**
 * https://github.com/eternicode/bootstrap-datepicker
 * Symfony\Component\Form\Extension\Core\Type\DateTimeType
 */

class DateTimeType extends TextType {
    
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
        $builder->addModelTransformer( new DateTimeTransformer( $options['format'], $options['picker']) ) ;
        
        if( $options['less_than'] || $options['greater_than'] ) {
            $builder->addEventListener(\Symfony\Component\Form\FormEvents::POST_BIND, function(\Symfony\Component\Form\FormEvent $event)use($options){
                $form = $event->getForm() ;
                $date = $form->getData() ;
                if( !$date ) {
                    return ;
                }
                $tr = $this->container->get('translator') ;
                
                $parent = $form->getParent() ;
                $object = $parent->getData() ;
                $admin  = $this->container->get('app.admin.loader')->getAdminByClass( $object ) ;
                
                if( $options['greater_than'] ) {
                    if( property_exists($object, $options['greater_than']) ) {
                        $property   = $options['greater_than'] ;
                        
                        if( $parent->has($property) ) {
                            $_date   = $parent->get($property) ->getData() ;
                        } else {
                            $_date   = $admin->getReflectionProperty($property) ->getValue( $object ) ;
                        }
                        
                        $label  = $admin->getPropertyLabel( $property ) ;
                        
                        if( $_date && !($_date instanceof \DateTime) ) {
                                $error  = sprintf("配置错误, 比较目标(%s,%s) 不是日期类型", $label , $property ) ;
                                $form->addError(new \Symfony\Component\Form\FormError( $error ));
                            
                        } else {
                            if( !$_date || $date->getTimestamp() < $_date->getTimestamp() ) {
                                $error  = sprintf("该日期应该大于%s", $label ) ;
                                $form->addError(new \Symfony\Component\Form\FormError( $error ));
                            }
                        }
                        
                    } else {
                         $_date   = date_create_from_format( $options['format'], $options['greater_than'] ) ;
                         if( ! $_date ) {
                            $_date   = new \Datetime();
                            $_date->setTimestamp( strtotime( $options['greater_than']  ) ) ;
                         }
                         if( $_date && $date->getTimestamp() < $_date->getTimestamp() ) {
                             $label  = $_date->format( $options['format'] ) ;
                             $error  = sprintf("该日期应该大于 %s", $label ) ; 
                             $form->addError(new \Symfony\Component\Form\FormError( $error ));
                         }
                    }
                }
                
                
                
                if( $options['less_than'] ) {
                    if( property_exists($object, $options['less_than']) ) {
                        $property   = $options['less_than'] ;
                        
                        if( $parent->has($property) ) {
                            $_date   = $parent->get($property) ->getData() ;
                        } else {
                            $_date   = $admin->getReflectionProperty($property) ->getValue( $object ) ;
                        }
                        
                       $label  = $_date->format( $options['format'] ) ;
                       
                       if( $_date && !($_date instanceof \DateTime) ) {
                            $error  = sprintf("配置错误, 比较目标(%s,%s) 不是日期类型", $label , $property ) ;
                            $form->addError(new \Symfony\Component\Form\FormError( $error ));
                        } else {
                            if( !$_date ||  $date->getTimestamp() > $_date->getTimestamp() ) {
                                $error  = sprintf("该日期应该小于%s", $label ) ;
                                $form->addError(new \Symfony\Component\Form\FormError( $error ));
                            }
                        }
                    } else {
                         $_date   = date_create_from_format( $options['format'], $options['less_than'] ) ;
                         if( ! $_date ) {
                            $_date   = new \Datetime();
                            $_date->setTimestamp( strtotime( $options['less_than']  ) ) ;
                         }
                         
                        if( $_date && $date->getTimestamp() > $_date->getTimestamp() ) {
                            $label  = $_date->format( $options['format'] ) ;
                            $error  = sprintf("该日期应该小于 %s", $label ) ;
                            $form->addError(new \Symfony\Component\Form\FormError( $error ));
                        }
                    }
                }
            });
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['datetime_picker']    = $options['picker'] ;
        $view->vars['datetime_format']    = $options['format'] ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        
        $resolver->setDefaults(array(
            'compound' => false,
            'greater_than'  => null ,
            'less_than'  => null ,
         ));
        
        $resolver->setRequired(array(
            'format' ,
            'picker' ,
        ));

    }
    
    public function getName(){
        return 'appdatetime' ;
    }
    
    public function getExtendedType()
    {
        return 'text';
    }
}
