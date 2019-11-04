<?php

namespace Imagineer\ShippingCorreosDeCostaRica\Model\Carrier;

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
 
class Customshipping extends AbstractCarrier implements CarrierInterface
{
    
    /**
     
    * Carrier's code
    *
    * @var string
    */
    
    protected $_code = 'shippingcorreosdecostarica';
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
	$weight =  $request->getPackageWeight() * 1000;
	
	//$addressData = "Pais: ".$request->getDestCountryId()." Ciudad: ".$request->getDestRegionId()." Calle: ".$request->getSestStreet()." Zip: ".$request->getDestPostcode();
	$writer = new \Zend\Log\Writer\Stream(BP.'/var/log/test.log');
	$logger = new \Zend\Log\Logger();
	$logger->addWriter($writer);

	if($zip != "" && $weight != ""){
		$shippingRate = $this->averiguarPrecio(intval($weight*1000), $zip);
		$shippingRate = explode('<', $shippingRate)[0];
		$logger->info("Costo de envio: ".print_r($shippingRate, true)." peso: ".$weight." zip: ".$zip);
	}else{
		$shippingRate = 0;
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
        
    public function invocarWS(int $servicio, float $peso, string $zip)
    {
        $ws = 'http://amistad.correos.go.cr:82/wserPruebas/wsAppCorreos.wsAppCorreos.svc';
        $user = 'ccrWS10765';
        $pass = 'FIHTRQPDU8';
        $tipoCliente = "2";
        $servicioId = "2.3.2";
        $codigoCliente = "10765";
        $userId = "10765";
        $body ="";
        $action="";
        switch($servicio){
            case 1: //ccrProvincia  devuelve el listado de provincias
                $body = "\r\n<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body>
                <ccrProvincia xmlns=\"http://tempuri.org/\">               
                <User>".$user."</User>
                <Pass>".$pass."</Pass></ccrProvincia></s:Body></s:Envelope>";
                $action = "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrProvincia";
            break;
            case 2: //ccrCanton   devuelve los cantones en la provincia 
                $body = "\r\n<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body>
                <ccrCanton xmlns=\"http://tempuri.org/\">
                <Provincia>Alajuela</Provincia>
                <User>".$user."</User>
                <Pass>".$pass."</Pass>
                </ccrCanton></s:Body></s:Envelope>";
                $action = "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrCanton";

            break;
            case 3: //ccrDistrito    devuelve los distritos en el canton
                $body = "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body>
                <ccrDistrito xmlns=\"http://tempuri.org/\">
                <Canton>Alajuela</Canton>
                <User>".$user."</User>
                <Pass>".$pass."</Pass>
                </ccrDistrito></s:Body></s:Envelope>";
                $action = "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrDistrito";
            break;
            case 4: //ccrCodPostal devuelve el codigo postal dado el canton y distrito
                $body = "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body>
                <ccrCodPostal xmlns=\"http://tempuri.org/\">
                <Canton>Alajuela</Canton>
                <Distrito>Alajuela</Distrito>
                <User>".$user."</User>
                <Pass>".$pass."</Pass>
                </ccrCodPostal></s:Body></s:Envelope>";
                $action = "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrCodPostal";
            break;
            case 5: //ccrMovilTarifaCCR   devuelve un aproximado del costo
                $body = '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><ccrMovilTarifaCCR xmlns="http://tempuri.org/"><resTarifa xmlns:a="http://schemas.datacontract.org/2004/07/wsAppCorreos" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><a:Cantidad>1</a:Cantidad><a:Pais>CR</a:Pais><a:Peso>'.$peso.'</a:Peso><a:Prioridad i:nil="true"/><a:Servicio>PYMEXPRESS</a:Servicio><a:TipoEnvio>1</a:TipoEnvio><a:ZonDestino>'.$zip.'</a:ZonDestino><a:ZonUbicacion>10108</a:ZonUbicacion></resTarifa><User>'.$user.'</User><Pass>'.$pass.'</Pass></ccrMovilTarifaCCR></s:Body></s:Envelope>';
                $action = 'SOAPAction: http://tempuri.org/IwsAppCorreos/ccrMovilTarifaCCR';


            break;
            case 6: //ccrGenerarGuia      genera el número de envío 
                $body = "<s:Envelope xmlns:s=\"http://schemas.xmlsoap.org/soap/envelope/\"><s:Body>
                <ccrGenerarGuia xmlns=\"http://tempuri.org/\">
                <Datos xmlns:a=\"http://schemas.datacontract.org/2004/07/wsAppCorreos\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\">
                <a:CodCliente>".$codigoCliente."</a:CodCliente>
                <a:TipoCliente>".$tipoCliente."</a:TipoCliente></Datos>                
                <User>".$user."</User>
                <Pass>".$pass."</Pass>
                </ccrGenerarGuia></s:Body></s:Envelope>";

                $action = "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrGenerarGuia";
            break;
            case 7: //ccrRegistroEnvio    genera el envio, primero debe llamarse a "ccrGenerarGuia"  FALTA FECHA DE ENVIA PORQUE ESPERA UN OBJETO DATETIME DE C#
                $body = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\" xmlns:wsap=\"http://schemas.datacontract.org/2004/07/wsAppCorreos\">
                <soapenv:Header/><soapenv:Body><tem:ccrRegistroEnvio>
                <tem:ccrReqEnvio>
                <wsap:Cliente>".$codigoCliente."</wsap:Cliente>
                <wsap:Envio><wsap:DEST_APARTADO>10108</wsap:DEST_APARTADO>
                <wsap:DEST_DIRECCION>Sabana sur</wsap:DEST_DIRECCION><wsap:DEST_NOMBRE>Imagineer</wsap:DEST_NOMBRE><wsap:DEST_PAIS>CR</wsap:DEST_PAIS>
                <wsap:DEST_TELEFONO>12345678</wsap:DEST_TELEFONO><wsap:DEST_ZIP>10108</wsap:DEST_ZIP>
                <wsap:ENVIO_ID>WS006412605CR</wsap:ENVIO_ID>
                <wsap:ID_DISTRITO_DESTINO>10108</wsap:ID_DISTRITO_DESTINO>
                <wsap:MONTO_FLETE>5000</wsap:MONTO_FLETE><wsap:OBSERVACIONES>Bolso de cuero</wsap:OBSERVACIONES><wsap:PESO>800</wsap:PESO>
                <wsap:SEND_DIRECCION>Sabana norte</wsap:SEND_DIRECCION><wsap:SEND_NOMBRE>Cuero papel y tijera</wsap:SEND_NOMBRE>
                <wsap:SEND_TELEFONO>87654321</wsap:SEND_TELEFONO><wsap:SEND_ZIP>10108</wsap:SEND_ZIP><wsap:SERVICIO>".$servicioId."</wsap:SERVICIO>
                <wsap:USUARIO_ID>".$userId."</wsap:USUARIO_ID></wsap:Envio></tem:ccrReqEnvio>
                <tem:User>".$user."</tem:User><tem:Pass>".$pass."</tem:Pass>
                </tem:ccrRegistroEnvio></soapenv:Body></soapenv:Envelope>";
                $action = "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrRegistroEnvio";
            break;
            default:

            break;
        }
	
        //invoca ws  
        $curl = curl_init();   
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "82",
            CURLOPT_URL => $ws,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Cache-Control: no-cache',
                'Content-Type: text/xml',
                $action
                )
            )
        );

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return '{"error":"'.htmlentities($err).'"}';
        } else {
          return $response;
        }
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

			return (explode("CostoColones>",$response))[1];

        }
    }

}
