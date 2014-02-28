<?php

namespace App\AdminBundle\Menu ;


use Knp\Menu\ItemInterface;
use Knp\Menu\Renderer\TwigRenderer;
 
class BootstrapRenderer extends TwigRenderer
{
    public function render(ItemInterface $item, array $options = array())
    {
        $options = array_merge(
            array('currentClass' => 'active') ,
            $options
        );
 
        if ( $item->isRoot() ) {
            $item->setChildrenAttribute('class', trim('nav navbar-nav ' . $item->getChildrenAttribute('class'))) ;
        }
        
        return parent::render($item, $options);
    }
}