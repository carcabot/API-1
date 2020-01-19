<?php

declare(strict_types=1);

namespace App\Domain\Command\Quotation;

use App\Entity\Quotation;

class UpdateQuotationToken
{
    /**
     * @var Quotation
     */
    private $quotation;

    /**
     * @param Quotation $quotation
     */
    public function __construct(Quotation $quotation)
    {
        $this->quotation = $quotation;
    }

    /**
     * Gets the quotation.
     *
     * @return Quotation
     */
    public function getQuotation(): Quotation
    {
        return $this->quotation;
    }
}
