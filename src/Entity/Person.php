<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\GenderType;
use App\Enum\MaritalStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A person (alive, dead, undead, or fictional).
 *
 * @see http://schema.org/Person
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"keywords"}),
 *     @ORM\Index(columns={"name"}),
 * })
 * @ApiResource(iri="http://schema.org/Person",
 *      attributes={
 *          "normalization_context"={"groups"={"person_read"}},
 *          "denormalization_context"={"groups"={"person_write", "lead_import_write"}},
 *      },
 *      collectionOperations={"post"}
 * )
 */
class Person
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
     * @var string|null An additional name for a Person, can be used for a middle name.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/additionalName")
     */
    protected $additionalName;

    /**
     * @var string|null An alias for the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/alternateName")
     */
    protected $alternateName;

    /**
     * @var \DateTime|null Date of birth.
     *
     * @ORM\Column(type="date", nullable=true)
     * @ApiProperty(iri="http://schema.org/birthDate")
     */
    protected $birthDate;

    /**
     * @var string|null The place where the person was born.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/birthPlace")
     */
    protected $birthPlace;

    /**
     * @var Collection<ContactPoint> A contact point for a person or organization.
     *
     * @ORM\ManyToMany(targetEntity="ContactPoint", cascade={"persist", "refresh"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="person_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="contact_point_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="DESC"})
     * @ApiProperty(iri="http://schema.org/contactPoints")
     * @ApiSubresource()
     */
    protected $contactPoints;

    /**
     * @var string|null The origin country of the person.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $countryOfOrigin;

    /**
     * @var \DateTime|null Date of death.
     *
     * @ORM\Column(type="date", nullable=true)
     * @ApiProperty(iri="http://schema.org/deathDate")
     */
    protected $deathDate;

    /**
     * @var string|null The place where the person died.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/deathPlace")
     */
    protected $deathPlace;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var string|null Family name. In the U.S., the last name of an Person. This can be used along with givenName instead of the name property.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/familyName")
     */
    protected $familyName;

    /**
     * @var GenderType|null Gender of the person. While http://schema.org/Male and http://schema.org/Female may be used, text strings are also acceptable for people who do not identify as a binary gender.
     *
     * @ORM\Column(type="gender_type_enum", nullable=true)
     * @ApiProperty(iri="http://schema.org/gender")
     */
    protected $gender;

    /**
     * @var string|null Given name. In the U.S., the first name of a Person. This can be used along with familyName instead of the name property.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/givenName")
     */
    protected $givenName;

    /**
     * @var string|null An honorific prefix preceding a Person's name such as Dr/Mrs/Mr.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/honorificPrefix")
     */
    protected $honorificPrefix;

    /**
     * @var Collection<Identification> The identifier property represents any kind of identifier for any kind of Thing, such as ISBNs, GTIN codes, UUIDs etc. Schema.org provides dedicated properties for representing many of these, either as textual strings or as URL (URI) links. See background notes for more details.
     *
     * @ORM\ManyToMany(targetEntity="Identification", cascade={"persist", "refresh"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="person_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="identification_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="DESC"})
     * @ApiProperty(iri="http://schema.org/identifier")
     * @ApiSubresource()
     */
    protected $identifiers;

    /**
     * @var string|null The job title of the person (for example, Financial Manager).
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/jobTitle")
     */
    protected $jobTitle;

    /**
     * @var string|null
     *
     * @ORM\Column(type="tsvector", nullable=true, options={
     *     "tsvector_fields"={
     *         "name"={
     *             "config"="english",
     *             "weight"="A",
     *         },
     *     },
     * })
     */
    protected $keywords;

    /**
     * @var string[] Of a Person, and less typically of an Organization, to indicate a known language. We do not distinguish skill levels or reading/writing/speaking/signing here. Use language codes from the IETF BCP 47 standard.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://pending.schema.org/knowsLanguage")
     */
    protected $knowsLanguages;

    /**
     * @var MaritalStatus|null The relationship with a significant other.
     *
     * @ORM\Column(type="marital_status_enum", nullable=true)
     * @ApiProperty()
     */
    protected $maritalStatus;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string|null Nationality of the person.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/nationality")
     */
    protected $nationality;

    /**
     * @var string|null A language used with the customer. Please use one of the language codes from the [IETF BCP 47 standard](http://tools.ietf.org/html/bcp47). Supersedes [language](http://schema.org/language).
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty()
     */
    protected $preferredLanguage;

    /**
     * @var string[] URL of a reference Web page that unambiguously indicates the item's identity. E.g. the URL of the item's Wikipedia page, Freebase page, or official website.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/sameAs")
     */
    protected $sameAsUrls;

    public function __construct()
    {
        $this->contactPoints = new ArrayCollection();
        $this->identifiers = new ArrayCollection();
        $this->knowsLanguages = [];
        $this->sameAsUrls = [];
    }

    public function __clone()
    {
        if (null !== $this->id) {
            $this->id = null;

            $contactPoints = new ArrayCollection();
            foreach ($this->contactPoints as $contactPoint) {
                $contactPoints[] = clone $contactPoint;
            }
            $this->contactPoints = $contactPoints;

            $identifiers = new ArrayCollection();
            foreach ($this->identifiers as $identifier) {
                $identifiers[] = clone $identifier;
            }
            $this->identifiers = $identifiers;
        }
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
     * Sets additionalName.
     *
     * @param string|null $additionalName
     *
     * @return $this
     */
    public function setAdditionalName(?string $additionalName)
    {
        $this->additionalName = $additionalName;

        return $this;
    }

    /**
     * Gets additionalName.
     *
     * @return string|null
     */
    public function getAdditionalName(): ?string
    {
        return $this->additionalName;
    }

    /**
     * Sets alternateName.
     *
     * @param string|null $alternateName
     *
     * @return $this
     */
    public function setAlternateName(?string $alternateName)
    {
        $this->alternateName = $alternateName;

        return $this;
    }

    /**
     * Gets alternateName.
     *
     * @return string|null
     */
    public function getAlternateName(): ?string
    {
        return $this->alternateName;
    }

    /**
     * Sets birthDate.
     *
     * @param \DateTime|null $birthDate
     *
     * @return $this
     */
    public function setBirthDate(?\DateTime $birthDate)
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Gets birthDate.
     *
     * @return \DateTime|null
     */
    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    /**
     * Sets birthPlace.
     *
     * @param string|null $birthPlace
     *
     * @return $this
     */
    public function setBirthPlace(?string $birthPlace)
    {
        $this->birthPlace = $birthPlace;

        return $this;
    }

    /**
     * Gets birthPlace.
     *
     * @return string|null
     */
    public function getBirthPlace(): ?string
    {
        return $this->birthPlace;
    }

    /**
     * Adds contactPoint.
     *
     * @param ContactPoint $contactPoint
     *
     * @return $this
     */
    public function addContactPoint(ContactPoint $contactPoint)
    {
        $this->contactPoints[] = $contactPoint;

        return $this;
    }

    /**
     * Removes contactPoint.
     *
     * @param ContactPoint $contactPoint
     *
     * @return $this
     */
    public function removeContactPoint(ContactPoint $contactPoint)
    {
        $this->contactPoints->removeElement($contactPoint);

        return $this;
    }

    /**
     * Gets contactPoints.
     *
     * @return ContactPoint[]
     */
    public function getContactPoints(): array
    {
        return $this->contactPoints->getValues();
    }

    /**
     * Sets countryOfOrigin.
     *
     * @param string|null $countryOfOrigin
     *
     * @return $this
     */
    public function setCountryOfOrigin(?string $countryOfOrigin)
    {
        $this->countryOfOrigin = $countryOfOrigin;

        return $this;
    }

    /**
     * Gets countryOfOrigin.
     *
     * @return string|null
     */
    public function getCountryOfOrigin(): ?string
    {
        return $this->countryOfOrigin;
    }

    /**
     * Sets deathDate.
     *
     * @param \DateTime|null $deathDate
     *
     * @return $this
     */
    public function setDeathDate(?\DateTime $deathDate)
    {
        $this->deathDate = $deathDate;

        return $this;
    }

    /**
     * Gets deathDate.
     *
     * @return \DateTime|null
     */
    public function getDeathDate(): ?\DateTime
    {
        return $this->deathDate;
    }

    /**
     * Sets deathPlace.
     *
     * @param string|null $deathPlace
     *
     * @return $this
     */
    public function setDeathPlace(?string $deathPlace)
    {
        $this->deathPlace = $deathPlace;

        return $this;
    }

    /**
     * Gets deathPlace.
     *
     * @return string|null
     */
    public function getDeathPlace(): ?string
    {
        return $this->deathPlace;
    }

    /**
     * Sets description.
     *
     * @param string|null $description
     *
     * @return $this
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets familyName.
     *
     * @param string|null $familyName
     *
     * @return $this
     */
    public function setFamilyName(?string $familyName)
    {
        $this->familyName = $familyName;

        return $this;
    }

    /**
     * Gets familyName.
     *
     * @return string|null
     */
    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    /**
     * Sets gender.
     *
     * @param GenderType|null $gender
     *
     * @return $this
     */
    public function setGender(?GenderType $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Gets gender.
     *
     * @return GenderType|null
     */
    public function getGender(): ?GenderType
    {
        return $this->gender;
    }

    /**
     * Sets givenName.
     *
     * @param string|null $givenName
     *
     * @return $this
     */
    public function setGivenName(?string $givenName)
    {
        $this->givenName = $givenName;

        return $this;
    }

    /**
     * Gets givenName.
     *
     * @return string|null
     */
    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    /**
     * Sets honorificPrefix.
     *
     * @param string|null $honorificPrefix
     *
     * @return $this
     */
    public function setHonorificPrefix(?string $honorificPrefix)
    {
        $this->honorificPrefix = $honorificPrefix;

        return $this;
    }

    /**
     * Gets honorificPrefix.
     *
     * @return string|null
     */
    public function getHonorificPrefix(): ?string
    {
        return $this->honorificPrefix;
    }

    /**
     * Adds identifier.
     *
     * @param Identification $identifier
     *
     * @return $this
     */
    public function addIdentifier(Identification $identifier)
    {
        $this->identifiers[] = $identifier;

        return $this;
    }

    /**
     * Removes identifier.
     *
     * @param Identification $identifier
     *
     * @return $this
     */
    public function removeIdentifier(Identification $identifier)
    {
        $this->identifiers->removeElement($identifier);

        return $this;
    }

    /**
     * Gets identifiers.
     *
     * @return Identification[]
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers->getValues();
    }

    /**
     * Sets jobTitle.
     *
     * @param string|null $jobTitle
     *
     * @return $this
     */
    public function setJobTitle(?string $jobTitle)
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    /**
     * Gets jobTitle.
     *
     * @return string|null
     */
    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    /**
     * Adds knowsLanguage.
     *
     * @param string $knowsLanguage
     *
     * @return $this
     */
    public function addKnowsLanguage(string $knowsLanguage)
    {
        $this->knowsLanguages[] = $knowsLanguage;

        return $this;
    }

    /**
     * Removes knowsLanguage.
     *
     * @param string $knowsLanguage
     *
     * @return $this
     */
    public function removeKnowsLanguage(string $knowsLanguage)
    {
        if (false !== ($key = \array_search($knowsLanguage, $this->knowsLanguages, true))) {
            \array_splice($this->knowsLanguages, $key, 1);
        }

        return $this;
    }

    /**
     * Gets knowsLanguages.
     *
     * @return string[]
     */
    public function getKnowsLanguages(): array
    {
        return $this->knowsLanguages;
    }

    /**
     * Sets maritalStatus.
     *
     * @param MaritalStatus|null $maritalStatus
     *
     * @return $this
     */
    public function setMaritalStatus(?MaritalStatus $maritalStatus)
    {
        $this->maritalStatus = $maritalStatus;

        return $this;
    }

    /**
     * Gets maritalStatus.
     *
     * @return MaritalStatus|null
     */
    public function getMaritalStatus(): ?MaritalStatus
    {
        return $this->maritalStatus;
    }

    /**
     * Sets name.
     *
     * @param string|null $name
     *
     * @return $this
     */
    public function setName(?string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Sets nationality.
     *
     * @param string|null $nationality
     *
     * @return $this
     */
    public function setNationality(?string $nationality)
    {
        $this->nationality = $nationality;

        return $this;
    }

    /**
     * Gets nationality.
     *
     * @return string|null
     */
    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    /**
     * Sets preferredLanguage.
     *
     * @param string|null $preferredLanguage
     *
     * @return $this
     */
    public function setPreferredLanguage(?string $preferredLanguage)
    {
        $this->preferredLanguage = $preferredLanguage;

        return $this;
    }

    /**
     * Gets preferredLanguage.
     *
     * @return string|null
     */
    public function getPreferredLanguage(): ?string
    {
        return $this->preferredLanguage;
    }

    /**
     * Adds sameAsUrl.
     *
     * @param string $sameAsUrl
     *
     * @return $this
     */
    public function addSameAsUrl(string $sameAsUrl)
    {
        $this->sameAsUrls[] = $sameAsUrl;

        return $this;
    }

    /**
     * Removes sameAsUrl.
     *
     * @param string $sameAsUrl
     *
     * @return $this
     */
    public function removeSameAsUrl(string $sameAsUrl)
    {
        if (false !== ($key = \array_search($sameAsUrl, $this->sameAsUrls, true))) {
            \array_splice($this->sameAsUrls, $key, 1);
        }

        return $this;
    }

    /**
     * Gets sameAsUrls.
     *
     * @return string[]
     */
    public function getSameAsUrls(): array
    {
        return $this->sameAsUrls;
    }
}
