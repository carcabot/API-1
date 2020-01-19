<?php

declare(strict_types=1);

namespace App\Domain\Command\UpdateCreditsAction;

use App\Entity\UpdateCreditsAction;

/**
 * Creates the reciprocal action.
 */
class CreateReciprocalAction
{
    /**
     * @var UpdateCreditsAction
     */
    private $updateCreditsAction;

    /**
     * @param UpdateCreditsAction $updateCreditsAction
     */
    public function __construct(UpdateCreditsAction $updateCreditsAction)
    {
        $this->updateCreditsAction = $updateCreditsAction;
    }

    /**
     * @return UpdateCreditsAction
     */
    public function getUpdateCreditsAction(): UpdateCreditsAction
    {
        return $this->updateCreditsAction;
    }
}
