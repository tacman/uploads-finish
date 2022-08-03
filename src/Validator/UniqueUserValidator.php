<?php

namespace App\Validator;

use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueUserValidator extends ConstraintValidator
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public function validate($value, Constraint $constraint)
    {
        $existingUser = $this->userRepository->findOneBy([
            'email' => $value
        ]);

        if (!$existingUser) {
            return;
        }

        /* @var $constraint \App\Validator\UniqueUser */

        $this->context->buildViolation($constraint->message)
            ->addViolation();
    }
}
