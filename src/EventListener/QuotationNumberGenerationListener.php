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

namespace App\EventListener;

use App\Domain\Command\Quotation\UpdateQuotationNumber;
use App\Entity\Quotation;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

class QuotationNumberGenerationListener
{
    use Traits\RunningNumberLockTrait;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * QuotationNumberGenerationListener constructor.
     *
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager)
    {
        $this->setEntityManager($entityManager);
        $this->commandBus = $commandBus;
        $this->setLocked(false);
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $controllerResult = $event->getControllerResult();

        if (!($controllerResult instanceof Quotation)) {
            return;
        }

        /** @var Quotation $quotation */
        $quotation = $controllerResult;

        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        $this->startLockTransaction();
        $this->commandBus->handle(new UpdateQuotationNumber($quotation));
    }

    public function onPostWrite(GetResponseForControllerResultEvent $event)
    {
        $this->endLockTransaction();
    }
}
