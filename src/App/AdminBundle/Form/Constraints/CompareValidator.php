<?php

namespace App\AdminBundle\Form\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author loong
 */
class CompareValidator implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {
    
    /**
     * @var array
     */
    private $options ;
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminCache 
     */
    private $admin ;

    public function __construct(array $options, \App\AdminBundle\Compiler\Cache\AdminCache $admin ) {
        $this->options = $options ;
        $this->admin = $admin ;
    }


    public static function getSubscribedEvents()
    {
        return array(
            \Symfony\Component\Form\FormEvents::POST_BIND  => 'validate' ,
        );
    }
    
    /**
     * {@inheritDoc}
     */
    public function validate(\Symfony\Component\Form\FormEvent $event)
    {
        $options    = $this->options ;

        $form = $event->getForm() ;
        $data = $form->getData() ;
        if( !$data ) {
            return ;
        }
        $admin  = $this->admin ;

        $parent = $form->getParent() ;
        $object = $parent->getData() ;
        
        $unit   = '' ;
        if( isset($options['unit']) ) {
            $unit   = $this->admin->trans( $options['unit'][0], null, $options['unit'][1]);
        }
        
        if( isset($options['greater_than']) ) {
            if( property_exists($object, $options['greater_than']) ) {
                $property   = $options['greater_than'] ;
                if( $parent->has($property) ) {
                    $_data   = $parent->get($property)->getData() ;
                } else {
                    $_data   = $admin->getReflectionProperty($property) ->getValue( $object ) ;
                }

                $label  = $admin->getPropertyLabel( $property ) ;
                

                if( $_data && !is_numeric( $_data ) ) {
                        $error  = sprintf("配置错误, 比较目标(%s,%s) 不是数值类型", $label , $property ) ;
                        $form->addError(new \Symfony\Component\Form\FormError( $error ));
                } else {
                    if( !$_data || $data < $_data ) {
                        $error  = sprintf("该值应该大于%s %s%s", $label, $_data, $unit) ;
                        $form->addError(new \Symfony\Component\Form\FormError( $error ));
                    }
                }

            } else {
                $error  = sprintf("配置错误, 比较目标(%s) 不存是%s属性", $property, $this->admin->getClassName() ) ;
                $form->addError(new \Symfony\Component\Form\FormError( $error ));
            }
        } 
        
        if( isset($options['less_than']) ) {
            if( property_exists($object, $options['less_than']) ) {
                $property   = $options['less_than'] ;
                if( $parent->has($property) ) {
                    $_data   = $parent->get($property)->getData() ;
                } else {
                    $_data   = $admin->getReflectionProperty($property) ->getValue( $object ) ; 
                }

                $label  = $admin->getPropertyLabel( $property ) ;

                if( $_data && !is_numeric( $_data ) ) {
                        $error  = sprintf("配置错误, 比较目标(%s,%s) 不是数值类型", $label , $property ) ;
                        $form->addError(new \Symfony\Component\Form\FormError( $error ));
                } else {
                    if( !$_data || $data > $_data ) {
                        $error  = sprintf("该值应该小于%s %s%s", $label, $_data, $unit) ;
                        $form->addError(new \Symfony\Component\Form\FormError( $error ));
                    }
                }

            } else {
                $error  = sprintf("配置错误, 比较目标(%s) 不存是%s属性", $property, $this->admin->getClassName() ) ;
                $form->addError(new \Symfony\Component\Form\FormError( $error ));
            }
        } 
    }
}