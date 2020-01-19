<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;

/**
 * An unsubscribe list item.
 *
 * @ORM\Entity(repositoryClass="App\Repository\UnsubscribeListItemRepository")
 * @ApiResource(attributes={
 *     "normalization_context"={"groups"={"unsubscribe_list_item_read"}},
 *     "denormalization_context"={"groups"={"unsubscribe_list_item_write"}},
 * })
 */
class UnsubscribeListItem extends ListItem
{
    /**
     * @var Campaign An entity represented by an entry in a list or data feed (e.g. an 'artist' in a list of 'artists')’.
     *
     * @ORM\ManyToOne(targetEntity="Campaign")
     * @ORM\JoinColumn(nullable=false, onDelete="RESTRICT")
     * @ApiProperty(iri="http://schema.org/item")
     */
    protected $item;

    /**
     * @var string Unsubscribed email.
     *
     * @ORM\Column(type="string")
     * @ApiProperty(iri="")
     */
    protected $email;

    /**
     * @var string|null Unsubscribe note.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $note;

    /**
     * @var UnsubscribeReason Reason for unsubscribe.
     *
     * @ORM\OneToOne(targetEntity="UnsubscribeReason")
     * @ApiProperty()
     */
    protected $unsubscribeReason;

    /**
     * Gets item.
     *
     * @return Campaign
     */
    public function getItem(): Campaign
    {
        return $this->item;
    }

    /**
     * Sets item.
     *
     * @param Campaign $item
     */
    public function setItem(Campaign $item): void
    {
        $this->item = $item;
    }

    /**
     * Gets email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets email.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Gets note.
     *
     * @return string|null
     */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /**
     * Sets note.
     *
     * @param string $note
     */
    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    /**
     * Gets UnsubscribeReason.
     *
     * @return UnsubscribeReason
     */
    public function getUnsubscribeReason(): UnsubscribeReason
    {
        return $this->unsubscribeReason;
    }

    /**
     * Sets UnsubscribeReason.
     *
     * @param UnsubscribeReason $unsubscribeReason
     */
    public function setUnsubscribeReason(UnsubscribeReason $unsubscribeReason): void
    {
        $this->unsubscribeReason = $unsubscribeReason;
    }
}
