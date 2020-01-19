<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class Reference
{
    /**
     * @var string|null
     *
     * @ODM\Field(type="id", name="id")
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string", name="type")
     */
    protected $type;
}
