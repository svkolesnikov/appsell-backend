<?php

namespace App\Service;

use DOMDocument;
use DOMXPath;
use Symfony\Component\HttpFoundation\RequestStack;

class ImageService
{
    protected $imageStorage;

    protected $requestStack;

    public function __construct(RequestStack $requestStack, $imageStorePath)
    {
        $this->imageStorage = $imageStorePath;
        $this->requestStack = $requestStack;
    }

    /**
     * Сохранение картинки из определенного узла страницы в хранилище
     *
     * @param string $url
     * @param string $imageTag
     * @return string
     * @throws \Exception
     */
    public function saveFromHTML($url, $imageTag = 'og:image'): string
    {
        $doc = new DomDocument('1.0', 'UTF-8');

        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTMLFile($url);
        libxml_use_internal_errors($internalErrors);

        $xpath = new DOMXPath($doc);

        $query = sprintf('//*/meta[starts-with(@property, \'%s\')]', $imageTag);
        $metas = $xpath->query($query);

        if (0 === count($metas)) {
            throw new \Exception('Не обнаружен узел с картинкой!');
        }

        /** @var \DOMElement $meta */
        $meta     = $metas[0];
        $content  = $meta->getAttribute('content');

        $extension = pathinfo(parse_url($content)['path'])['extension'];
        $fileName = sprintf('%s.%s', uniqid(), $extension);

        file_put_contents($this->imageStorage. '/' . $fileName, file_get_contents($content));

        return $fileName;
    }

    public function remove($url)
    {
        unlink($this->imageStorage . '/' . $url);
    }

    public function getPublicUrl($url)
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