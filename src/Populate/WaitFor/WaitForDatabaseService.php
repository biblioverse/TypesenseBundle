<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Populate\WaitFor;

use Doctrine\ORM\EntityManagerInterface;

class WaitForDatabaseService extends AbstractWaitForService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getName(): string
    {
        return 'Database';
    }

    public function doCheck(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isConnected()) {
            return;
        }
        $connection->executeQuery('SELECT 1');
    }
}
