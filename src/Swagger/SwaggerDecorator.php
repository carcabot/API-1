<?php

declare(strict_types=1);

namespace App\Swagger;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SwaggerDecorator implements NormalizerInterface
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
            $patternPaths = '/^\/(corporations|customer_accounts|customer_account_postal_addresses|leads|postal_addresses|people|identifications|contact_points)(\/\{id\})?$/';

            $unsetPaths = [];
            foreach ($docs['paths'] as $path => $pathParameters) {
                if (0 === \preg_match($patternPaths, $path)) {
                    $unsetPaths[] = $path;
                } elseif (!isset($pathParameters['get'])) {
                    $unsetPaths[] = $path;
                }
            }

            $patternDefinitionStart = '/^(?!(Promotion|Partner))/';
            $patternDefinitions = '/-(corporation|customer_account|customer_account_postal_address|lead|postal_address|person|identification|contact_point)(_read)?$/';

            $unsetDefinitions = [];
            foreach ($docs['definitions'] as $definition => $definitionParamaters) {
                if (0 === \preg_match($patternDefinitionStart, $definition) || 0 === \preg_match($patternDefinitions, $definition)) {
                    $unsetDefinitions[] = $definition;
                }
            }

            foreach ($unsetPaths as $unset) {
                unset($docs['paths'][$unset]);
            }

            foreach ($unsetDefinitions as $unset) {
                unset($docs['definitions'][$unset]);
            }

            foreach ($docs['paths'] as $path => $pathParameters) {
                $docs['paths'][$path] = ['get' => $pathParameters['get']];
            }
        }

        return $docs;
    }

    public function supportsNormalization($data, $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
