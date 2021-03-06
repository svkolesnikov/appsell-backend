<?php

namespace App\Entity;

use App\Lib\Enum\OfferLinkTypeEnum;
use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="offerdata.offer_link")
 * @ORM\HasLifecycleCallbacks
 */
class OfferLink
{
    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @var Offer
     * @ORM\ManyToOne(targetEntity = "Offer", inversedBy = "links")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="string")
     */
    protected $url;

    /**
     * @ORM\Column(type="string")
     */
    protected $image;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $ctime;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $mtime;

    public function __construct()
    {
        $this->type = OfferLinkTypeEnum::WEB;
        $this->url  = '';
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->ctime = new \DateTime();
        $this->mtime = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->mtime = new \DateTime();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOffer(): Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer): void
    {
        $this->offer = $offer;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url)
    {
        $applePattern  = '/itunes.apple.com/';
        $googlePattern = '/play.google.com/';

        if (preg_match($applePattern, $url)) {
            $this->type = OfferLinkTypeEnum::APP_STORE()->getValue();
        } elseif (preg_match($googlePattern, $url)) {
            $this->type = OfferLinkTypeEnum::GOOGLE_PLAY()->getValue();
        } else {
            $this->type = OfferLinkTypeEnum::WEB()->getValue();
        }

        $this->url = $url;
        return $this;
    }

    public function getCtime(): \DateTime
    {
        return $this->ctime;
    }

    public function getMtime(): \DateTime
    {
        return $this->mtime;
    }

    /**
     * @return OfferLinkTypeEnum
     * @throws \UnexpectedValueException
     */
    public function getType(): OfferLinkTypeEnum
    {
        return new OfferLinkTypeEnum($this->type);
    }

    public function setType(OfferLinkTypeEnum $type)
    {
        $this->type = $type->getValue();
        return $this;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }
}