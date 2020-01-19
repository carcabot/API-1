<?php

declare(strict_types=1);

namespace App\Domain\Command\Offer;

use App\Model\OfferNumberGenerator;

class UpdateOfferNumberHandler
{
    /**
     * @var OfferNumberGenerator
     */
    private $offerNumberGenerator;

    /**
     * @param OfferNumberGenerator $offerNumberGenerator
     */
    public function __construct(OfferNumberGenerator $offerNumberGenerator)
    {
        $this->offerNumberGenerator = $offerNumberGenerator;
    }

    public function handle(UpdateOfferNumber $command): void
    {
        $offer = $command->getOffer();
        $offerNumber = $this->offerNumberGenerator->generate();

        $offer->setOfferNumber($offerNumber);
    }
}
