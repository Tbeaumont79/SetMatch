<?php

namespace App\Contract;

interface ValidatorInterface
{

    public function validateEntity(object $entity, ?array $groups = null): void;


    public function getValidationErrors(object $entity, ?array $groups = null): array;
}
