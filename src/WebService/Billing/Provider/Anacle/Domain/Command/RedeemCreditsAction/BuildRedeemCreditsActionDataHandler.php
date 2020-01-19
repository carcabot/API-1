<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle\Domain\Command\RedeemCreditsAction;

use App\Entity\OrderItem;
use App\Entity\RedeemCreditsAction;
use App\Enum\OfferType;

class BuildRedeemCreditsActionDataHandler
{
    public function handle(BuildRedeemCreditsActionData $command): array
    {
        /**
         * @var RedeemCreditsAction[]
         */
        $redeemedCreditsActions = $command->getRedeemedCreditsActions();
        $redeemedCreditsActionData = [];
        $timezone = new \DateTimeZone('Asia/Singapore');

        foreach ($redeemedCreditsActions as $redeemedCreditsAction) {
            $contractNumber = $redeemedCreditsAction->getObject()->getContractNumber();
            $orderNumber = $redeemedCreditsAction->getInstrument()->getOrderNumber();
            $redeemDate = $redeemedCreditsAction->getStartTime();
            $redeemDate->setTimezone($timezone);
            $amount = 0;
            $points = 0;
            $isBillRebate = false;
            /**
             * @var OrderItem[]
             */
            $orderItems = $redeemedCreditsAction->getInstrument()->getItems();
            foreach ($orderItems as $orderItem) {
                if (OfferType::BILL_REBATE === $orderItem->getOfferListItem()->getItem()->getType()->getValue()) {
                    $points += (float) $redeemedCreditsAction->getAmount();
                    $amount += (float) $redeemedCreditsAction->getAmount() / ((float) $orderItem->getOfferListItem()->getPriceSpecification()->getPrice() / (float) $orderItem->getOfferListItem()->getMonetaryExchangeValue()->getValue());

                    $isBillRebate = true;
                }
            }

            if (false === $isBillRebate) {
                continue;
            }

            $billRebateRedeemedCreditsActionData = [
                'CustomerAccountNumber' => $contractNumber,
                'RedemptionOrderNumber' => $orderNumber,
                'RedemptionDate' => \sprintf('%sT%sZ', $redeemDate->format('Y-m-d'), $redeemDate->format('H:i:s.u')),
                'Amount' => \number_format($amount, 2),
                'RedemptionPoints' => $points,
            ];

            $redeemedCreditsActionData[] = $billRebateRedeemedCreditsActionData;
        }

        return $redeemedCreditsActionData;
    }
}
