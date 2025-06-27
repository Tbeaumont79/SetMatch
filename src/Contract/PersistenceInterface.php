<?php

namespace App\Contract;


interface PersistenceInterface
{

    public function persist(object $entity): void;


    public function flush(): void;


    public function persistAndFlush(object $entity): void;
}
