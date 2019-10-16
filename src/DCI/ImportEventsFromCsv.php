<?php

namespace App\DCI;

use App\Entity\ImportFromCsvLogItem;
use App\Entity\User;
use App\Exception\Admin\ImportFromCsvException;
use App\Lib\Enum\SdkEventSourceEnum;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportEventsFromCsv
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var SdkEventCreating */
    private $eventCreator;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManagerInterface $em, SdkEventCreating $ec, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->eventCreator = $ec;
    }

    public function import(
        string $delimeter,
        int $clickIdColumn,
        int $eventColumn,
        UploadedFile $file,
        string $userId
    ): void
    {
        $importedEventsCount = 0;

        // Сначала пробуем разобрать файл

        $csvFile = new \SplFileObject($file->getPath() . '/' . $file->getFilename());
        $csvFile->setCsvControl($delimeter);

        while ($cells = $csvFile->fgetcsv()) {

            // Не удалось разобрать строку
            if (\count($cells) < 2) {
                continue;
            }

            $clickIdRow = $clickIdColumn - 1;
            $clickId    = $cells[$clickIdRow];

            $eventRow   = $eventColumn - 1;
            $eventName  = $cells[$eventRow];

            $error = null;

            try {
                $this->eventCreator->createFromClickId(
                    $clickId,
                    $eventName,
                    SdkEventSourceEnum::CSV()
                );

            } catch (\Exception $ex) {

                $error = $ex->getMessage();
                $this->logger->error(
                    'Не удалось импортировать событие из CSV: ' . $ex->getMessage(),
                    $cells
                );
            }

            $existsLogItem = $this->em->getRepository(ImportFromCsvLogItem::class)->findOneBy([
                'click_id' => $clickId,
                'event_name' => $eventName
            ]);

            if (null !== $existsLogItem) {
                $this->logger->warning("Событие $clickId,$eventName уже импортировалось");
            } else {

                $logItem = new ImportFromCsvLogItem(
                    $file->getClientOriginalName(),
                    $userId,
                    $clickId,
                    $eventName,
                    $error,
                    implode($delimeter, $cells)
                );

                $this->em->persist($logItem);
                $this->em->flush();

                $importedEventsCount++;
            }
        }

        if (0 === $importedEventsCount) {
            throw new ImportFromCsvException('Переданный файл не содержит событий');
        }
    }
}