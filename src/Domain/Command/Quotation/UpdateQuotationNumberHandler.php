<?php
/*
 * This file is part of the U-Centric project.
 *
 * (c) U-Centric Development Team <dev@ucentric.sisgroup.sg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Domain\Command\Quotation;

use App\Model\QuotationNumberGenerator;

class UpdateQuotationNumberHandler
{
    /**
     * @var QuotationNumberGenerator
     */
    private $quotationNumberGenerator;

    /**
     * UpdateQuotationNumberHandler constructor.
     *
     * @param QuotationNumberGenerator $quotationNumberGenerator
     */
    public function __construct(QuotationNumberGenerator $quotationNumberGenerator)
    {
        $this->quotationNumberGenerator = $quotationNumberGenerator;
    }

    public function handle(UpdateQuotationNumber $command): void
    {
        $quotation = $command->getQuotation();
        $quotationNumber = $this->quotationNumberGenerator->generate($quotation);

        $quotation->setQuotationNumber($quotationNumber);
    }
}
