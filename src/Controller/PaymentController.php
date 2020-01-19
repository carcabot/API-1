<?php

declare(strict_types=1);

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Domain\Command\Payment\UpdatePaymentNumber;
use App\Entity\Contract;
use App\Entity\Payment;
use App\Enum\PaymentMode;
use App\Enum\PaymentStatus;
use App\Service\AuthenticationHelper;
use App\WebService\PaymentGateway\ClientInterface as PaymentGatewayClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Tactician\CommandBus;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class PaymentController
{
    /**
     * @var AuthenticationHelper
     */
    private $authHelper;

    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var PaymentGatewayClientInterface
     */
    private $paymentGatewayClient;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param AuthenticationHelper          $authHelper
     * @param CommandBus                    $commandBus
     * @param EntityManagerInterface        $entityManager
     * @param IriConverterInterface         $iriConverter
     * @param SerializerInterface           $serializer
     * @param PaymentGatewayClientInterface $paymentGatewayClient
     */
    public function __construct(AuthenticationHelper $authHelper, CommandBus $commandBus, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, SerializerInterface $serializer, PaymentGatewayClientInterface $paymentGatewayClient)
    {
        $this->authHelper = $authHelper;
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->paymentGatewayClient = $paymentGatewayClient;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/payment_requests", methods={"POST"})
     *
     * @param ServerRequestInterface $request
     *
     * @return HttpResponseInterface
     */
    public function paymentRequestAction(ServerRequestInterface $request): HttpResponseInterface
    {
        $params = \json_decode($request->getBody()->getContents(), true);

        $paymentMode = null;

        if (!empty($params['paymentMode'])) {
            $paymentMode = new PaymentMode($params['paymentMode']);
        }

        if (empty($params['contract']) || empty($params['amount'])) {
            throw new BadRequestHttpException('Not enough information.');
        }

        try {
            $customer = $this->authHelper->getCustomerAccount();

            if (null === $customer) {
                throw new BadRequestHttpException('Not authorized.');
            }

            $contract = $this->iriConverter->getItemFromIri($params['contract']);

            // build payment
            $paymentData = [
                'amount' => $params['amount'],
                'status' => PaymentStatus::PENDING,
            ];

            $payment = $this->serializer->deserialize(\json_encode($paymentData), Payment::class, 'jsonld', ['payment_write']);
            $this->entityManager->persist($payment);

            if ($payment instanceof Payment && $contract instanceof Contract) {
                $this->entityManager->getConnection()->beginTransaction();
                $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                $contract->addPayment($payment);
                $this->entityManager->persist($contract);
                $this->commandBus->handle(new UpdatePaymentNumber($payment));

                $payment->setPaymentUrl($this->paymentGatewayClient->getPaymentUrl($payment, $contract, $paymentMode));
                $this->entityManager->flush();
                $this->entityManager->getConnection()->commit();

                return new JsonResponse(['url' => $payment->getPaymentUrl()]);
            }

            throw new BadRequestHttpException('Unknown error occured.');
        } catch (BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], $e->getStatusCode());
        }

        return new EmptyResponse(400);
    }
}
