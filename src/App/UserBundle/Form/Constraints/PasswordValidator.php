<?php

namespace App\UserBundle\Form\Constraints ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author loong
 */
class PasswordValidator extends ConstraintValidator {
       
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint){
        if (null === $value || '' === $value ) {
            return ;
        }
        
        if ( 
                !preg_match('/\d/u', $value) 
                || !preg_match('/[a-z]/', $value)
                || !preg_match('/[A-Z]/', $value)
                || !preg_match('/\W/', $value)
           ) {
            $this->context->addViolation(  sprintf($constraint->message, $value ) ) ;
        }
    }
}
