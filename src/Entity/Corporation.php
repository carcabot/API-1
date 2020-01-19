<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiSubresource;
use App\Enum\Industry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Organization: A business corporation.
 *
 * @see http://schema.org/Corporation
 *
 * @ORM\Entity
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"keywords"}),
 *     @ORM\Index(columns={"name"}),
 * })
 * @ApiResource(iri="http://schema.org/Corporation",
 *      attributes={
 *          "normalization_context"={"groups"={"corporation_read"}},
 *          "denormalization_context"={"groups"={"corporation_write", "lead_import_write"}},
 *      },
 *      collectionOperations={"post"}
 * )
 */
class Corporation
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
     * @var Collection<ContactPoint> A contact point for a person or organization.
     *
     * @ORM\ManyToMany(targetEntity="ContactPoint", cascade={"persist", "refresh"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="corporation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="contact_point_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="DESC"})
     * @ApiProperty(iri="http://schema.org/contactPoints")
     * @ApiSubresource()
     */
    protected $contactPoints;

    /**
     * @var string|null A description of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/description")
     */
    protected $description;

    /**
     * @var \DateTime|null The date that this organization was dissolved.
     *
     * @ORM\Column(type="date", nullable=true)
     * @ApiProperty(iri="http://schema.org/dissolutionDate")
     */
    protected $dissolutionDate;

    /**
     * @var Collection<EmployeeRole> Basic unit of information about a customer.
     *
     * @ORM\ManyToMany(targetEntity="EmployeeRole", cascade={"persist"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="corporation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="employee_role_id", onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="ASC"})
     * @ApiProperty()
     * @ApiSubresource()
     */
    protected $employees;

    /**
     * @var \DateTime|null The date that this organization was founded.
     *
     * @ORM\Column(type="date", nullable=true)
     * @ApiProperty(iri="http://schema.org/foundingDate")
     */
    protected $foundingDate;

    /**
     * @var Collection<Identification> The identifier property represents any kind of identifier for any kind of Thing, such as ISBNs, GTIN codes, UUIDs etc. Schema.org provides dedicated properties for representing many of these, either as textual strings or as URL (URI) links. See background notes for more details.
     *
     * @ORM\ManyToMany(targetEntity="Identification", cascade={"persist", "refresh"})
     * @ORM\JoinTable(
     *     joinColumns={@ORM\JoinColumn(name="corporation_id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="identification_id", unique=true, onDelete="CASCADE")}
     * )
     * @ORM\OrderBy({"id"="DESC"})
     * @ApiProperty(iri="http://schema.org/identifier")
     * @ApiSubresource()
     */
    protected $identifiers;

    /**
     * @var Industry|null The industry associated with the job position.
     *
     * @ORM\Column(type="industry_enum", nullable=true)
     * @ApiProperty(iri="http://schema.org/industry")
     */
    protected $industry;

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
     * @var string|null The official name of the organization, e.g. the registered company name.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/legalName")
     */
    protected $legalName;

    /**
     * @var string|null The name of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/name")
     */
    protected $name;

    /**
     * @var string[] URL of a reference Web page that unambiguously indicates the item's identity. E.g. the URL of the item's Wikipedia page, Freebase page, or official website.
     *
     * @ORM\Column(type="json", nullable=false, options={"jsonb"=true})
     * @ApiProperty(iri="http://schema.org/sameAs")
     */
    protected $sameAsUrls;

    /**
     * @var string|null URL of the item.
     *
     * @ORM\Column(type="text", nullable=true)
     * @ApiProperty(iri="http://schema.org/url")
     */
    protected $url;

    public function __construct()
    {
        $this->contactPoints = new ArrayCollection();
        $this->employees = new ArrayCollection();
        $this->identifiers = new ArrayCollection();
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

            $employees = new ArrayCollection();
            foreach ($this->employees as $employee) {
                $employees[] = clone $employee;
            }
            $this->employees = $employees;

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
     * Sets dissolutionDate.
     *
     * @param \DateTime|null $dissolutionDate
     *
     * @return $this
     */
    public function setDissolutionDate(?\DateTime $dissolutionDate)
    {
        $this->dissolutionDate = $dissolutionDate;

        return $this;
    }

    /**
     * Gets dissolutionDate.
     *
     * @return \DateTime|null
     */
    public function getDissolutionDate(): ?\DateTime
    {
        return $this->dissolutionDate;
    }

    /**
     * Adds employee.
     *
     * @param EmployeeRole $employee
     *
     * @return $this
     */
    public function addEmployee(EmployeeRole $employee)
    {
        $this->employees[] = $employee;

        return $this;
    }

    /**
     * Removes employee.
     *
     * @param EmployeeRole $employee
     *
     * @return $this
     */
    public function removeEmployee(EmployeeRole $employee)
    {
        $this->employees->removeElement($employee);

        return $this;
    }

    /**
     * Gets employees.
     *
     * @return EmployeeRole[]
     */
    public function getEmployees(): array
    {
        return $this->employees->getValues();
    }

    /**
     * Sets foundingDate.
     *
     * @param \DateTime|null $foundingDate
     *
     * @return $this
     */
    public function setFoundingDate(?\DateTime $foundingDate)
    {
        $this->foundingDate = $foundingDate;

        return $this;
    }

    /**
     * Gets foundingDate.
     *
     * @return \DateTime|null
     */
    public function getFoundingDate(): ?\DateTime
    {
        return $this->foundingDate;
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
     * Sets industry.
     *
     * @param Industry|null $industry
     *
     * @return $this
     */
    public function setIndustry(?Industry $industry)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * Gets industry.
     *
     * @return Industry|null
     */
    public function getIndustry(): ?Industry
    {
        return $this->industry;
    }

    /**
     * Sets legalName.
     *
     * @param string|null $legalName
     *
     * @return $this
     */
    public function setLegalName(?string $legalName)
    {
        $this->legalName = $legalName;

        return $this;
    }

    /**
     * Gets legalName.
     *
     * @return string|null
     */
    public function getLegalName(): ?string
    {
        return $this->legalName;
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

    /**
     * Sets url.
     *
     * @param string|null $url
     *
     * @return $this
     */
    public function setUrl(?string $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Gets url.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
