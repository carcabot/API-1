<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * The expiration for credits of the contract.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"expire_contract_credits_action_read"}},
 *     "denormalization_context"={"groups"={"expire_contract_credits_action_write"}}
 * })
 */
class ExpireContractCreditsAction extends UpdateCreditsAction implements CreditsExpirationInterface
{
    /**
     * @var string|null The amount of credits used from the expiring credits.
     *
     * @ORM\Column(type="decimal", precision=19, scale=4, nullable=true)
     * @ApiProperty()
     */
    protected $amountUsed;

    /**
     * @var Contract The object upon which the action is carried out, whose state is kept intact or changed. Also known as the semantic roles patient, affected or undergoer (which change their state) or theme (which doesn't). e.g. John read a book.
     *
     * @ORM\ManyToOne(targetEntity="Contract")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/object")
     */
    protected $object;

    /**
     * @var CreditsScheme The credits scheme.
     *
     * @ORM\ManyToOne(targetEntity="CreditsScheme")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $scheme;

    /**
     * @var Collection<ExpireCreditsUseHistory> A record of usage for expiring credits.
     *
     * @ORM\ManyToMany(targetEntity="ExpireCreditsUseHistory", cascade={"persist"}, orphanRemoval=true)
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="expire_contract_credits_action_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="expire_credits_use_history_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $useHistories;

    public function __construct()
    {
        $this->useHistories = new ArrayCollection();
    }

    /**
     * Sets amountUsed.
     *
     * @param string|null $amountUsed
     *
     * @return $this
     */
    public function setAmountUsed(?string $amountUsed)
    {
        $this->amountUsed = $amountUsed;

        return $this;
    }

    /**
     * Gets amountUsed.
     *
     * @return string|null
     */
    public function getAmountUsed(): ?string
    {
        return $this->amountUsed;
    }

    /**
     * Sets object.
     *
     * @param Contract $object
     *
     * @return $this
     */
    public function setObject(Contract $object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Gets object.
     *
     * @return Contract
     */
    public function getObject(): Contract
    {
        return $this->object;
    }

    /**
     * Sets scheme.
     *
     * @param CreditsScheme $scheme
     *
     * @return $this
     */
    public function setScheme(CreditsScheme $scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Gets scheme.
     *
     * @return CreditsScheme
     */
    public function getScheme(): CreditsScheme
    {
        return $this->scheme;
    }

    /**
     * Adds useHistory.
     *
     * @param ExpireCreditsUseHistory $useHistory
     *
     * @return $this
     */
    public function addUseHistory(ExpireCreditsUseHistory $useHistory)
    {
        $this->useHistories[] = $useHistory;

        return $this;
    }

    /**
     * Removes useHistory.
     *
     * @param ExpireCreditsUseHistory $useHistory
     *
     * @return $this
     */
    public function removeUseHistory(ExpireCreditsUseHistory $useHistory)
    {
        $this->useHistories->removeElement($useHistory);

        return $this;
    }

    /**
     * Get useHistories.
     *
     * @return ExpireCreditsUseHistory[]
     */
    public function getUseHistories(): array
    {
        return $this->useHistories->getValues();
    }
}
