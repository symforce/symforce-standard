<?php

namespace App\UserBundle\Form\Constraints ;

class IdCard extends \Symfony\Component\Validator\Constraint {
    public $message = 'This value is not a valid ID Card.' ;
    public $bypass_code = 'no1' ;
}
