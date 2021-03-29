<?php

namespace App\Entity;

use App\Lib\Enum\OfferTypeEnum;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use App\Lib\Orm\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass="App\Entity\Repository\PromoCodeRepository")
 * @ORM\Table(name="promo_codes")
 * @ORM\HasLifecycleCallbacks
 */
class PromoCode
{
    const STATUS_FRESH = 'fresh';
    const STATUS_SETTLE = 'settle';
    const STATUS_ACTIVATED = 'activated';
    const STATUS_RESSURECTED = 'ressurected';

    public static function getStatusesWithValues(): array
    {
        return [
            self::STATUS_FRESH => self::STATUS_FRESH,
            self::STATUS_SETTLE => self::STATUS_SETTLE,
            self::STATUS_ACTIVATED => self::STATUS_ACTIVATED,
            self::STATUS_RESSURECTED => self::STATUS_RESSURECTED,
        ];
    }

    /**
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidGenerator::class)
     */
    protected $id;

    /**
     * @var Offer
     *
     * @ORM\ManyToOne(targetEntity = "Offer")
     * @ORM\JoinColumn(name = "offer_id", referencedColumnName = "id")
     */
    protected $offer;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id", referencedColumnName = "id")
     */
    protected $user;

    /**
     * @ORM\Column(type="string")
     */
    protected $promoCode;

    /**
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @ORM\Column(type="string")
     */
    protected ?string $description = '';

    public function getId(): string
    {
        return $this->id;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(Offer $offer)
    {
        $this->offer = $offer;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;
        return $this;
    }

    public function getPromoCode(): ?string
    {
        return $this->promoCode;
    }

    public function setPromoCode(string $promoCode)
    {
        $this->promoCode = $promoCode;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description)
    {
        $this->description = $description ? $description : '';
        return $this;
    }

}