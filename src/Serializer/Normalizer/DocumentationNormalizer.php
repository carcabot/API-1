<?php

declare(strict_types=1);

namespace App\Serializer\Normalizer;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class DocumentationNormalizer implements NormalizerInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var NormalizerInterface
     */
    private $decorated;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param NormalizerInterface           $decorated
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, NormalizerInterface $decorated)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->decorated = $decorated;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);

        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $classes = [
                'ContactPoint',
                'Corporation',
                'CustomerAccountPostalAddress',
                'CustomerAccount',
                'Identification',
                'Lead',
                'Person',
                'PostalAddress',
            ];

            $supportedClasses = [];
            foreach ($docs['hydra:supportedClass'] as $key => $supportedClass) {
                if (\in_array($supportedClass['hydra:title'], $classes, true)) {
                    $supportedOperations = [];
                    foreach ($supportedClass['hydra:supportedOperation'] as $operation) {
                        if ('GET' === $operation['hydra:method']) {
                            $supportedOperations[] = $operation;
                            break;
                        }
                    }
                    $supportedClass['hydra:supportedOperation'] = $supportedOperations;
                    $supportedClasses[] = $supportedClass;
                }
            }

            $docs['hydra:supportedClass'] = $supportedClasses;
        }

        return $docs;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
