<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An url token.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"url_token_read"}},
 *     "denormalization_context"={"groups"={"url_token_write"}},
 * })
 */
class UrlToken
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string Token
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty()
     */
    protected $token;

    /**
     * @var \DateTimeInterface|null The date when the item becomes valid.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validFrom")
     */
    protected $validFrom;

    /**
     * @var \DateTimeInterface|null The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours.
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @ApiProperty(iri="http://schema.org/validThrough")
     */
    protected $validThrough;

    /**
     * Gets id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Gets token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Sets token.
     *
     * @param string $token
     *
     * @return $this
     */
    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Gets valid from.
     *
     * @return \DateTimeInterface|null
     */
    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    /**
     * Sets valid from.
     *
     * @param \DateTimeInterface|null $validFrom
     *
     * @return $this
     */
    public function setValidFrom(?\DateTimeInterface $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Gets valid through.
     *
     * @return \DateTimeInterface|null
     */
    public function getValidThrough(): ?\DateTimeInterface
    {
        return $this->validThrough;
    }

    /**
     * Sets valid through.
     *
     * @param \DateTimeInterface|null $validThrough
     *
     * @return $this
     */
    public function setValidThrough(?\DateTimeInterface $validThrough)
    {
        $this->validThrough = $validThrough;

        return $this;
    }
}
