<?php

declare(strict_types=1);

namespace App\Domain\Command\Offer;

use App\Entity\Offer;

class UpdateOfferNumber
{
    /**
     * @var Offer
     */
    private $offer;

    /**
     * @param Offer $offer
     */
    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * Gets offer.
     *
     * @return Offer
     */
    public function getOffer(): Offer
    {
        return $this->offer;
    }
}
