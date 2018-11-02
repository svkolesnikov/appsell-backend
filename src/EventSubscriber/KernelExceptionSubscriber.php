<?php

namespace App\EventSubscriber;

use App\Exception\Api;
use Doctrine\DBAL\Exception\DriverException;
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
            $result[$key] = \in_array($key, $fields, true) ? '***' : $value;
        }

        return $result;
    }

    public function handleHttpAception(GetResponseForExceptionEvent $event): void
    {
        $exception  = $event->getException();
        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        // Данный обработчик должен обрабатывать исключения
        // только в разделе API

        if (0 !== strpos($event->getRequest()->get('_route'), 'app_api_')) {
            return;
        }

        $response   = [
            'message' => null,
            'details' => []
        ];

        $logLevel   = Logger::ERROR;
        $logContext = [
            'exception' => \get_class($exception),
            'request'   => $this->maskSecretFields($event->getRequest()->request->all()),
            'headers'   => $this->maskSecretFields($event->getRequest()->headers->all())
        ];

        if ($exception instanceof HttpException) {

            $response['message'] = $exception->getMessage();
            $statusCode          = $exception->getStatusCode();
            if ($statusCode === JsonResponse::HTTP_NOT_FOUND) {
                $logLevel = Logger::NOTICE;
            }

        } elseif ($exception instanceof Api\AccessTokenException) {

            $statusCode            = JsonResponse::HTTP_FORBIDDEN;
            $logContext['details'] = $exception->getErrors();

        } elseif ($exception instanceof Api\FormValidationException) {

            $statusCode            = JsonResponse::HTTP_BAD_REQUEST;
            $response['details']   = $exception->getErrors();
            $logContext['details'] = $exception->getErrors();

        } elseif ($exception instanceof Api\ApiException) {

            $statusCode            = JsonResponse::HTTP_BAD_REQUEST;
            $response['message']   = $exception->getMessage();
            $logContext['details'] = $exception->getErrors();

        } elseif ($exception instanceof InsufficientAuthenticationException) {

            $statusCode = JsonResponse::HTTP_UNAUTHORIZED;

        } elseif ($exception instanceof DriverException) {

            $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
            $response['message'] = 'Internal Server Error';

            if (preg_match('/invalid input syntax for type uuid/i', $exception->getMessage())) {
                $statusCode = JsonResponse::HTTP_NOT_FOUND;
                $response['message'] = 'Указан некорректный идентификатор контента';
            }

        } else {
            $logLevel            = Logger::CRITICAL;
            $response['message'] = 'Internal Server Error';
        }

        $this->logger->log($logLevel, $exception->getMessage(), $logContext);
        $event->setResponse(new JsonResponse($response, $statusCode));
    }
}