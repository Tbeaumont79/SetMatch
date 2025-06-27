<?php

namespace App\Service;

use App\Contract\ValidatorInterface as AppValidatorInterface;
use App\Exception\PostException;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class EntityValidationService implements AppValidatorInterface
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {}


    public function validateEntity(object $entity, ?array $groups = null): void
    {
        $violations = $this->validator->validate($entity, null, $groups);

        if (count($violations) === 0) {
            return;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = $violation->getMessage();
        }

        throw PostException::invalidContent(implode(', ', $errors));
    }


    public function getValidationErrors(object $entity, ?array $groups = null): array
    {
        $violations = $this->validator->validate($entity, null, $groups);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        return $errors;
    }
}
