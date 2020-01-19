<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Enum\CommissionStatementDataType;
use Doctrine\ORM\Mapping as ORM;

/**
 * A commission statement datum for a commission statement.
 *
 * @ORM\Entity
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"partner_commission_statement_data_read"}},
 *     "denormalization_context"={"groups"={"partner_commission_statement_data_write"}},
 * })
 */
class PartnerCommissionStatementData
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var MonetaryAmount The commission amount calculated.
     *
     * @ORM\Embedded(class="MonetaryAmount")
     * @ApiProperty()
     */
    protected $amount;

    /**
     * @var ApplicationRequest|null.
     *
     * @ORM\OneToOne(targetEntity="ApplicationRequest", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $applicationRequest;

    /**
     * @var Collection<CommissionRate> The commission rate that applies to the partner.
     *
     * @ORM\ManyToOne(targetEntity="CommissionRate", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $commissionRate;

    /**
     * @var Lead|null.
     *
     * @ORM\OneToOne(targetEntity="Lead", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @ApiProperty()
     */
    protected $lead;

    /**
     * @var PartnerCommissionStatement The statement that the data is for.
     *
     * @ORM\ManyToOne(targetEntity="PartnerCommissionStatement", inversedBy="data")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty()
     */
    protected $statement;

    /**
     * @var CommissionStatementDataType The type of data.
     *
     * @ORM\Column(type="commission_statement_data_type_enum", nullable=false)
     * @ApiProperty()
     */
    protected $type;

    public function __construct()
    {
        $this->amount = new MonetaryAmount();
    }

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
     * Sets amount.
     *
     * @param MonetaryAmount $amount
     *
     * @return $this
     */
    public function setAmount(MonetaryAmount $amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Gets amount.
     *
     * @return MonetaryAmount
     */
    public function getAmount(): MonetaryAmount
    {
        return $this->amount;
    }

    /**
     * Sets applicationRequest.
     *
     * @param ApplicationRequest|null $applicationRequest
     *
     * @return $this
     */
    public function setApplicationRequest(?ApplicationRequest $applicationRequest)
    {
        $this->applicationRequest = $applicationRequest;

        return $this;
    }

    /**
     * Gets ApplicationRequest.
     *
     * @return ApplicationRequest|null
     */
    public function getApplicationRequest(): ?ApplicationRequest
    {
        return $this->applicationRequest;
    }

    /**
     * Sets commissionRate.
     *
     * @param CommissionRate|null $commissionRate
     *
     * @return $this
     */
    public function setCommissionRate(?CommissionRate $commissionRate)
    {
        $this->commissionRate = $commissionRate;

        return $this;
    }

    /**
     * Gets commissionRate.
     *
     * @return CommissionRate|null
     */
    public function getCommissionRate(): ?CommissionRate
    {
        return $this->commissionRate;
    }

    /**
     * Sets lead.
     *
     * @param Lead|null $lead
     *
     * @return $this
     */
    public function setLead(?Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Gets lead.
     *
     * @return Lead|null
     */
    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    /**
     * Sets statement.
     *
     * @param PartnerCommissionStatement $statement
     *
     * @return $this
     */
    public function setStatement(PartnerCommissionStatement $statement)
    {
        $this->statement = $statement;

        return $this;
    }

    /**
     * Gets statement.
     *
     * @return PartnerCommissionStatement
     */
    public function getStatement(): PartnerCommissionStatement
    {
        return $this->statement;
    }

    /**
     * Sets type.
     *
     * @param CommissionStatementDataType $type
     *
     * @return $this
     */
    public function setType(CommissionStatementDataType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Gets type.
     *
     * @return CommissionStatementDataType
     */
    public function getType(): CommissionStatementDataType
    {
        return $this->type;
    }
}
