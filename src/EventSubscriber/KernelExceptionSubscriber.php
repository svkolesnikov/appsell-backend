<?php

namespace App\EventSubscriber;

use App\Exception\AccessTokenException;
use App\Exception\AppException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class KernelExceptionSubscriber implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $l)
    {
        $this->logger = $l;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [['handleHttpAception', 0]],
        ];
    }

    protected function maskSecretFields(array $params): array
    {
        $fields = ['password', 'php-auth-pw'];
        $result = [];

        foreach ($params as $key => $value) {
            $result[$key] = \in_array($key, $fields)
                ? '***'
                : $value;
        }

        return $result;
    }

    public function handleHttpAception(GetResponseForExceptionEvent $event): void
    {
        $exception  = $event->getException();
        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        $response   = [
            'message' => $exception->getMessage(),
            'details' => []
        ];

        $logLevel   = Logger::ERROR;
        $logContext = [
            'exception' => \get_class($exception),
            'request'   => $this->maskSecretFields($event->getRequest()->request->all()),
            'headers'   => $this->maskSecretFields($event->getRequest()->headers->all())
        ];

        if ($exception instanceof HttpException) {

            $statusCode = $exception->getStatusCode();
            if ($statusCode === JsonResponse::HTTP_NOT_FOUND) {
                $logLevel = Logger::NOTICE;
            }

        } elseif ($exception instanceof AccessTokenException) {

            $statusCode            = JsonResponse::HTTP_FORBIDDEN;
            $response['details']   = $exception->getErrors();
            $logContext['details'] = $exception->getErrors();

        } elseif ($exception instanceof AppException) {

            $statusCode            = JsonResponse::HTTP_BAD_REQUEST;
            $response['details']   = $exception->getErrors();
            $logContext['details'] = $exception->getErrors();

        } elseif ($exception instanceof InsufficientAuthenticationException) {

            $statusCode            = JsonResponse::HTTP_UNAUTHORIZED;

        } else {
            $logLevel            = Logger::CRITICAL;
            $response['message'] = 'Internal Server Error';
        }

        $this->logger->log($logLevel, $exception->getMessage(), $logContext);
        $event->setResponse(new JsonResponse($response, $statusCode));
    }
}