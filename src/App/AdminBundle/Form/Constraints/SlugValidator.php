<?php

namespace App\AdminBundle\Form\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author loong
 */
class SlugValidator extends ConstraintValidator {
    
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value ) {
            return ;
        }
        
        if ( !preg_match('/[a-z]/i', $value) || !preg_match('/^[\w\-]+$/', $value) ) {
            $this->context->addViolation($constraint->message);
        }
    }
}
