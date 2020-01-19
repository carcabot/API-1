<?php

declare(strict_types=1);

namespace App\Domain\Command\CustomerAccount;

class UpdateRelationshipsHandler
{
    public function handle(UpdateRelationships $command): void
    {
        $relationship = $command->getRelationship();

        $fromCustomer = $relationship->getFrom();
        $toCustomer = $relationship->getTo();

        // check 'from' side of the relationship
        $fromExists = false;
        foreach ($fromCustomer->getRelationships() as $fromRelationship) {
            if (
                $fromRelationship->getFrom() === $fromCustomer &&
                $fromRelationship->getTo() === $toCustomer &&
                $fromRelationship->getType()->getValue() === $relationship->getType()->getValue()
            ) {
                $fromExists = true;
                break;
            }
        }

        if (false === $fromExists) {
            $fromCustomer->addRelationship($relationship);
        }

        // check 'to' side of the relationship
        $toExists = false;
        foreach ($toCustomer->getRelationships() as $toRelationship) {
            if (
                $toRelationship->getFrom() === $fromCustomer &&
                $toRelationship->getTo() === $toCustomer &&
                $toRelationship->getType()->getValue() === $relationship->getType()->getValue()
            ) {
                $toExists = true;
                break;
            }
        }

        if (false === $toExists) {
            $toCustomer->addRelationship($relationship);
        }
    }
}
