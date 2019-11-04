<?php
namespace Imagineer\ShippingCorreosDeCostaRica\Observer;

use Magento\Framework\Event\ObserverInterface; 
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Captcha\Observer\CaptchaStringResolver;


    class RegistrarPedido implements ObserverInterface { 
	private $productRepository;	
	private $storeManager;

	public function __construct(\Magento\Catalog\Api\ProductRepositoryInterface $productRepository, \Magento\Store\Model\StoreManagerInterface $storeManager){
		$this->productRepository = $productRepository;
		$this->storeManager = $storeManager;
	}


        public function execute(\Magento\Framework\Event\Observer $observer){

	if($this->storeManager->getStore()->getCode() == 'cr'){

            $order = $observer->getEvent()->getOrder();
            $customerId = $order->getCustomerId();
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/registroEnvio.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            //llama ws de correos para generar guia
	    $numGuia = 0;
	    $user = 'ccrWS10765';
	    $pass = 'FIHTRQPDU8';
	

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
   CURLOPT_POSTFIELDS => '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><ccrGenerarGuia xmlns="http://tempuri.org/"><Datos xmlns:a="http://schemas.datacontract.org/2004/07/wsAppCorreos" xmlns:i="http://www.w3.org/2001/XMLSchema-instance"><a:CodCliente>10765</a:CodCliente><a:TipoCliente>2</a:TipoCliente></Datos><User>'.$user.'</User><Pass>'.$pass.'</Pass></ccrGenerarGuia></s:Body></s:Envelope>',
   CURLOPT_HTTPHEADER => array(
    "Content-Type: text/xml",
    "Postman-Token: 30ba7515-f9c7-4f5e-99a2-82e1856cb86b,91d7e6fd-db51-4c2b-a772-b74e02094815",
    "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrGenerarGuia",
    "cache-control: no-cache"
    ),
  ));
  $response = curl_exec($curl); 
  $err = curl_error($curl);
  curl_close($curl); 
  if ($err) {
    $numGuia = "error";
  } else {
    $numGuia =  $response;
  }
  if($numGuia != "error"){

	$logger->info("Respuesta: ".$numGuia);
	$numGuia = explode('<', explode('ListadoXML>', $numGuia)[1])[0];
        $logger->info("numGuia: ".$numGuia);


  //generar pedido con correos de costa rica
        $billingAddress = $order->getBillingAddress();

        $codCliente = '10765';

        $dir = implode(", ",$billingAddress->getStreet());
        $nomCliente = $billingAddress->getFirstname() . ' ' .  $billingAddress->getLastname();
        $tel = $billingAddress->getTelephone();
        $zip = $billingAddress->getPostcode();
 	$obs = ""; 
	$peso = 0;
	$items = $order->getAllItems();
	foreach($items as $item){
		$obs .= ($item->getName()) . ", ";
		$peso += ($this->productRepository->get($item->getSku())->getWeight())*1000; //pasarlo a kilos
	}
        $telCPT = '22225516';

        $dirCPT = 'Sabana Norte, 100 mt. norte de Torre La Sabana';

        $zipCPT = '10108';

        $fecha = date('d/m/Y');

	$logger->info("datos: ".$dir." ".$nomCliente." ".$tel." ".$zip." ".$obs." ".$peso);	

	$envio = '<wsap:Cliente>'.$codCliente.'</wsap:Cliente><wsap:Envio><wsap:DEST_APARTADO>'.$zip.'</wsap:DEST_APARTADO><wsap:DEST_DIRECCION>'.$dir.'</wsap:DEST_DIRECCION>'

                                .'<wsap:DEST_NOMBRE>'.$nomCliente.'</wsap:DEST_NOMBRE><wsap:DEST_PAIS>CR</wsap:DEST_PAIS><wsap:DEST_TELEFONO>'.$tel.'</wsap:DEST_TELEFONO>'

                                .'<wsap:DEST_ZIP>'.$zip.'</wsap:DEST_ZIP><wsap:FECHA_RECEPCION>'.$fecha.'</wsap:FECHA_RECEPCION>'

                                .'<wsap:ENVIO_ID>'.$numGuia.'</wsap:ENVIO_ID><wsap:ID_DISTRITO_DESTINO>'.$zip.'</wsap:ID_DISTRITO_DESTINO><wsap:MONTO_FLETE></wsap:MONTO_FLETE>'

                                .'<wsap:OBSERVACIONES>'.$obs.'</wsap:OBSERVACIONES><wsap:PESO>'.$peso.'</wsap:PESO><wsap:SEND_DIRECCION>'.$dirCPT.'</wsap:SEND_DIRECCION>';
	$logger->info($envio);
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

        CURLOPT_POSTFIELDS => '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/" xmlns:wsap="http://schemas.datacontract.org/2004/07/wsAppCorreos"><soapenv:Header/><soapenv:Body><tem:ccrRegistroEnvio><tem:ccrReqEnvio>'

                                .'<wsap:Cliente>'.$codCliente.'</wsap:Cliente><wsap:Envio><wsap:DEST_APARTADO>'.$zip.'</wsap:DEST_APARTADO><wsap:DEST_DIRECCION>'.$dir.'</wsap:DEST_DIRECCION>'

                                .'<wsap:DEST_NOMBRE>'.$nomCliente.'</wsap:DEST_NOMBRE><wsap:DEST_PAIS>CR</wsap:DEST_PAIS><wsap:DEST_TELEFONO>'.$tel.'</wsap:DEST_TELEFONO>'

                                .'<wsap:DEST_ZIP>'.$zip.'</wsap:DEST_ZIP>'

                                .'<wsap:ENVIO_ID>'.$numGuia.'</wsap:ENVIO_ID><wsap:ID_DISTRITO_DESTINO>'.$zip.'</wsap:ID_DISTRITO_DESTINO><wsap:MONTO_FLETE>0</wsap:MONTO_FLETE>'

                                .'<wsap:OBSERVACIONES>'.$obs.'</wsap:OBSERVACIONES><wsap:PESO>'.$peso.'</wsap:PESO><wsap:SEND_DIRECCION>'.$dirCPT.'</wsap:SEND_DIRECCION>'

                                .'<wsap:SEND_NOMBRE>Cuero papel y tijera</wsap:SEND_NOMBRE><wsap:SEND_TELEFONO>'.$telCPT.'</wsap:SEND_TELEFONO><wsap:SEND_ZIP>'.$zipCPT.'</wsap:SEND_ZIP>'

                                .'<wsap:SERVICIO>2.3.2</wsap:SERVICIO><wsap:USUARIO_ID>10765</wsap:USUARIO_ID></wsap:Envio></tem:ccrReqEnvio>'

                                .'<tem:User>'.$user.'</tem:User><tem:Pass>'.$pass.'</tem:Pass></tem:ccrRegistroEnvio></soapenv:Body></soapenv:Envelope>',

        CURLOPT_HTTPHEADER => array(

            "Content-Type: text/xml",

            "Postman-Token: c5be9fd8-42be-45e8-aa45-0e9fc9928386,0f945dec-3cd3-4daa-8c69-c6bcf4686c29",

            "SOAPAction: http://tempuri.org/IwsAppCorreos/ccrRegistroEnvio",

            "cache-control: no-cache"

        ),

        ));


        $response = curl_exec($curl);

        $err = curl_error($curl);


        curl_close($curl);


        if ($err) {

        $respuesta = "cURL Error #:" . $err;

        } else {

        $respuesta = $response;

        }

        $logger->info($respuesta);

      }
    }
  }
}
