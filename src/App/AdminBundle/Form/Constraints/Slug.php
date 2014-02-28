<?php

namespace App\AdminBundle\Form\Constraints ;


/**
 * @author loong
 */
class Slug extends \Symfony\Component\Validator\Constraint {
    public $message = 'This value is not a valid slug.' ;
    public $create  = false ;
}
