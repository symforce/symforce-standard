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
use App\AdminBundle\Form\DataTransformer\EmbedHiddenTransformer ;



class WorkflowType extends ChoiceType {
    
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
        $builder->addEventListener(FormEvents::POST_SUBMIT , function(FormEvent $event){
            $parent = $event->getForm()->getParent() ;
            $object = $parent->getData() ;
            if( !$object ) {
                throw new \Exception("can not work for null form");
            }
            if( !is_object($object) ) {
                throw new \Exception("form data must be object");
            }
            $admin_loader   = $this->container->get('app.admin.loader') ;
            $admin      = $admin_loader->getAdminByClass( $object ) ;
            $new_data   = $event->getForm()->getData() ;
            $property   = $event->getForm()->getName() ;
            $old_data   = $admin->getReflectionProperty($property)->getValue($object);
            
            if( $new_data !== $old_data ) {
                $admin->addEvent( 'update' , function($_object, $admin) use($old_data, $new_data, $object) {
                    if( $_object !== $object ) {
                        throw new \Exception("big error") ;
                    }
                    $_new_data =  $admin->getReflectionProperty( $admin->workflow['property'])->getValue($object);
                    if( $_new_data !== $new_data ) {
                        throw new \Exception("big error") ;
                    }
                    $admin->onWorkflowValueChange($object, $new_data, $old_data) ;
                });
            }
        });
        
    }
    
    public function getName(){
        return 'appworkflow' ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired( array(
             'admin_class' ,
        ));
    }
} 