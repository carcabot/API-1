<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="measurement_configs")
 */
class MeasurementConfig
{
    /**
     * @var string|null
     *
     * @ODM\Id
     */
    protected $id;
    /**
     * @var string|null The billing period number.
     *
     * @ODM\Field(type="string", name="key")
     */
    protected $key;
    /**
     * @var string|null The billing period number.
     *
     * @ODM\Field(type="string", name="name")
     */
    protected $name;

    /**
     * @var bool|null The billing period number.
     *
     * @ODM\Field(type="boolean", name="active")
     */
    protected $active;

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool|null
     */
    public function getActive()
    {
        return $this->active;
    }
}
