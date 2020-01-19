<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\URLStatus;
use Doctrine\ORM\Mapping as ORM;

/**
 * An affiliate program url for a customer account.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"customer_account_affiliate_program_url_read"}},
 *     "denormalization_context"={"groups"={"customer_account_affiliate_program_url_write"}},
 * })
 */
class CustomerAccountAffiliateProgramUrl
{
    use Traits\TimestampableTrait;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AffiliateProgram An affiliate program.
     *
     * @ORM\ManyToOne(targetEntity="AffiliateProgram")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $affiliateProgram;

    /**
     * @var CustomerAccount Basic unit of information about a customer.
     *
     * @ORM\ManyToOne(targetEntity="CustomerAccount")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $customer;

    /**
     * @var URLStatus The status of the URL.
     *
     * @ORM\Column(type="url_status_enum", nullable=false)
     * @ApiProperty()
     */
    protected $status;

    /**
     * @var string The tracking URL.
     *
     * @ORM\Column(type="text", nullable=false)
     * @ApiProperty(iri="http://schema.org/url")
     */
    protected $url;

    /**
     * Gets id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets affiliateProgram.
     *
     * @param AffiliateProgram $affiliateProgram
     *
     * @return $this
     */
    public function setAffiliateProgram(AffiliateProgram $affiliateProgram)
    {
        $this->affiliateProgram = $affiliateProgram;

        return $this;
    }

    /**
     * Gets affiliateProgram.
     *
     * @return AffiliateProgram
     */
    public function getAffiliateProgram(): AffiliateProgram
    {
        return $this->affiliateProgram;
    }

    /**
     * Sets customer.
     *
     * @param CustomerAccount $customer
     *
     * @return $this
     */
    public function setCustomer(CustomerAccount $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets customer.
     *
     * @return CustomerAccount
     */
    public function getCustomer(): CustomerAccount
    {
        return $this->customer;
    }

    /**
     * Sets status.
     *
     * @param URLStatus $status
     *
     * @return $this
     */
    public function setStatus(URLStatus $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Gets status.
     *
     * @return URLStatus
     */
    public function getStatus(): URLStatus
    {
        return $this->status;
    }

    /**
     * Sets url.
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Gets url.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
