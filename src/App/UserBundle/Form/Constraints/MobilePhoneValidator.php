<?php

namespace App\UserBundle\Form\Constraints ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author loong
 */
class MobilePhoneValidator extends ConstraintValidator {
       
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint){
        if (null === $value || '' === $value ) {
            return ;
        }
        
        if ( !preg_match('/^1[3,5,8]\d{9}$/', $value) ) {
            $this->context->addViolation(  sprintf($constraint->message, $value ) ) ;
        }
    }
}
