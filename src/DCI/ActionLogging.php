<?php

namespace App\DCI;

use App\Entity\ActionLog;
use App\Lib\Enum\ActionLogItemTypeEnum;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ActionLogging
{
    /** @var EntityManagerInterface|EntityManager */
    protected $entityManager;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->entityManager = $em;
        $this->logger = $logger;
    }

    public function log(ActionLogItemTypeEnum $type, string $message, array $data = [], Request $request = null): void
    {
        if (null !== $request) {
            $data['request'] = array_intersect_key(
                $request->server->all(),
                array_flip([
                    'HTTP_USER_AGENT',
                    'REMOTE_ADDR'
                ])
            );
        }

        $logItem = new ActionLog();
        $logItem->setType($type);
        $logItem->setMessage($message);
        $logItem->setData($data);

        try {

            if (!$this->entityManager->isOpen()) {
                $this->entityManager = EntityManager::create(
                    $this->entityManager->getConnection(),
                    $this->entityManager->getConfiguration()
                );
            }

            $this->entityManager->persist($logItem);
            $this->entityManager->flush();

        } catch (ORMException $ex) {
            $this->logger->error('Не удалось создать запись в лога в БД');
        }
    }
}