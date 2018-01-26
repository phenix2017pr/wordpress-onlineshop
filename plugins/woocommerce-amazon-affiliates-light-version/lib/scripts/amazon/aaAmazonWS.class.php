<?php
/**
 * Amazon Webservices Client Class
 * http://www.amazon.country
 * =========================
 *
 * @package			aaAmazonWS
 * @author			 AA-Team
 */
if ( !class_exists('aaAmazonWS') ) {
class aaAmazonWS
{
	const RETURN_TYPE_ARRAY	= 1;
	const RETURN_TYPE_OBJECT = 2;
	
	private $plugin_alias = '';

	private $protocol = 'SOAP';

	/**
	 * Baseconfigurationstorage
	 *
	 * @var array
	 */
	private $requestConfig = array();

	/**
     * The Session Key used for cart tracking
     * @access protected
     * @var string
     */
    private $_sessionKey = 'amzCart';

	/**
	 * Responseconfigurationstorage
	 *
	 * @var array
	 */
	private $responseConfig = array(
		'returnType'			=> self::RETURN_TYPE_ARRAY,
		'responseGroup'			=> 'Small',
		'optionalParameters'	=> array()
	);

	/**
	 * All possible locations
	 *
	 * @var array
	 */
	private $possibleLocations = array('de', 'com', 'co.uk', 'ca', 'fr', 'co.jp', 'it', 'cn', 'es', 'in', 'com.mx', 'com.br', 'com.au');

	/**
	 * The WSDL File
	 *
	 * @var string
	 */
	protected $webserviceWsdl = 'http://webservices.amazon.com/AWSECommerceService/AWSECommerceService.wsdl';

	/**
	 * The SOAP Endpoint
	 *
	 * @var string
	 */
	protected $webserviceEndpoint = 'https://webservices.amazon.%%COUNTRY%%/onca/%%PROTOCOL%%?Service=AWSECommerceService';
	//protected $webserviceEndpoint = 'https://sha256.webservices.amazon.%%COUNTRY%%/onca/%%PROTOCOL%%?Service=AWSECommerceService';
	
	private $__lastCart = null;
	private $__lastCart_ = null;

    private $xmlAmazonLink = null;
    private $the_plugin = null;
    private $amz_settings = array();


	/**
	 * @param string $accessKey
	 * @param string $secretKey
	 * @param string $country
	 * @param string $associateTag
	 */
	public function __construct($accessKey, $secretKey, $country, $associateTag='' )
	{
		if (!session_id()) {
		  @session_start();
		}

		// private setter helper
		$this->setProtocol();

		/*if (empty($accessKey) || empty($secretKey))
		{
			throw new Exception('No Access Key or Secret Key has been set');
		}*/

		$this->requestConfig['accessKey']		 = $accessKey;
		$this->requestConfig['secretKey']		 = $secretKey;
		
		$this->associateTag( $associateTag );
		
		$this->country( $country );
	}

    public function set_the_plugin( $the_plugin=array(), $settings=array() ) {
        $this->the_plugin = $the_plugin;
        
        if ( !empty($this->the_plugin) && !empty($this->the_plugin->amz_settings) ) {
            $this->amz_settings = $this->the_plugin->amz_settings;
        } else {
            $this->amz_settings = $settings;
        }
		$this->amz_settings = !empty($this->amz_settings) && is_array($this->amz_settings) ? $this->amz_settings : array();

        // private setter helper
        $this->setProtocol();
    }
    
    private function save_amazon_request_time() {
        if ( !empty($this->the_plugin) && is_object($this->the_plugin)
        	//&& is_a($this->the_plugin, $this->plugin_alias) ) {
        	) {
            return $this->the_plugin->save_amazon_request_time();
        }
        return false;
    }
    
    private function verify_amazon_request_rate( $do_pause=true ) {
        if ( !empty($this->the_plugin) && is_object($this->the_plugin)
			//&& is_a($this->the_plugin, $this->plugin_alias) ) {
            ) {
            return $this->the_plugin->verify_amazon_request_rate( $do_pause );
        }
        return false;
    }

	private function setProtocol()
	{ 
		$db_protocol_setting = isset($this->amz_settings['protocol']) ? $this->amz_settings['protocol'] : 'auto';
		
		$this->protocol = 'XML';
		if ( extension_loaded('soap') && in_array($db_protocol_setting, array('soap', 'auto')) ) {
			$this->protocol = 'SOAP';
		}

	}

	/**
	 * execute search
	 *
	 * @param string $pattern
	 *
	 * @return array|object return type depends on setting
	 *
	 * @see returnType()
	 */
	public function search($pattern, $nodeId = null)
	{
		if (false === isset($this->requestConfig['category']))
		{
			throw new Exception('No Category given: Please set it up before');
		}

		$browseNode = array();
		if (null !== $nodeId && true === $this->validateNodeId($nodeId))
		{
			$browseNode = array('BrowseNode' => $nodeId);
		}

		$params = $this->buildRequestParams('ItemSearch', array_merge(
			array(
				'Keywords' 		=> $pattern,
				'SearchIndex' 	=> $this->requestConfig['category']
			),
			$browseNode
		));

		return $this->returnData(
			$this->performTheRequest("ItemSearch", $params)
		);
	}

	/**
     * Convenience method to bulk submit a couple items, or just one single item. This will create a cart if necessary.
     *
     *  Example: $this->Amazon->cartThem(array(array('offerId' => 'asdasd...', 'quantity' => 3), array(...)));
      *
     * @access public
     * @param array $selectedItems A array with offerIds and quantity keys.
     * @return mixed Response or FALSE if nothing to do or bad input
     */
    public function cartThem($selectedItems)
	{
        $result = false;
        if (!empty($selectedItems) && is_array($selectedItems)) {
            if (!isset($_SESSION[$this->_sessionKey]["cartId"])) { // new cart
                $firstItem = array_shift($selectedItems);
                $result = $this->cartCreate($firstItem['offerId'], $firstItem['quantity']);
            }
  
            if (count($selectedItems)) { // add
                foreach ($selectedItems as $item) {
                    $result = $this->cartAdd($item['offerId'], $item['quantity']);
                }
            }
        }
        return $result;
    }

    /**
     * Creates a new Remote Cart. A new cart is initialized once you add at least 1 item. The HMAC and CartID
     * is used in all further communications. BEFORE YOU CAN USE THE CART, YOU HAVE TO ADD 1 ITEM AT LEAST!
     *
     * @access public
     * @param array $offerListingId An OfferListing->OfferListingId from Lookup or Search. You'll need "Offer" response group!
     * @param integer $quantity The amount the user wants from this item.
     * @return array
     */
    public function cartCreate($offerListingId, $quantity = 1)
	{
		$params = $this->buildRequestParams('CartCreate',
			array( 'Items' =>
				array(
					'Item' => array('ASIN' => $offerListingId, 'Quantity' => $quantity)
				)
			)
		);

		$response = $this->returnData(
			$this->performTheRequest("CartCreate", $params)
		);

		$this->__lastCart_ = $response;
        $response = $response['Cart'];

		// first if return some error
		if( isset($response['Request']['Errors']) ) {
			die(json_encode(array(
				'status' 	=> 'invalid',
				'msg'		=> $offerListingId . ' : ' . ( isset($response['Request']['Errors']['Error']['Message']) ? $response['Request']['Errors']['Error']['Message'] : 'Unable to add this product to cart. Please contact shop administrator. ' )
			)));
		}

        // save the result in the session
        $_SESSION[$this->_sessionKey] = array(
            'HMAC' => $response['HMAC'],
            'cartId' => $response['CartId'],
            'PurchaseUrl' => $response['PurchaseURL'],
        );

        return $this->__formatCartItems($response);
    }

    /**
     * Adds a new Item with given quantity to the remote cart.
     *
     * @access public
     * @param string $offerListingId An ItemID from Lookup or Search Offer
     * @param integer $quantity As the name says..
     * @param string $HMAC (optional) HMAC If empty, uses session.
     * @param string $cartId (optional) Remote cart ID. If empty, uses session.
     * @return mixed Response or FALSE on missing HMAC/ID
     */
    public function cartAdd($offerListingId, $quantity = 1, $HMAC = null, $cartId = null) {
        if (!$HMAC) {
            $HMAC = $_SESSION[$this->_sessionKey]['HMAC'];
        }
        if (!$cartId) {
            $cartId = $_SESSION[$this->_sessionKey]['cartId'];
        }

        if (!$HMAC || !$cartId) {
            return false;
        }

		$params = $this->buildRequestParams('CartAdd',
			array(
				'CartId' 	=> $cartId,
				'HMAC' 		=> $HMAC,
				'Items' 	=>
					array(
						'Item' => array('ASIN' => $offerListingId, 'Quantity' => $quantity)
					)
			)
		);
		$response = $this->returnData(
			$this->performTheRequest("CartAdd", $params)
		);
		
		$this->__lastCart_ = $response;

		$response = $response['Cart'];
    
		// first if return some error
		if( isset($response['Request']['Errors']) ) {
			$code = isset($response['Request']['Errors']['Error']['Code']) ? $response['Request']['Errors']['Error']['Code'] : false;
			if ( !empty($code) && 'AWS.ECommerceService.ItemAlreadyInCart' == $code ) {
			} else {

			die(json_encode(array(
				'status' 	=> 'invalid',
				'msg'		=> $offerListingId . ' : ' . ( isset($response['Request']['Errors']['Error']['Message']) ? $response['Request']['Errors']['Error']['Message'] : 'Unable to add this product to cart. Please contact shop administrator. ' )
			)));
			}
		}

        return $this->__formatCartItems($response);
    }

    /**
     * Update the Quantity of a CartItem
     *
     * @access public
     * @param string $cartItemId As the name says.. [CartItem][CartItemId]
     * @param integer $quantity As the name says..
     * @param string $HMAC (optional) HMAC which was returned with cartCreate. If empty, uses session.
     * @param string $cartId (optional) The ID of the remote cart. If empty, uses session.
     * @return mixed Response or FALSE on missing HMAC/ID
     */
    public function cartUpdate($cartItemId, $quantity, $HMAC = null, $cartId = null)
    {
        if (!$HMAC) {
            $HMAC = isset($_SESSION[$this->_sessionKey]['HMAC']) ? $_SESSION[$this->_sessionKey]['HMAC'] : '';
        }
        if (!$cartId) {
        	$cartId = isset($_SESSION[$this->_sessionKey]['cartId']) ? $_SESSION[$this->_sessionKey]['cartId'] : '';
        }
        if (!$HMAC || !$cartId) {
            return false;
        }

		$params = $this->buildRequestParams('CartModify',
			array(
				'CartId' 	=> $cartId,
				'HMAC' 		=> $HMAC,
				'Items' 	=>
					array(
						'Item' => array('CartItemId' => $cartItemId, 'Quantity' => $quantity)
					)
			)
		);

		$response = $this->returnData(
			$this->performTheRequest("CartModify", $params)
		);
		$this->__lastCart_ = $response;
        return $this->__formatCartItems($response['Cart']['Request']['CartModifyRequest']);
    }

	/**
     * Gets the current remote cart contents
     *
     * @access public
     * @param string $HMAC (optional) HMAC which was returned with cartCreate. If empty, uses session.
     * @param string $cartId (optional) The ID of the remote cart. If empty, uses session.
     * @return mixed Response or FALSE on missing HMAC/ID
     */
    public function cartGet($HMAC = null, $cartId = null) {
        if (!$HMAC) {
            $HMAC = isset($_SESSION[$this->_sessionKey]['HMAC']) ? $_SESSION[$this->_sessionKey]['HMAC'] : '';
        }
        if (!$cartId) {
            $cartId = isset($_SESSION[$this->_sessionKey]['cartId']) ? $_SESSION[$this->_sessionKey]['cartId'] : '';
        }
        if (!$HMAC || !$cartId) {
            return false;
        }

        $params = $this->buildRequestParams('CartGet',
			array(
				'CartId' 	=> $cartId,
				'HMAC' 		=> $HMAC
			)
		);

		return $this->returnData(
			$this->performTheRequest("CartGet", $params)
		);
    }

    /**
     * Check if an remote cart is available based on last/given response
     *
     * @access public
     * @param array $cart A cart response
     * @return boolean
     */
    public function cartIsActive($cart = null) {
        if (!$cart) {
            $cart = $this->__lastCart;
        }
        return ($cart && isset($cart['CartId']));
    }

    /**
     * Check if Cart-Response has any Items
     *
     * @access public
     * @author Kjell Bublitz <m3nt0r.de@gmail.com>
     * @param array $cart A cart response
     * @return boolean
     */
    public function cartHasItems($cart = null) {
        if (!$cart) {
            $cart = $this->__lastCart;
        }
        return ($cart && isset($cart['CartItems']));
    }

    /**
     * Remove Cart from Session.
     *
     * @access public
     * @return boolean
     */
    public function cartKill()
    {
		unset($_SESSION[$this->_sessionKey]);
		unset($_SESSION['aaCartProd']);
    }

    /**
     * Makes sure that CartItem is always a single dim array.
     *
     * @access private
     * @param array $cart Cart Response
     * @return array Cart Response
     */
    public function __formatCartItems($cart) {

        unset($cart['Request']);
        if (isset($cart['CartItems'])) {
            $_cartItem = $cart['CartItems']['CartItem'];
            $items = array_keys($_cartItem);
            if (!is_numeric(array_shift($items))) {
                $cart['CartItems']['CartItem'] = array($_cartItem);
            }
        }
        $this->__lastCart = $cart; // for easier working with helper methods
    
        return $cart;
    }

	/**
	 * execute ItemLookup request
	 *
	 * @param string $asin
	 *
	 * @return array|object return type depends on setting
	 *
	 * @see returnType()
	 */
	public function lookup($asin)
	{
		$params = $this->buildRequestParams('ItemLookup', array(
			'ItemId' => $asin,
		));

		return $this->returnData(
			$this->performTheRequest("ItemLookup", $params)
		);
	}

	/**
	 * Implementation of BrowseNodeLookup
	 * This allows to fetch information about nodes (children anchestors, etc.)
	 *
	 * @param integer $nodeId
	 */
	public function browseNode($nodeId)
	{
		$this->validateNodeId($nodeId);
		$this->responseConfig['BrowseNode'] = $nodeId;
	}

	/**
	 * Implementation of BrowseNodeLookup
	 * This allows to fetch information about nodes (children anchestors, etc.)
	 *
	 * @param integer $nodeId
	 */
	public function browseNodeLookup($nodeId)
	{
		$this->validateNodeId($nodeId);

		$params = $this->buildRequestParams('BrowseNodeLookup', array(
			'BrowseNodeId' => $nodeId
		));

		return $this->returnData(
			$this->performTheRequest("BrowseNodeLookup", $params)
		);
	}

	/**
	 * Implementation of SimilarityLookup
	 * This allows to fetch information about product related to the parameter product
	 *
	 * @param string $asin
	 */
	public function similarityLookup($asin)
	{
		$params = $this->buildRequestParams('SimilarityLookup', array(
			'ItemId' => $asin
		));

		return $this->returnData(
			$this->performTheRequest("SimilarityLookup", $params)
		);
	}

	/**
	 * Builds the request parameters
	 *
	 * @param string $function
	 * @param array	$params
	 *
	 * @return array
	 */
	protected function buildRequestParams($function, array $params)
	{
		$associateTag = array();

		if(false === empty($this->requestConfig['associateTag']))
		{
			$associateTag = array('AssociateTag' => $this->requestConfig['associateTag']);
		}

		return array_merge(
			$associateTag,
			array(
				'AWSAccessKeyId' => $this->requestConfig['accessKey'],
				'Request' => array_merge(
					array('Operation' => $function),
					$params,
					$this->responseConfig['optionalParameters'],
					array('ResponseGroup' => $this->prepareResponseGroup())
		)));
	}

	/**
	 * Prepares the responsegroups and returns them as array
	 *
	 * @return array|prepared responsegroups
	 */
	protected function prepareResponseGroup()
	{
		if (false === strstr($this->responseConfig['responseGroup'], ','))
			return $this->responseConfig['responseGroup'];

		return explode(',', $this->responseConfig['responseGroup']);
	}

	/**
	 * @param string $function Name of the function which should be called
	 * @param array $params Requestparameters 'ParameterName' => 'ParameterValue'
	 *
	 * @return array The response as an array with stdClass objects
	 */
	protected function performXMLRequest($function, $params, $execute=true)
	{
		$_params = $params['Request'];

		$params = array_merge($params, $_params);
		unset($params['Request']);

		if( is_array($params['ResponseGroup']) ){
			$params['ResponseGroup'] = implode(",", $params['ResponseGroup']);
		}

	 	$sign_params = array();

		if( $params['Operation'] == 'ItemLookup' ){
			$sign_params['Operation']            = $params['Operation'];
			$sign_params['ItemId']               = $params['ItemId'];
			if ( isset($params['MerchantId']) ) {
				$sign_params['MerchantId']       = $params['MerchantId'];
			}
			$sign_params['ResponseGroup']        = $params['ResponseGroup'];
		}
		
		if( $params['Operation'] == 'SimilarityLookup' ){
			$sign_params['Operation']            = $params['Operation'];
			$sign_params['ItemId']               = $params['ItemId'];
			if ( isset($params['Condition']) ) {
				$sign_params['Condition']        = $params['Condition'];
			}
			if ( isset($params['MerchantId']) ) {
				$sign_params['MerchantId']       = $params['MerchantId'];
			}
			$sign_params['ResponseGroup']        = $params['ResponseGroup'];
		}

		if( $params['Operation'] == 'ItemSearch' ){
			$sign_params['Operation']            = $params['Operation'];
			$sign_params['Keywords']             = $params['Keywords'];
			$sign_params['SearchIndex']          = $params['SearchIndex'];
			$sign_params['ItemPage']             = $params['ItemPage'];
			$sign_params['ResponseGroup']        = $params['ResponseGroup'];
			
			if ( isset($params['MerchantId']) ) {
				$sign_params['MerchantId']       = $params['MerchantId'];
			}
			
			if( $sign_params['SearchIndex'] != "All" ){
				$sign_params['BrowseNode'] = '';
				if (!empty($params['BrowseNode'])) {
					$sign_params['BrowseNode']	= $params['BrowseNode'];
				}
			}

			$sign_params = array_merge($sign_params, array_diff_key($params, array(
				'Operation' 		=> 1,
				'Keywords' 			=> 1,
				'SearchIndex'		=> 1,
				'ItemPage'			=> 1,
				'ResponseGroup'		=> 1,
				'BrowseNode'		=> 1,
				'AssociateTag'		=> 1,
				'AWSAccessKeyId'	=> 1,
			)));
			if( $sign_params['SearchIndex'] == "All" ){
				unset($sign_params["Sort"]);
			}
			//var_dump('<pre>', $sign_params, '</pre>'); die('debug...'); 
		}

		// http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CartCreate.html
		if( $params['Operation'] == 'CartCreate' ){
			$sign_params['Operation'] 		= $params['Operation'];

			/**
			 * Item.1.ASIN=[ASIN]&
			 * Item.1.Quantity=2&
			 */
			if( count($params['Items']) > 0 ){
				$c = 1;
				foreach ($params['Items'] as $key => $value){
					$sign_params['Item.' . $c . '.ASIN'] = $value['ASIN'];
					$sign_params['Item.' . $c . '.Quantity'] = $value['Quantity'];
					$c++;
				}
			}
		}

		// http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CartModify.html
		if( $params['Operation'] == 'CartModify' ){
			$sign_params['Operation'] 	= $params['Operation'];
			$sign_params['CartId'] 		= $params['CartId'];
			$sign_params['HMAC'] 		= $params['HMAC'];

			/**
			 * Item.1.ASIN=[ASIN]&
			 * Item.1.Quantity=2&
			 */
			if( count($params['Items']) > 0 ){
				$c = 1;
				foreach ($params['Items'] as $key => $value){
					$sign_params['Item.' . $c . '.CartItemId'] = $value['CartItemId'];
					$sign_params['Item.' . $c . '.Quantity'] = $value['Quantity'];
					$c++;
				}
			}
		}

		// http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CartAdd.html
		if( $params['Operation'] == 'CartAdd' ){
			$sign_params['Operation'] 	= $params['Operation'];
			$sign_params['CartId'] 		= $params['CartId'];
			$sign_params['HMAC'] 		= $params['HMAC'];

			/**
			 * Item.1.ASIN=[ASIN]&
			 * Item.1.Quantity=2&
			 */
			if( count($params['Items']) > 0 ){
				$c = 1;
				foreach ($params['Items'] as $key => $value){
					$sign_params['Item.' . $c . '.ASIN'] = $value['ASIN'];
					$sign_params['Item.' . $c . '.Quantity'] = $value['Quantity'];
					$c++;
				}
			}
		}

 		// http://docs.aws.amazon.com/AWSECommerceService/latest/DG/CartGet.html
		if( $params['Operation'] == 'CartGet' ){
			$sign_params['Operation'] 	= $params['Operation'];
			$sign_params['CartId'] 		= $params['CartId'];
			$sign_params['HMAC'] 		= $params['HMAC'];
		}

        if( $params['Operation'] == 'BrowseNodeLookup' ){
            $sign_params['Operation']            = $params['Operation'];
            $sign_params['BrowseNodeId']         = $params['BrowseNodeId'];
        }

		$amzLink = $this->aws_signed_request(
			$this->responseConfig['country'],
			$sign_params,
	        $this->requestConfig['accessKey'],
	        $this->requestConfig['secretKey'],
	        $this->requestConfig['associateTag']
	    );

        $this->xmlAmazonLink = $amzLink;
		//var_dump('<pre>', $this->get_xml_amazon_link(), '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		
		// return before requst: we only want to use the built request link
		if ( ! $execute ) {
			return true;
		}

        if ( function_exists('wp_remote_request') ) {
            $ret = wp_remote_request( $amzLink, array(
                'method'    => 'GET',
                'timeout'   => 30,
            ));
            $ret_ = 'valid';
            if ( is_wp_error($ret) ) {
                $ret_ = 'invalid';
                $ret = array('body' => $ret->get_error_message());
            }
            $ret = is_array($ret) && isset($ret['body']) ? $ret['body'] : '';
        } else {
            
            $input_params = array(
                'header'                        => false,
            );
            $output_params = array(
                'parse_headers'                 => false,
            );
            //$ret = file_get_contents( $amzLink );

            $ret = $this->the_plugin->curl( $amzLink, $input_params, $output_params, true );
            $ret_  = $ret['status'];
            $ret = is_array($ret) && isset($ret['data']) ? $ret['data'] : '';
        } 
 
        if ($ret_ == 'invalid') {
            return json_decode(json_encode(array('status' => $ret_, 'data' => $ret, 'msg' => $ret)),1);
        }
		return json_decode(json_encode((array)simplexml_load_string($ret)),1);
	}

    public function get_xml_amazon_link( $format_type='both' ) {
        $ret = array();
        $ret['normal'] = $this->xmlAmazonLink;
        $ret['format'] = str_replace("&", "\n", $this->xmlAmazonLink);
		
		if ( isset($ret["$format_type"]) ) {
			return $ret["$format_type"];
		}
        return $ret;
    }

	private function aws_signed_request($region, $params, $public_key, $private_key, $associate_tag=NULL, $version='2011-08-01') {
	    // some paramters
	    $method = 'GET';
	    $host = 'webservices.amazon.'.$region;
		//$host = 'sha256.webservices.amazon.'.$region;
	    $uri = '/onca/xml';

	    // additional parameters
	    $params['Service'] = 'AWSECommerceService';
	    $params['AWSAccessKeyId'] = $public_key;
	    // GMT timestamp
	    $params['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
	    // API version
	    $params['Version'] = $version;
	    if ($associate_tag !== NULL) {
	        $params['AssociateTag'] = $associate_tag;
	    }

	    // sort the parameters
	    ksort($params);

	    // create the canonicalized query
	    $canonicalized_query = array();
	    foreach ($params as $param=>$value)
	    {
	        $param = str_replace('%7E', '~', rawurlencode($param));
	        $value = str_replace('%7E', '~', rawurlencode($value));
	        $canonicalized_query[] = $param.'='.$value;
	    }
	    $canonicalized_query = implode('&', $canonicalized_query);

	    // create the string to sign
	    $string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;

	    // calculate HMAC with SHA256 and base64-encoding
	    $signature = base64_encode(hash_hmac('sha256', $string_to_sign, $private_key, TRUE));

	    // encode the signature for the request
	    $signature = str_replace('%7E', '~', rawurlencode($signature));

	    // create request
	    $request = 'http://'.$host.$uri.'?'.$canonicalized_query.'&Signature='.$signature;

	    return $request;
	}

	/**
	 * @param string $function Name of the function which should be called
	 * @param array $params Requestparameters 'ParameterName' => 'ParameterValue'
	 *
	 * @return array The response as an array with stdClass objects
	 */
	protected function performSoapRequest($function, $params)
	{		
        $this->webserviceEndpoint = str_replace(
            '%%PROTOCOL%%',
            strtolower( $this->protocol ),
            $this->webserviceEndpoint
        );
    
		try {
			$soapClient = new SoapClient(
				$this->webserviceWsdl,
				array(
					'exceptions' 	=> 1,
					'trace' 			=> 1
				)
			);
		} catch (SoapFault $fault) {
			return json_decode(json_encode(array('status' => 'invalid', 'msg' => "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", 'request_link' => $soapClient->__getLastRequest())),1);
		}

		try {   
			$soapClient->__setLocation(str_replace(
				'%%COUNTRY%%',
				$this->responseConfig['country'],
				$this->webserviceEndpoint
			));
		} catch (SoapFault $fault) {
			return json_decode(json_encode(array('status' => 'invalid', 'msg' => "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", 'request_link' => $soapClient->__getLastRequest())),1);
		}
   
		try {
			$soapClient->__setSoapHeaders($this->buildSoapHeader($function));  
			$response = $soapClient->__soapCall($function, array($params));
			return $response;
		} catch (SoapFault $fault) {
			return json_decode(json_encode(array('status' => 'invalid', 'msg' => "SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", 'request_link' => $soapClient->__getLastRequest())),1);
		}
	}

	/**
	 * Provides some necessary soap headers
	 *
	 * @param string $function
	 *
	 * @return array Each element is a concrete SoapHeader object
	 */
	protected function buildSoapHeader($function)
	{
		$timeStamp = $this->getTimestamp();
		$signature = $this->buildSignature($function . $timeStamp);

		return array(
			new SoapHeader(
				'http://security.amazonaws.com/doc/2007-01-01/',
				'AWSAccessKeyId',
				$this->requestConfig['accessKey']
			),
			new SoapHeader(
				'http://security.amazonaws.com/doc/2007-01-01/',
				'Timestamp',
				$timeStamp
			),
			new SoapHeader(
				'http://security.amazonaws.com/doc/2007-01-01/',
				'Signature',
				$signature
			)
		);
	}


	protected function performTheRequest($function, $params)
	{
	    $ret = null;

        // verify amazon request rate (per second)
        $this->verify_amazon_request_rate();

		if( $this->protocol == 'XML' ) {
			//var_dump('<pre>', 'xml', '</pre>'); die('debug...');
			$ret = $this->returnData(
				$this->performXMLRequest($function, $params)
			);
		}

		if( $this->protocol == 'SOAP' ) {

			// called here only to build the request link
			$this->performXMLRequest($function, $params, false);

			//var_dump('<pre>', 'soap', '</pre>'); die('debug...');  
			$ret = $this->returnData(
				$this->performSoapRequest($function, $params)
			);
		}

        // save amazon request time
        $this->save_amazon_request_time();

        return $ret;
	}

	/**
	 * provides current gm date
	 *
	 * primary needed for the signature
	 *
	 * @return string
	 */
	final protected function getTimestamp()
	{
		return gmdate("Y-m-d\TH:i:s\Z");
	}

	/**
	 * provides the signature
	 *
	 * @return string
	 */
	final protected function buildSignature($request)
	{

		return base64_encode(hash_hmac("sha256", $request, $this->requestConfig['secretKey'], true));
	}

	/**
	 * Basic validation of the nodeId
	 *
	 * @param integer $nodeId
	 *
	 * @return boolean
	 */
	final protected function validateNodeId($nodeId)
	{
		//if (false === is_numeric($nodeId) || $nodeId <= 0)
		if (false === is_numeric($nodeId))
		{
			throw new InvalidArgumentException(sprintf('Node has to be a positive Integer.'));
		}

		return true;
	}

	/**
	 * Returns the response either as Array or Array/Object
	 *
	 * @param object $object
	 *
	 * @return mixed
	 */
	protected function returnData($object)
	{
		switch ($this->responseConfig['returnType'])
		{
			case self::RETURN_TYPE_OBJECT:
				return $object;
			break;

			case self::RETURN_TYPE_ARRAY:
				return $this->objectToArray($object);
			break;

			default:
				throw new InvalidArgumentException(sprintf(
					"Unknwon return type %s", $this->responseConfig['returnType']
				));
			break;
		}
	}

	/**
	 * Transforms the responseobject to an array
	 *
	 * @param object $object
	 *
	 * @return array An arrayrepresentation of the given object
	 */
	protected function objectToArray($object)
	{
		$out = array();
		foreach ($object as $key => $value)
		{
			switch (true)
			{
				case is_object($value):
					$out[$key] = $this->objectToArray($value);
				break;

				case is_array($value):
					$out[$key] = $this->objectToArray($value);
				break;

				default:
					$out[$key] = $value;
				break;
			}
		}

		return $out;
	}

	/**
	 * set or get optional parameters
	 *
	 * if the argument params is null it will reutrn the current parameters,
	 * otherwise it will set the params and return itself.
	 *
	 * @param array $params the optional parameters
	 *
	 * @return array|aaAmazonWS depends on params argument
	 */
	public function optionalParameters($params = null)
	{
		if (null === $params)
		{
			return $this->responseConfig['optionalParameters'];
		}

		if (false === is_array($params))
		{
			throw new InvalidArgumentException(sprintf(
				"%s is no valid parameter: Use an array with Key => Value Pairs", $params
			));
		}

		$this->responseConfig['optionalParameters'] = $params;

		return $this;
	}

	/**
	 * Set or get the country
	 *
	 * if the country argument is null it will return the current
	 * country, otherwise it will set the country and return itself.
	 *
	 * @param string|null $country
	 *
	 * @return string|aaAmazonWS depends on country argument
	 */
	public function country($country = null)
	{
		if (null === $country)
		{
			return $this->responseConfig['country'];
		}

		if (false === in_array(strtolower($country), $this->possibleLocations))
		{
			throw new InvalidArgumentException(sprintf(
				"Invalid Country-Code: %s! Possible Country-Codes: %s",
				$country,
				implode(', ', $this->possibleLocations)
			));
		}

		$this->responseConfig['country'] = strtolower($country);

		return $this;
	}

	/**
	 * Setting/Getting the amazon category
	 *
	 * @param string $category
	 *
	 * @return string|aaAmazonWS depends on category argument
	 */
	public function category($category = null)
	{
		if (null === $category)
		{
			return isset($this->requestConfig['category']) ? $this->requestConfig['category'] : null;
		}

		$this->requestConfig['category'] = $category;

		return $this;
	}

	/**
	 * Setting/Getting the responsegroup
	 *
	 * @param string $responseGroup Comma separated groups
	 *
	 * @return string|aaAmazonWS depends on responseGroup argument
	 */
	public function responseGroup($responseGroup = null)
	{
		if (null === $responseGroup)
		{
			return $this->responseConfig['responseGroup'];
		}

		$this->responseConfig['responseGroup'] = $responseGroup;

		return $this;
	}

	/**
	 * Setting/Getting the returntype
	 * It can be an object or an array
	 *
	 * @param integer $type Use the constants RETURN_TYPE_ARRAY or RETURN_TYPE_OBJECT
	 *
	 * @return integer|aaAmazonWS depends on type argument
	 */
	public function returnType($type = null)
	{
		if (null === $type)
		{
			return $this->responseConfig['returnType'];
		}

		$this->responseConfig['returnType'] = $type;

		return $this;
	}

	/**
	 * Setter/Getter of the AssociateTag.
	 * This could be used for late bindings of this attribute
	 *
	 * @param string $associateTag
	 *
	 * @return string|aaAmazonWS depends on associateTag argument
	 */
	public function associateTag($associateTag = null)
	{
		if (null === $associateTag)
		{
			return $this->requestConfig['associateTag'];
		}

		$this->requestConfig['associateTag'] = $associateTag;

		return $this;
	}

	/**
	 * @deprecated use returnType() instead
	 */
	public function setReturnType($type)
	{
		return $this->returnType($type);
	}

	/**
	 * Setting the resultpage to a specified value.
	 * Allows to browse resultsets which have more than one page.
	 *
	 * @param integer $page
	 *
	 * @return aaAmazonWS
	 */
	public function page($page)
	{
		if (false === is_numeric($page) || $page <= 0)
		{
			throw new InvalidArgumentException(sprintf(
				'%s is an invalid page value. It has to be numeric and positive',
				$page
			));
		}

		$this->responseConfig['optionalParameters'] = array_merge(
			$this->responseConfig['optionalParameters'],
			array("ItemPage" => $page)
		);

		return $this;
	}

	public function get_lastCart() {
		return $this->__lastCart_;
	}
}
} // end class exists!