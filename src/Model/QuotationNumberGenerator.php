<?php

declare(strict_types=1);
/*
 * This file is part of the U-Centric project.
 *
 * (c) U-Centric Development Team <dev@ucentric.sisgroup.sg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Entity\Quotation;

class QuotationNumberGenerator
{
    const LENGTH = 9;
    const PREFIX = 'Q';
    const TYPE = 'quotation';

    /**
     * @var RunningNumberGenerator
     */
    private $runningNumberGenerator;

    /**
     * @param RunningNumberGenerator $runningNumberGenerator
     */
    public function __construct(RunningNumberGenerator $runningNumberGenerator)
    {
        $this->runningNumberGenerator = $runningNumberGenerator;
    }

    /**
     * Generates a quotation number.
     *
     * @param Quotation $quotation
     *
     * @return string
     */
    public function generate(Quotation $quotation)
    {
        $nextNumber = $this->runningNumberGenerator->getNextNumber(self::TYPE, (string) self::LENGTH);
        $quotationNumber = \sprintf('%s%s', self::PREFIX, \str_pad((string) $nextNumber, self::LENGTH, '0', STR_PAD_LEFT));

        return $quotationNumber;
    }
}
