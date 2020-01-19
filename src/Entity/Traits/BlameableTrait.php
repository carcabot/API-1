<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use ApiPlatform\Core\Annotation\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait BlameableTrait
{
    /**
     * @var App\Entity\User|null The direct performer or driver of the action (animate or inanimate). e.g. *John* wrote a book.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Gedmo\Blameable(on="update")
     * @ApiProperty(iri="http://schema.org/agent")
     */
    protected $agent;

    /**
     * @var App\Entity\User|null The creator/author of this CreativeWork. This is the same as the Author property for CreativeWork.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Gedmo\Blameable(on="create")
     * @ApiProperty(iri="http://schema.org/creator")
     */
    protected $creator;

    /**
     * Sets agent.
     *
     * @param ?\App\Entity\User $agent
     */
    public function setAgent(?\App\Entity\User $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Gets agent.
     *
     * @return \App\Entity\User|null
     */
    public function getAgent(): ?\App\Entity\User
    {
        return $this->agent;
    }

    /**
     * Sets creator.
     *
     * @param ?\App\Entity\User $creator
     */
    public function setCreator(?\App\Entity\User $creator)
    {
        $this->creator = $creator;
    }

    /**
     * Gets creator.
     *
     * @return \App\Entity\User|null
     */
    public function getCreator(): ?\App\Entity\User
    {
        return $this->creator;
    }
}
