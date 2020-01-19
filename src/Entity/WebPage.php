<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * A web page. Every web page is implicitly assumed to be declared to be of type WebPage, so the various properties about that webpage, such as breadcrumb may be used. We recommend explicit declaration if these properties are specified, but if they are found outside of an itemscope, they will be assumed to be about the page.
 *
 * @see http://schema.org/WebPage
 *
 * @ORM\Entity
 * @ApiResource(iri="http://schema.org/WebPage", attributes={
 *     "normalization_context"={"groups"={"web_page_read"}},
 *     "denormalization_context"={"groups"={"web_page_write"}},
 * })
 */
class WebPage extends WebPageBase
{
    /**
     * @var int|null The position of an item in a series or sequence of items.
     *
     * @ORM\Column(type="integer", nullable=true)
     * @ApiProperty(iri="http://schema.org/position")
     */
    protected $position;

    /**
     * @var string|null The return url specified after enrolling for the tariff rate.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $returnUrl;

    /**
     * Sets position.
     *
     * @param int|null $position
     *
     * @return $this
     */
    public function setPosition(?int $position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Gets position.
     *
     * @return int|null
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Sets returnUrl.
     *
     * @param string|null $returnUrl
     *
     * @return $this
     */
    public function setReturnUrl(?string $returnUrl)
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    /**
     * Gets returnUrl.
     *
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }
}
