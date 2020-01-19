<?php

declare(strict_types=1);

namespace App\WebService\Billing\Provider\Anacle;

use Psr\Log\LoggerInterface;

class AnacleSoapClient extends \SoapClient
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct($wsdl, $options, $logger)
    {
        parent::__construct($wsdl, $options);
        $this->logger = $logger;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = null)
    {
        $namespace = 'mybill.sg';

        $request = \str_replace('SOAP-ENV', 'soap', $request);
        $request = \str_replace(':SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"', ':xsd="http://www.w3.org/2001/XMLSchema"', $request);
        $request = \str_replace('<SOAP-ENC:Struct>', '', $request);
        $request = \str_replace('</SOAP-ENC:Struct>', '', $request);
        $request = \str_replace('xmlns:ns1="'.$namespace.'"', '', $request);
        $request = \str_replace('xsi:type', 'xmlns', $request);
        $request = \str_replace('<ns1:AuthHeader', '<AuthHeader xmlns="'.$namespace.'"', $request);
        $request = \str_replace('ns1:', '', $request);
        $request = \str_replace(' >', '>', $request);

        //log the request
        $pattern = '/<FileBytes>.*<\/FileBytes>/';
        $replacement = '<FileBytes>base64 encoded string</FileBytes>';
        $requestLog = \preg_replace($pattern, $replacement, $request);

        $this->logger->info($requestLog);

        // parent call
        return parent::__doRequest($request, $location, $action, $version, $one_way);
    }
}
