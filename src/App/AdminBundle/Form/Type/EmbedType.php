<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType ;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\AdminBundle\Form\DataTransformer\EmbedHiddenTransformer ;



class EmbedType extends AbstractType {
    
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
        parent::buildForm($builder, $options ) ;
        
        if( !$options['children_render'] ) {
            $transformer    = new EmbedHiddenTransformer( $this->container->get('app.admin.loader'), $options['target_entity'] ) ;
            $builder->addModelTransformer( $transformer ) ;
            
            $builder->addEventListener(FormEvents::PRE_SUBMIT , function(FormEvent $event) use ($options, $transformer ){
                $parent = $event->getForm()->getParent() ;
                $object = $parent->getData() ;
                if( !$object ) {
                    throw new \Exception("can not work for null form");
                }
                if( !is_object($object) ) {
                    throw new \Exception("form data must be object");
                }
                if( !($object instanceof $options['source_entity'] ) ) {
                    throw new \Exception( sprintf("get type:%s, expect type:%s", get_class($object), $options['source_entity'] ));
                }
                $admin_loader   = $this->container->get('app.admin.loader') ;
                $source_admin   = $admin_loader->getAdminByClass( $options['source_entity'] ) ;
                $target_admin   = $admin_loader->getAdminByClass( $options['target_entity'] ) ;
                $_object   = $source_admin->getReflectionProperty( $event->getForm()->getName() )->getValue( $object ) ;
                $transformer->setReverseData( $_object ) ;
            });
        }
        
        $builder->addEventListener(FormEvents::POST_SUBMIT , function(FormEvent $event) use ($options ){
            // respond to the event, modify data, or form elements
            $data  = $event->getData();
            $parent = $event->getForm()->getParent() ;
            $object = $parent->getData() ;
            if( !$object ) {
                throw new \Exception("can not work for null form");
            }
            if( !is_object($object) ) {
                throw new \Exception("form data must be object");
            }
            if( !($object instanceof $options['source_entity'] ) ) {
                throw new \Exception( sprintf("get type:%s, expect type:%s", get_class($object), $options['source_entity'] ));
            }
            $admin_loader   = $this->container->get('app.admin.loader') ;
            $source_admin   = $admin_loader->getAdminByClass( $options['source_entity'] ) ;
            $target_admin   = $admin_loader->getAdminByClass( $options['target_entity'] ) ;
            if( !$options['children_render'] ) {
                $data = $source_admin->getReflectionProperty( $event->getForm()->getName() )->getValue( $object ) ;
            }
            foreach($options['copy_properties']  as $from_property => $to_property ) {
                if( $parent->has($from_property) ) {
                    $to_value   = $parent->get($from_property)->getData() ;
                } else {
                    $from_prod  = $source_admin->getReflectionProperty( $from_property ) ;
                    $to_value   = $from_prod->getValue($object) ;
                }
                $to_prop    = $target_admin->getReflectionProperty( $to_property ) ;
                $to_prop->setValue($data, $to_value );
            }
        });
    }
    
    public function getName(){
        return 'appembed' ;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options) ;
        $view->vars['children_render']  = $options['children_render'] ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired(array(
             'copy_properties' ,
             'target_entity' ,
             'source_entity' ,
        ));
        
        $resolver->setDefaults(array(
            'children_render'   => true ,
        ));
    }
}
