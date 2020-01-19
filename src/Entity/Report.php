<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Controller\ApplicationRequestReportController;
use App\Controller\CampaignReportController;
use App\Controller\ContractReportController;
use App\Controller\CreditsActionReportController;
use App\Controller\CustomerAccountRelationshipReportController;
use App\Controller\CustomerAccountReportController;
use App\Controller\LeadReportController;
use App\Controller\OrderReportController;
use App\Controller\TicketReportController;
use App\Controller\UserReportController;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * The report resource.
 *
 * @ApiResource(iri="Report", attributes={
 *     "normalization_context"={"groups"={"report_read"}},
 * },
 * collectionOperations={
 *     "generate_lead_report"={
 *         "method"="POST",
 *         "path"="/reports/lead.{_format}",
 *         "controller"=LeadReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_application_request_report"={
 *         "method"="POST",
 *         "path"="/reports/application_request.{_format}",
 *         "controller"=ApplicationRequestReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_campaign_report"={
 *         "method"="POST",
 *         "path"="/reports/campaign.{_format}",
 *         "controller"=CampaignReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_contract_report"={
 *         "method"="POST",
 *         "path"="/reports/contract.{_format}",
 *         "controller"=ContractReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_credits_action_report"={
 *         "method"="POST",
 *         "path"="/reports/credits_action.{_format}",
 *         "controller"=CreditsActionReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_customer_account_relationship_report"={
 *         "method"="POST",
 *         "path"="/reports/customer_account_relationship.{_format}",
 *         "controller"=CustomerAccountRelationshipReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_customer_account_report"={
 *         "method"="POST",
 *         "path"="/reports/customer_account.{_format}",
 *         "controller"=CustomerAccountReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_order_report"={
 *         "method"="POST",
 *         "path"="/reports/order.{_format}",
 *         "controller"=OrderReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_ticket_report"={
 *         "method"="POST",
 *         "path"="/reports/ticket.{_format}",
 *         "controller"=TicketReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 *     "generate_user_report"={
 *         "method"="POST",
 *         "path"="/reports/user.{_format}",
 *         "controller"=UserReportController::class,
 *         "defaults"={"_api_receive"=false},
 *         "normalization_context"={"groups"={"report_read"}},
 *     },
 * })
 */
class Report
{
    /**
     * @ApiProperty(identifier=true)
     */
    protected $id;

    /**
     * @var Collection<InternalDocument> The reports.
     */
    protected $documents;

    public function __construct(array $reports)
    {
        $this->id = \uniqid();
        $this->documents = new ArrayCollection();

        foreach ($reports as $report) {
            $this->addDocument($report);
        }
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
     * Adds document.
     *
     * @param InternalDocument $document
     *
     * @return $this
     */
    public function addDocument(InternalDocument $document)
    {
        $this->documents[] = $document;

        return $this;
    }

    /**
     * Gets documents.
     *
     * @return InternalDocument[]
     */
    public function getDocuments(): array
    {
        return $this->documents->getValues();
    }
}
