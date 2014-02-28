<?php

namespace App\UserBundle\Form\Constraints ;

class Password extends \Symfony\Component\Validator\Constraint {
    public $message = 'This value `%s` is not a valid password.' ;
} 