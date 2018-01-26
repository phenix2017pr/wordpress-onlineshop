<?php
/**
 * HOW TO MAKE A REQUEST:
 * /wp-content/plugins/woozonelite/_utils/api-response.php?country=com&asin=B0012Y0BIE
 */

if ( !defined('ABSPATH') ) {
	$absolute_path = __FILE__;
	$path_to_file = explode( 'wp-content', $absolute_path );
	$path_to_wp = $path_to_file[0];

	/** Set up WordPress environment */
	require_once( $path_to_wp.'/wp-load.php' );
} else {
	die('wrong path!');
}

$countries = array('com', 'ca', 'cn', 'de', 'in', 'it', 'es', 'fr', 'co.uk', 'co.jp');

    function _escape($str)
    {
        return preg_replace("!([\b\t\n\r\f\"\\'])!", "\\\\\\1", $str);
    };

// php.net / bohwaz / This is intended to be a simple readable json encode function for PHP 5.3+ (and licensed under GNU/AGPLv3 or GPLv3 like you prefer)
function json_readable_encode($in, $indent = 0, $from_array = false)
{
    $_myself = __FUNCTION__;

    $out = '';

    foreach ($in as $key=>$value)
    {
        $out .= str_repeat("\t", $indent + 1);
        $out .= "\""._escape((string)$key)."\": ";

        if (is_object($value) || is_array($value))
        {
            $out .= "\n";
            $out .= $_myself($value, $indent + 1);
        }
        elseif (is_bool($value))
        {
            $out .= $value ? 'true' : 'false';
        }
        elseif (is_null($value))
        {
            $out .= 'null';
        }
        elseif (is_string($value))
        {
            $out .= "\"" . _escape($value) ."\"";
        }
        else
        {
            $out .= $value;
        }

        $out .= ",\n";
    }

    if (!empty($out))
    {
        $out = substr($out, 0, -2);
    }

    $out = str_repeat("\t", $indent) . "{\n" . $out;
    $out .= "\n" . str_repeat("\t", $indent) . "}";

    return $out;
}

function amzProdResp() {
		global $WooZoneLite, $countries;

		$asin = isset($_REQUEST['asin']) ? htmlentities($_REQUEST['asin']) : '';
		$country = isset($_REQUEST['country']) ? htmlentities($_REQUEST['country']) : 'com';
		if ( !in_array($country, $countries) ) $country = 'com';

		//$WooZoneLite->amzHelper->setupAmazonWS($country);

		$provider = 'amazon';
		$rsp = $WooZoneLite->get_ws_object( $provider )->api_make_request(array(
			'amz_settings'			=> $WooZoneLite->amz_settings,
			'from_file'				=> str_replace($WooZoneLite->cfg['paths']['plugin_dir_path'], '', __FILE__),
			'from_func'				=> __FUNCTION__ != __METHOD__ ? __METHOD__ : __FUNCTION__,
			'requestData'			=> array(
				'asin'					=> $asin,
			),
			'optionalParameters'	=> array(),
			'responseGroup'			=> 'Large,ItemAttributes,OfferFull,Variations,PromotionSummary',
			'method'				=> 'lookup',
		));
		$product = $rsp['response'];

		// create new amazon instance
		//$aaAmazonWS = $WooZoneLite->amzHelper->aaAmazonWS;
		// create request by ASIN
		// Large,ItemAttributes,Offers,OfferSummary,OfferFull,Variations,VariationOffers,Reviews,PromotionSummary
		//$product = $aaAmazonWS->responseGroup('Large,ItemAttributes,OfferFull,Variations,PromotionSummary')
		//->optionalParameters(array('MerchantId' => 'All'))
		//->lookup($asin);

		//$product = serialize( $product );
		//$product = json_encode( $product );
		$product = json_readable_encode( $product );
		//highlight_string( serialize( $product ) ); die;
		//var_dump('<pre>', json_encode( $product ), '</pre>'); die('debug...');

?>

<html>
<head>
	<title>amazon response</title>
	
	<?php /*
	<link rel="stylesheet" type="text/css" href="pretty-json.css" />
 
	<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
	<script type="text/javascript" src="jquery-1.11.1.min.js"></script>
	<script type="text/javascript" src="underscore-min.js"></script>
	<script type="text/javascript" src="backbone-min.js"></script>
	<script type="text/javascript" src="pretty-json-min.js"></script>
	
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/styles/default.min.css">
	<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.4/highlight.min.js"></script>
	*/ ?>
	
	<?php /*<link rel="stylesheet" type="text/css" href="lib/school_book.css" />
 	<script type="text/javascript" src="lib/highlight.pack.js"></script>

	<style type="text/css">
		#container {
			margin: 0 auto;
			width: 100%;
			height: auto;
			border: 1px solid green;
			overflow: hidden;
			overflow-y: auto;
		}
	</style>
	<script type="text/javascript">
		hljs.configure({
			tabReplace		: '    ' // 4 spaces
		});
		hljs.initHighlightingOnLoad();
	</script>*/ ?>
</head>
<body>
	<div id="container">
		<pre><code class="json"><?php echo $product; ?></code></pre>
	</div>
</body>
</html>
<?php
}
amzProdResp();