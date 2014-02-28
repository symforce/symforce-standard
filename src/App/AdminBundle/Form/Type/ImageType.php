<?php

namespace App\AdminBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType ;

/*
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
*/

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ImageType extends FileType {

    public function getName(){
        return 'appimage' ;
    }
    
    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options) ;
        $view->vars['img']    = array(
            'width' => $options['img_config'][0][0] ,
            'height' => $options['img_config'][0][1] ,
            'small_height' => $options['img_config'][1][0] ,
            'small_height' => $options['img_config'][1][1] ,
            'default' => $options['img_config'][2] ,
            'use_crop' => $options['img_config'][3] ,
        );
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        parent::setDefaultOptions($resolver) ;
        $resolver->setRequired(array(
            'img_config' ,
        ));
    }
    
}
