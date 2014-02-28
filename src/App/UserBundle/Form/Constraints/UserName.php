<?php

namespace App\UserBundle\Form\Constraints ;

class UserName extends \Symfony\Component\Validator\Constraint {
    public $message = 'This value `%s` is not a valid username.' ;
}