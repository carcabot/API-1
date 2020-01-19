<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\RedeemCreditsAction;

use App\Entity\RedeemCreditsAction;

class BuildRedeemCreditsActionData
{
    /**
     * @var RedeemCreditsAction[]
     */
    private $redeemedCreditsActions;

    /**
     * @param RedeemCreditsAction[] $redeemedCreditsActions
     */
    public function __construct(array $redeemedCreditsActions)
    {
        $this->redeemedCreditsActions = $redeemedCreditsActions;
    }

    /**
     * @return RedeemCreditsAction[]
     */
    public function getRedeemedCreditsActions(): array
    {
        return $this->redeemedCreditsActions;
    }
}
