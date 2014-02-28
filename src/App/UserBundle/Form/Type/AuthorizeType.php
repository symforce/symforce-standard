<?php

namespace App\UserBundle\Form\Type ;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\AbstractType ;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\PropertyAccess\PropertyAccess;


/**
 * Description of Authorize
 *
 * @author loong
 */
class AuthorizeType extends AbstractType {
    
    protected $app ;

    public function setContainer($app){
        $this->app  = $app ;
    }


    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => false ,
        ));
    }

    /**
     * Pass the image URL to the view
     *
     * @param FormView $view
     * @param FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['admin_tree']   = $this->app->get('app.admin.loader')->getAdminTree() ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'appauthorize';
    }
}