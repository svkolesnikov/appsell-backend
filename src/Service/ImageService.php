<?php

namespace App\Service;

use App\Exception\Admin\LoadExternalImageException;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\HttpFoundation\RequestStack;

class ImageService
{
    protected $imageStorage;

    protected $requestStack;

    /** @var Client */
    protected $httpClient;

    public function __construct(RequestStack $requestStack, Client $httpClient, $imageStorePath)
    {
        $this->imageStorage = $imageStorePath;
        $this->requestStack = $requestStack;
        $this->httpClient   = $httpClient;
    }

    /**
     * Сохранение картинки из определенного узла страницы в хранилище
     *
     * @param string $url
     * @param string $imageTag
     * @return string
     * @throws LoadExternalImageException
     */
    public function saveFromHTML($url, $imageTag = 'og:image'): string
    {
        try {

            $htmlDocument = $this->httpClient
                ->get($url, [RequestOptions::ALLOW_REDIRECTS => true])
                ->getBody()
                ->getContents();

        } catch (\Exception $ex) {
            $message = "Не удалось загрузить картинку из $url по причине: " . $ex->getMessage();
            throw new LoadExternalImageException($message);
        }

        $doc = new DomDocument('1.0', 'UTF-8');

        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($htmlDocument);
        libxml_use_internal_errors($internalErrors);

        $xpath = new DOMXPath($doc);

        $query = sprintf('//*/meta[starts-with(@property, \'%s\')]', $imageTag);
        $metas = $xpath->query($query);

        if (0 === count($metas)) {
            throw new LoadExternalImageException('Не обнаружен узел с картинкой!');
        }

        /** @var \DOMElement $meta */
        $meta     = $metas[0];
        $content  = $meta->getAttribute('content');

        $tmpImagePath = tempnam(sys_get_temp_dir(), 'offer_image');
        copy($content, $tmpImagePath);

        $imageProps = getimagesize($tmpImagePath);
        $allowedTypes = [
            'image/png',
            'image/jpeg',
            'image/gif'
        ];

        if (!in_array($imageProps['mime'], $allowedTypes, true)) {
            unlink($tmpImagePath);
            throw new LoadExternalImageException('Неподдерживаемый тип картинки – ' . $imageProps['mime']);
        }

        $extension = explode('/', $imageProps['mime'])[1];
        $fileName  = sprintf('%s.%s', uniqid('', false), $extension);

        copy($tmpImagePath, $this->imageStorage. '/' . $fileName);
        unlink($tmpImagePath);

        return $fileName;
    }

    public function remove($url): void
    {
        $fullPath = $this->imageStorage . '/' . $url;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    public function getPublicUrl($url): ?string
    {
        if (empty($url)) {
            return null;
        }

        return sprintf(
            '%s/images/%s',
            $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost(), $url
        );
    }
}