<?php

declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\Domain\Command\Offer\UpdateOfferNumber;
use App\Entity\Merchant;
use App\Entity\MonetaryAmount;
use App\Entity\Offer;
use App\Entity\OfferCategory;
use App\Entity\OfferListItem;
use App\Entity\OfferSerialNumber;
use App\Entity\PriceSpecification;
use App\Entity\QuantitativeValue;
use App\Enum\OfferType;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Tactician\CommandBus;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OfferListItemImporter
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var IriConverterInterface
     */
    private $iriConverter;

    /**
     * @var DenormalizerInterface
     */
    private $denormalizer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var string
     */
    private $documentConverterHost;

    /**
     * @param CommandBus             $commandBus
     * @param EntityManagerInterface $entityManager
     * @param IriConverterInterface  $iriConverter
     * @param DenormalizerInterface  $denormalizer
     * @param ValidatorInterface     $validator
     * @param string                 $documentConverterHost
     */
    public function __construct(CommandBus $commandBus, EntityManagerInterface $entityManager, IriConverterInterface $iriConverter, DenormalizerInterface $denormalizer, ValidatorInterface $validator, string $documentConverterHost)
    {
        $this->commandBus = $commandBus;
        $this->entityManager = $entityManager;
        $this->iriConverter = $iriConverter;
        $this->denormalizer = $denormalizer;
        $this->validator = $validator;
        $this->documentConverterHost = $documentConverterHost;
    }

    /**
     * @param UploadedFileInterface $file
     * @param bool                  $testRun
     *
     * @return array
     */
    public function importFromFile(UploadedFileInterface $file, bool $testRun = true)
    {
        $offerListItemsData = [];
        $offerListItems = [];

        if ($file->getSize() > 0) {
            $client = new GuzzleClient();
            $baseDocumentConverterUri = HttpUri::createFromString($this->documentConverterHost);
            $modifier = new AppendSegment('/offer_list_items/csv');
            $documentConverterUri = $modifier->process($baseDocumentConverterUri);

            $multipartContent = [
                'headers' => [
                    'User-Agent' => 'U-Centric API',
                ],
                'multipart' => [
                    [
                        'name' => 'file',
                        'filename' => \uniqid().'.xml',
                        'contents' => $file->getStream(),
                    ],
                ],
            ];
            $uploadResponse = $client->request('POST', $documentConverterUri, $multipartContent);
            $offerListItemsData = \json_decode((string) $uploadResponse->getBody(), true);

            if (200 === $uploadResponse->getStatusCode()) {
                foreach ($offerListItemsData as $offerListItemsDatum) {
                    try {
                        if (isset($offerListItemsDatum['errors'])) {
                            $offerListItems[] = $offerListItemsDatum;
                            continue;
                        }

                        $offerListItemsDatum = $this->removeEmptyValues($offerListItemsDatum, function ($value) { return empty($value); });
                        $offerListItem = null;
                        $offer = null;

                        if (isset($offerListItemsDatum['offerListItem']['item']['offerNumber'])) {
                            $offer = $this->entityManager->getRepository(Offer::class)->findOneBy(['offerNumber' => $offerListItemsDatum['offerListItem']['offerNumber']]);
                        } else {
                            $offer = new Offer();

                            if (isset($offerListItemsDatum['offerListItem']['item']['seller']['name'])) {
                                $sellerName = $offerListItemsDatum['offerListItem']['item']['seller']['name'];
                                $seller = $this->entityManager->getRepository(Merchant::class)->findOneBy(['name', $sellerName]);

                                if (null !== $seller) {
                                    $offer->setSeller($seller);
                                } else {
                                    throw new \Exception('Partner is not exists');
                                }
                            } else {
                                throw new \Exception('No partner name is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['item']['sku'])) {
                                $offer->setSku($offerListItemsDatum['offerListItem']['item']['sku']);
                            } else {
                                throw new \Exception('No sku is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['item']['name'])) {
                                $offer->setName($offerListItemsDatum['offerListItem']['item']['name']);
                            } else {
                                throw new \Exception('No product name is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['item']['category']['name'])) {
                                $categoryName = $offerListItemsDatum['offerListItem']['item']['category']['name'];
                                $offerCategory = $this->entityManager->getRepository(OfferCategory::class)->findOneBy(['name' => $categoryName]);

                                if (null !== $offerCategory) {
                                    $offer->setCategory($offerCategory);
                                } else {
                                    throw new \Exception('Product category is not exists');
                                }
                            } else {
                                throw new \Exception('No product category is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['item']['type'])) {
                                $offerType = $offerListItemsDatum['offerListItem']['item']['type'];
                                if (OfferType::VOUCHER === $offerType) {
                                    $offer->setType(new OfferType(OfferType::VOUCHER));
                                } elseif (OfferType::BILL_REBATE === $offerType) {
                                    $offer->setType(new OfferType(OfferType::BILL_REBATE));
                                } else {
                                    throw new \Exception('Product type is not exists');
                                }
                            } else {
                                throw new \Exception('No product type is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['item']['validFrom'])) {
                                $offer->setValidFrom(new \DateTime($offerListItemsDatum['offerListItem']['item']['validFrom']));
                            } else {
                                throw new \Exception('No valid from is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['item']['validThrough'])) {
                                $offer->setValidThrough(new \DateTime($offerListItemsDatum['offerListItem']['item']['validThrough']));
                            } else {
                                throw new \Exception('No valid through is set');
                            }

                            $this->entityManager->getConnection()->beginTransaction();
                            $this->entityManager->getConnection()->exec('LOCK TABLE running_numbers IN SHARE ROW EXCLUSIVE MODE;');
                            $this->commandBus->handle(new UpdateOfferNumber($offer));
                            $this->entityManager->persist($offer);
                            $this->entityManager->flush();
                            $this->entityManager->getConnection()->commit();
                        }

                        if (null !== $offer) {
                            $price = null;
                            $amount = null;
                            $validFrom = null;
                            $validThrough = null;

                            if (isset($offerListItemsDatum['offerListItem']['priceSpecification']['price'])) {
                                $price = new PriceSpecification(null, null, $price, null);
                            } else {
                                throw new \Exception('No points is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['monetaryExchangeValue']['value'])) {
                                $amount = new MonetaryAmount($offerListItemsDatum['monetaryExchangeValue']['value'], null);
                            } elseif (OfferType::BILL_REBATE === $offer->getType()->getValue()) {
                                throw new \Exception('No amount is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['validFrom'])) {
                                $validFrom = new \DateTime($offerListItemsDatum['offerListItem']['validFrom']);
                            } else {
                                throw new \Exception('No valid from is set');
                            }

                            if (isset($offerListItemsDatum['offerListItem']['validThrough'])) {
                                $validThrough = new \DateTime($offerListItemsDatum['offerListItem']['validThrough']);
                            } else {
                                throw new \Exception('No valid through is set');
                            }

                            $offerListItem = $this->entityManager->getRepository(OfferListItem::class)->findOneBy(['item' => $offer, 'priceSpecification' => $price, 'monetaryExchangeValue' => $amount, 'validFrom' => $validFrom, 'validThrough' => $validThrough]);

                            if (null === $offerListItem) {
                                $offerListItem = new OfferListItem();

                                $offerListItem->setItem($offer);
                                $offerListItem->setPriceSpecification($price);

                                if (null !== $amount) {
                                    $offerListItem->setMonetaryExchangeValue($amount);
                                }

                                $offerListItem->setValidFrom($validFrom);
                                $offerListItem->setValidThrough($validThrough);
                                $offerListItem->setInventoryLevel(new QuantitativeValue('0', null, null, null));

                                $this->entityManager->persist($offerListItem);
                                $this->entityManager->flush();
                            }

                            if (isset($offerListItemsDatum['offerSerialNumber']['serialNumber'])) {
                                $serialNumber = $offerListItemsDatum['offerSerialNumber']['serialNumber'];

                                $offerSerialNumber = new OfferSerialNumber();
                                $offerSerialNumber->setSerialNumber($serialNumber);
                                $offerSerialNumber->setOfferListItem($offerListItem);

                                if (isset($offerListItemsDatum['offerSerialNumber']['expires'])) {
                                    $offerSerialNumber->setExpires(new \DateTime($offerListItemsDatum['offerSerialNumber']['expires']));
                                }

                                $offerListItem->setInventoryLevel(new QuantitativeValue((string) ((int) $offerListItem->getInventoryLevel()->getValue() + 1), null, null, null));

                                $this->entityManager->persist($offerSerialNumber);
                                $this->entityManager->persist($offerListItem);

                                $this->entityManager->flush();
                            } elseif (OfferType::VOUCHER === $offer->getType()->getValue()) {
                                throw new \Exception('Serial number should be provided');
                            }
                        } else {
                            throw new \Exception('Product is not exists');
                        }
                    } catch (\Exception $ex) {
                    }
                }
            }
        }

        return $offerListItems;
    }

    private function removeEmptyValues(array $array, callable $callback)
    {
        foreach ($array as $k => $v) {
            if (\is_array($v)) {
                $array[$k] = $this->removeEmptyValues($v, $callback);
            } else {
                if ($callback($v)) {
                    unset($array[$k]);
                }
            }
        }

        return $array;
    }
}
