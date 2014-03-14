<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType ;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\AdminBundle\Form\DataTransformer\FileTransformer ;


class FileType extends AbstractType {
    
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
        $builder->addViewTransformer( new FileTransformer() ) ;
        
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options){
            // respond to the event, modify data, or form elements
            $data = $event->getData();
            $object = $event->getForm()->getParent()->getData() ;
            if( !$object ) {
                return ;
            }
            $crop   = null ;
            if( isset($options['img_config']) ) {
                $crop   = $data['crop'] ;
                $data   = $data['url'] ;
            }
            
            $admin  = $this->container->get('app.admin.loader')->getAdminByClass( $options['admin_class'] ) ;
            
            $oldValue = $admin->getReflectionProperty( $options['admin_property'])->getValue($object) ;
            
            if( $data && preg_match('/^\/upload\/file\/([\w\-]+)\.\w+/', $data, $ls) ) {
                
                $em     = $admin->getManager() ;
                $file   = $em->getRepository('App\AdminBundle\Entity\File')->loadByUUID( $ls[1] ) ;
                
                $object_id  = $admin->getId( $object ) ;
                if( $object_id ) {
                    $file->setEntityId( $object_id );
                }
                
                if( $crop ) {
                    $this->container->get('app.admin.imagine')->resize($file, $crop, $options['img_config']);
                }
                
                if( $file->getSessionId() ) {
                    
                    if( $oldValue ) {
                        $em->remove( $oldValue ) ;
                    }
                    
                    $file->setIsHtmlFile( false ) ;
                    $file->setSessionId( null ) ;
                    
                    $em->persist( $file ) ;
                    $event->setData( $file ) ;
                } else {
                    $event->setData( $file ) ;
                }
                
            } else {
                $event->setData(null) ;
            }
        });
        
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($options){
            if( isset($options['required']) && $options['required'] ) {
                $data   = $event->getData();
                $form   = $event->getForm() ;
                if( empty($data['url']) ) {
                    $error  = $this->container->get('translator')->trans( 'form.file.required', array(
                        '%field%' => isset($options['label'])  ? $options['label']: $form->getName() , 
                    ) ) ;
                    $form->addError(new \Symfony\Component\Form\FormError( $error ));
                }
            }
        });
    }
    
    public function getName(){
        return 'appfile' ;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options) ;
        
        $view->vars['admin_name']    = $options['admin_name'] ;
        $view->vars['admin_id']    = $options['admin_id'] ;
        $view->vars['accept_file_type']    = $options['accept_file_type'] ;
        $view->vars['max_file_size']    = $options['max_file_size'] ;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
       
        $resolver->setDefaults( array(
            'compound'       => false ,
        ));
        
        $resolver->setRequired(array(
             'admin_class' ,
             'admin_property' ,
             'admin_name' , 
             'admin_id' , 
             'accept_file_type' ,
             'max_file_size' ,
        ));
    }
}
