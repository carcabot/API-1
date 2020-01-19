<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class Partner
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $_id;

    /**
     * @var string|null The partner agency code.
     *
     * @ODM\Field(type="string", name="id")
     */
    protected $acquirerCode;

    /**
     * @var string|null The partner agency name.
     *
     * @ODM\Field(type="string", name="name")
     */
    protected $acquirerName;

    /**
     * @var string|null The sales representative name.
     *
     * @ODM\Field(type="string", name="sales_rep_name")
     */
    protected $salesRepName;

    /**
     * Gets _id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->_id;
    }

    /**
     * Gets acquirerCode.
     *
     * @return string|null
     */
    public function getAcquirerCode(): ?string
    {
        return $this->acquirerCode;
    }

    /**
     * Gets acquirerName.
     *
     * @return string|null
     */
    public function getAcquirerName(): ?string
    {
        return $this->acquirerName;
    }

    /**
     * Gets salesRepName.
     *
     * @return string|null
     */
    public function getSalesRepName(): ?string
    {
        return $this->salesRepName;
    }
}
