<?php

namespace App\UserBundle\Form\Constraints ;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author loong
 */
class ChineseNameValidator extends ConstraintValidator {
       
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint){
        if (null === $value || '' === $value ) {
            return ;
        }
        
        if ( !preg_match('/^[\x{4e00}-\x{9fa5}]{2,5}$/u', $value) || preg_match('/^[0-9\.\-\_]|[\.\-\_]$/', $value) ) {
            $this->context->addViolation(  sprintf($constraint->message, $value ) ) ;
        }
    }
}