<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzlePsr7Request;
use League\Uri\Modifiers\AppendSegment;
use League\Uri\Schemes\Http as HttpUri;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;

class RedemptionOrderVoucherGenerator
{
    const FILENAME = 'Redemption_Order_Voucher';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var HttpUri
     */
    private $baseUri;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface    $serializer
     * @param string                 $documentConverterHost
     * @param string                 $timezone
     */
    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, string $documentConverterHost, string $timezone)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->baseUri = HttpUri::createFromString($documentConverterHost);
        $this->client = new GuzzleClient();
        $this->timezone = new \DateTimeZone($timezone);
    }

    /**
     * Generates redemption order voucher pdf for the order.
     *
     * @param Order  $order
     * @param string $profile
     *
     * @return string
     */
    public function generateRedemptionOrderVoucher(Order $order, string $profile)
    {
        try {
            $now = new \DateTime();
            $filePath = \sprintf('%s/%s_%s.%s', \sys_get_temp_dir(), self::FILENAME, $now->setTimezone($this->timezone)->format('YmdHis'), 'pdf');

            $orderSerializeOptions = [
                'context' => [
                    'order_read',
                ],
            ];

            $data = [
                'order' => \json_decode($this->serializer->serialize($order, 'jsonld', $orderSerializeOptions)),
                'profile' => $profile,
            ];

            $modifier = new AppendSegment('pdf/redemption_orders_voucher');
            $uri = $modifier->process($this->baseUri);

            $headers = [
                'User-Agent' => 'U-Centric API',
                'Content-Type' => 'application/json',
            ];

            $request = new GuzzlePsr7Request('POST', $uri, $headers, \json_encode($data));
            $response = $this->client->send($request, ['save_to' => $filePath]);

            return $filePath;
        } catch (\Exception $ex) {
            throw new BadRequestHttpException($ex->getMessage());
        }
    }
}
