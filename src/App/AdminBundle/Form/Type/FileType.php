<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType ;

use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;


use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use App\AdminBundle\Form\DataTransformer\FileTransformer ;


class FileType extends HiddenType {
    
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
        parent::setDefaultOptions($resolver) ;
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
