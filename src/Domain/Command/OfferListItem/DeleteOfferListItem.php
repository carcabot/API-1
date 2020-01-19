<?php

declare(strict_types=1);

namespace App\Domain\Command\OfferListItem;

use App\Entity\OfferListItem;

class DeleteOfferListItem
{
    /**
     * @var OfferListItem
     */
    private $offerListItem;

    /**
     * @param OfferListItem $offerListItem
     */
    public function __construct(OfferListItem $offerListItem)
    {
        $this->offerListItem = $offerListItem;
    }

    /**
     * @return OfferListItem
     */
    public function getOfferListItem(): OfferListItem
    {
        return $this->offerListItem;
    }
}
