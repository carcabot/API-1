<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ApplicationRequestPromotion extends Constraint
{
    public $applicationRequestPromotionNotValidField = 'This promotion is not valid';
    public $applicationRequestPromotionFullyRedeemedField = 'This promotion has been fully redeemed';
    public $applicationRequestPromotionNotExistingField = 'This promotion does not exist';
    public $applicationRequestPromotionCustomerTypeNotValidField = 'This promotion is not valid for this customer type.';
    public $applicationRequestPromotionContractTypeNotValidField = 'This promotion is not valid for this contract type.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
