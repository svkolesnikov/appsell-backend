<?php

namespace App\Controller\Api;

use App\Entity\SellerOfferLink;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectToStoreController
{
    /**
     * @param SellerOfferLink $data
     * @return RedirectResponse
     * @throws \InvalidArgumentException
     */
    public function __invoke(SellerOfferLink $data): RedirectResponse
    {
        // todo: Затрекать установку приложения

        return new RedirectResponse($data->getOfferApp()->getUrl());
    }
}