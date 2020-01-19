<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\QuantitativeValue;
use App\Entity\Ticket;
use App\Enum\TimeType;
use App\Model\ServiceLevelAgreementTimerCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class TicketNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ServiceLevelAgreementTimerCalculator
     */
    private $serviceLevelAgreementTimerCalculator;

    /**
     * @param NormalizerInterface                  $decorated
     * @param EntityManagerInterface               $entityManager
     * @param ServiceLevelAgreementTimerCalculator $serviceLevelAgreementTimerCalculator
     */
    public function __construct(NormalizerInterface $decorated, EntityManagerInterface $entityManager, ServiceLevelAgreementTimerCalculator $serviceLevelAgreementTimerCalculator)
    {
        $this->entityManager = $entityManager;
        $this->decorated = $decorated;
        $this->serviceLevelAgreementTimerCalculator = $serviceLevelAgreementTimerCalculator;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof Ticket) {
            return $this->decorated->normalize($object, $format, $context);
        }

        /**
         * @var Ticket
         */
        $ticket = $object;

        $data = $this->decorated->normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
        }

        if (null === $ticket->getPaused() && null === $ticket->getTimeLeft()->getValue() && null === $ticket->getTimer()->getValue()) {
            $timer = $this->serviceLevelAgreementTimerCalculator->calculate($ticket);

            $data['timer'] = new QuantitativeValue(null !== $timer['timer'] ? (string) $timer['timer'] : null, null, null, TimeType::MIN);
            $data['timeLeft'] = new QuantitativeValue(null !== $timer['timeLeft'] ? (string) \round($timer['timeLeft'], 2) : null, null, null, TimeType::MIN);
            $data['paused'] = $timer['paused'];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->baseSetSerializer($serializer);

        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
