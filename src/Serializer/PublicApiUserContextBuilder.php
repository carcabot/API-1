<?php

declare(strict_types=1);

namespace App\Serializer;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use App\Enum\AuthorizationRole;
use App\Service\AuthenticationHelper;
use Symfony\Component\HttpFoundation\Request;

final class PublicApiUserContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $authenticationHelper;

    public function __construct(SerializerContextBuilderInterface $decorated, AuthenticationHelper $authenticationHelper)
    {
        $this->decorated = $decorated;
        $this->authenticationHelper = $authenticationHelper;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);

        if (isset($context['groups']) && $this->authenticationHelper->hasRole(AuthorizationRole::ROLE_PUBLIC_API) && true === $normalization) {
            $context['groups'] = 'public_read';
        }

        return $context;
    }
}
