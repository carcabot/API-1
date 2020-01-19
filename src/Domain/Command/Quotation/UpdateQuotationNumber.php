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

use App\Entity\Quotation;

class UpdateQuotationNumber
{
    /**
     * @var Quotation
     */
    private $quotation;

    /**
     * UpdateQuotationNumber constructor.
     *
     * @param Quotation $quotation
     */
    public function __construct(Quotation $quotation)
    {
        $this->quotation = $quotation;
    }

    /**
     * @return Quotation
     */
    public function getQuotation(): Quotation
    {
        return $this->quotation;
    }
}
