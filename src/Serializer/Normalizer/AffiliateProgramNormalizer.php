<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use App\Entity\AffiliateProgram;
use App\Entity\CustomerAccount;
use App\Entity\User;
use App\Model\AffiliateProgramURLGenerator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class AffiliateProgramNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait {
        setSerializer as baseSetSerializer;
    }

    /**
     * @var AffiliateProgramURLGenerator
     */
    private $affiliateProgramURLGenerator;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @param AffiliateProgramURLGenerator  $affiliateProgramURLGenerator
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param NormalizerInterface           $decorated
     */
    public function __construct(AffiliateProgramURLGenerator $affiliateProgramURLGenerator, AuthorizationCheckerInterface $authorizationChecker, TokenStorageInterface $tokenStorage, NormalizerInterface $decorated)
    {
        $this->affiliateProgramURLGenerator = $affiliateProgramURLGenerator;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof AffiliateProgram) {
            return $this->decorated->normalize($object, $format, $context);
        }

        /** @var AffiliateProgram $affiliateProgram */
        $affiliateProgram = $object;

        $data = $this->decorated->normalize($object, $format, $context);

        if (!\is_array($data)) {
            return $data;
        }

        $customerAccount = $this->getCustomerAccount();

        if (\in_array('affiliate_program_read', $context['groups'], true) && null !== $customerAccount) {
            $data['trackingUrl'] = $this->affiliateProgramURLGenerator->generate($affiliateProgram, $customerAccount);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
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
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->baseSetSerializer($serializer);

        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }

    /**
     * Determines whether the tracking url should be generated.
     *
     * @return CustomerAccount|null
     */
    private function getCustomerAccount(): ?CustomerAccount
    {
        if (!$this->authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return null;
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return null;
        }

        $authenticatedUser = $token->getUser();

        if (!$authenticatedUser instanceof User) {
            return null;
        }

        return $authenticatedUser->getCustomerAccount();
    }
}
