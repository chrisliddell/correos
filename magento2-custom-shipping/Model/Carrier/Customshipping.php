<?php

namespace MagePsycho\Customshipping\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;

/**
 * @category   MagePsycho
 * @package    MagePsycho_Customshipping
 * @author     magepsycho@gmail.com
 * @website    http://www.magepsycho.com
 */
class Customshipping extends AbstractCarrier implements CarrierInterface
{
    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'mpcustomshipping';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Generates list of allowed carrier`s shipping methods
     * Displays on cart price rules page
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
    }

    /**
     * Collect and get rates for storefront
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
 public function collectRates(RateRequest $request)

    {

    /**

    * Make sure that Shipping method is enabled

    */


        if (!$this->isActive()) {

            return false;

        }


        //$this->invocarWS();

    $zip = $request->getDestPostcode();

    $weight =  $request->getPackageWeight()*1000;


    //$addressData = "Pais: ".$request->getDestCountryId()." Ciudad: ".$request->getDestRegionId()." Calle: ".$request->getSestStreet().

    " Zip: ".$request->getDestPostcode();

    $writer = new \Zend\Log\Writer\Stream(BP.'/var/log/aproxEnvio.log');

    $logger = new \Zend\Log\Logger();

    $logger->addWriter($writer);

    if($zip != "" && $weight != ""){
    $shippingRate = $this->averiguarPrecio($weight, $zip);
    $shippingRate = explode('<', explode('CostoColones>', $shippingRate)[1])[0]; //<a:CostoColones>XXXX,XX</a:CostoColones> a XXXX,XX
    if($shippingRate =="0") return false; 
    $logger->info("Costo de envio: ".print_r($shippingRate, true)." peso: ".$weight." zip: ".$zip);
	
    }else{
	return false;
    }

        

        /** @var \Magento\Shipping\Model\Rate\Result $result */

        $result = $this->_rateResultFactory->create();

        $shippingPrice = $this->getConfigData('price');

        //$shippingPrice = $this->invocarWS(5);

        $method = $this->_rateMethodFactory->create();

        /**

        * Set carrier's method data

        */

        $method->setCarrier($this->getCarrierCode());

        $method->setCarrierTitle($this->getConfigData('title'));

        /**

        * Displayed as shipping method under Carrier

        */

        

        $method->setMethod($this->getCarrierCode());

        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($shippingRate);

        $method->setCost($shippingRate);

        $result->append($method);

        return $result;

    } 

        

    public function averiguarPrecio(int $peso, string $zip){

        $curl = curl_init(); 

        curl_setopt_array($curl, array(

        CURLOPT_PORT => "82",

        CURLOPT_URL => "http://amistad.correos.go.cr:82/wserPruebas/wsAppCorreos.wsAppCorreos.svc",

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_ENCODING => "",

        CURLOPT_MAXREDIRS => 10,

        CURLOPT_TIMEOUT => 30,

        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        CURLOPT_CUSTOMREQUEST => "POST",

        CURLOPT_POSTFIELDS => "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body><ccrMovilTarifaCCR xmlns=\"http://tempuri.org/\"><resTarifa xmlns:a=\"http://schemas.datacontract.org/2004/07/wsAppCorreos\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\"><a:Cantidad>1</a:Cantidad><a:Pais>CR</a:Pais><a:Peso>".$peso."</a:Peso><a:Prioridad i:nil=\"true\"/><a:Servicio>PYMEXPRESS</a:Servicio><a:TipoEnvio>1</a:TipoEnvio><a:ZonDestino>".$zip."</a:ZonDestino><a:ZonUbicacion>10108</a:ZonUbicacion></resTarifa><User>ccrWS10765</User><Pass>FIHTRQPDU8</Pass></ccrMovilTarifaCCR></s:Body></s:Envelope>",

        CURLOPT_HTTPHEADER => array(

              "Content-Type: text/xml",

              "Postman-Token: 3190431f-0e2f-4a3c-be0c-9f3cbef9a294",

              "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrMovilTarifaCCR",

              "cache-control: no-cache"

            ),

          ));

          

        

        $response = curl_exec($curl); 

        $err = curl_error($curl); 

        curl_close($curl); 

        if ($err) {

        return "cURL Error #:" . $err;

        } else {

        return $response;

        }


    }
}
