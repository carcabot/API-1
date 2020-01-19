<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\WebService\Billing\Controller\ContractArrearsHistoryController;

/**
 * The contract arrears history.
 *
 * @ApiResource(iri="ContractArrearsHistory", attributes={
 *     "normalization_context"={"groups"={"contract_arrears_history_read"}},
 * },
 * collectionOperations={
 *     "get",
 *     "get_arrears_history"={
 *         "method"="GET",
 *         "path"="/contracts/{id}/arrears_histories.{_format}",
 *         "controller"=ContractArrearsHistoryController::class,
 *         "normalization_context"={"groups"={"contract_arrears_history_read"}},
 *     },
 * },
 * itemOperations={"get"})
 */
class ContractArrearsHistory
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var \DateTime|null The date.
     */
    protected $date;

    /**
     * @var string|null The textual content of this CreativeWork.
     */
    protected $text;

    public function __construct(?\DateTime $date, ?string $text)
    {
        $this->id = \uniqid();
        $this->date = $date;
        $this->text = $text;
    }

    /**
     * Gets id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Gets text.
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Gets date.
     *
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }
}
