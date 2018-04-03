<?php

namespace App\ORM\Id;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Ramsey\Uuid\Uuid;

class UuidGenerator extends AbstractIdGenerator
{
    public function generate(EntityManager $em, $entity)
    {
        return Uuid::uuid4()->toString();
    }
}