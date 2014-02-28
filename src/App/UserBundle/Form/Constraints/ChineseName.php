<?php

namespace App\UserBundle\Form\Constraints ;

class ChineseName extends \Symfony\Component\Validator\Constraint {
    public $message = 'This value `%s` is not a valid username.' ;
}