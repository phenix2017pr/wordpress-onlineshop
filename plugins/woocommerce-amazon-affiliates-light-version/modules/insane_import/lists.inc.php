<?php

global $WooZoneLite;

$WooZoneLite_search_params = array();

// MinimumPrice & MaximumPrice
$WooZoneLite_search_params['MinimumPrice'] = array(
	"" 	        => "None",
	"100"		=> "£1.00",
	"200"		=> "£2.00",
	"500"		=> "£5.00",
	"1000"	    => "£10.00",
	"2000"	    => "£20.00",
	"5000"	    => "£50.00",
	"10000"	    => "£100.00",
	"20000"	    => "£200.00",
	"50000"	    => "£500.00",
	"any"	    => "Any"
);
$WooZoneLite_search_params['MaximumPrice']            = $WooZoneLite_search_params['MinimumPrice'];

// Condition
$WooZoneLite_search_params['Condition'] = array(
	"" 				=> "All Conditions",
	"New" 			=> "New",
	"Used" 			=> "Used",
	"Collectible" 	=> "Collectible",
	"Refurbished" 	=> "Refurbished",
);

// MinPercentageOff
$WooZoneLite_search_params['MinPercentageOff'] = array(
	"" 		    => "All Min Percentage Off",
	"10"		=> "10%",
	"20"		=> "20%",
	"30"		=> "30%",
	"40"		=> "40%",
	"50"		=> "50%",
	"60"		=> "60%",
	"70"		=> "70%",
	"80"		=> "80%",
	"90"		=> "90%",
	"100"		=> "100%",
);

// Sort
$WooZoneLite_search_params['Sort'] = array();
$WooZoneLite_search_params['Sort']['relevancerank'] = 'Relevance rank.';
$WooZoneLite_search_params['Sort']['salesrank'] = "Best selling";
$WooZoneLite_search_params['Sort']['pricerank'] = "Price: low to high";
$WooZoneLite_search_params['Sort']['inverseprice'] = "Price: high to low";
$WooZoneLite_search_params['Sort']['launch-date'] = "Newest arrivals: low to high";
$WooZoneLite_search_params['Sort']['-launch-date'] = "Newest arrivals: high to low";
$WooZoneLite_search_params['Sort']['sale-flag'] = "On sale";
$WooZoneLite_search_params['Sort']['pmrank'] = "Featured items";
$WooZoneLite_search_params['Sort']['price'] = "Price: low to high";
$WooZoneLite_search_params['Sort']['-price'] = "Price: high to low";
$WooZoneLite_search_params['Sort']['reviewrank'] = "Average customer review: high to low";
$WooZoneLite_search_params['Sort']['titlerank'] = "Alphabetical: A to Z";
$WooZoneLite_search_params['Sort']['-titlerank'] = "Alphabetical: Z to A";
$WooZoneLite_search_params['Sort']['pricerank'] = "Price: low to high";
$WooZoneLite_search_params['Sort']['inverse-pricerank'] = "Price: high to low";
$WooZoneLite_search_params['Sort']['daterank'] = "Publication date: newer to older";
$WooZoneLite_search_params['Sort']['psrank'] = "Bestseller ranking - projected sales.";
$WooZoneLite_search_params['Sort']['orig-rel-date'] = "Release date: newer to older";
$WooZoneLite_search_params['Sort']['-orig-rel-date'] = "Release date: older to newer";
$WooZoneLite_search_params['Sort']['releasedate'] = "Release date: newer to older";
$WooZoneLite_search_params['Sort']['-releasedate'] = "Release date: older to newer";
$WooZoneLite_search_params['Sort']['songtitlerank'] = "Most popular";
$WooZoneLite_search_params['Sort']['uploaddaterank'] = "Date added";
$WooZoneLite_search_params['Sort']['-video-release-date'] = "Release date: newer to older";
$WooZoneLite_search_params['Sort']['-edition-sales-velocity'] = "Quickest to slowest selling products.";
$WooZoneLite_search_params['Sort']['subslot-salesrank'] = "Bestselling";
$WooZoneLite_search_params['Sort']['release-date'] = "Latest release date: from newer to older.";
$WooZoneLite_search_params['Sort']['-age-min'] = "Age: high to low";

$WooZoneLite_search_params_sort = $WooZoneLite_search_params['Sort'];
$WooZoneLite_search_params_sort["relevancerank"] = 'Items ranked according to the following criteria: how often the keyword appears in the description, where the keyword appears (for example, the ranking is higher when keywords are found in titles), how closely they occur in descriptions (if there are multiple keywords), and how often customers purchased the products they found using the keyword.';
$WooZoneLite_search_params_sort["psrank"] = 'Bestseller ranking taking into consideration projected sales. The lower the value, the better the sales.';
$WooZoneLite_search_params_sort["release-date"] = 'Sorts by the latest release date from newer to older. See orig-rel-date, which sorts by the original release date.';

// Params description
$WooZoneLite_search_params_desc = array(
    'Sort'              => 'An optional parameter <br />Means by which the items in the response are ordered.',
    'BrowseNode'        => 'An optional parameter <br />Browse nodes are identify items categories',
    'Brand'             => 'An optional parameter <br />Name of a brand associated with the item. You can enter all or part of the name. For example, Timex, Seiko, Rolex.',
    'Condition'         => 'An optional parameter <br />Use the Condition parameter to filter the offers returned in the product list by condition type. By default, Condition equals "New". If you do not get results, consider changing the value to "All. When the Availability parameter is set to "Available," the Condition parameter cannot be set to "New."',
    'Manufacturer'      => 'An optional parameter <br />Name of a manufacturer associated with the item. You can enter all or part of the name.',
    'MaximumPrice'      => 'An optional parameter <br />Specifies the maximum price of the items in the response. Prices are in terms of the lowest currency denomination, for example, pennies. For example, 3241 represents $32.41.',
    'MinimumPrice'      => 'An optional parameter <br />Specifies the minimum price of the items to return. Prices are in terms of the lowest currency denomination, for example, pennies, for example, 3241 represents $32.41.',
    'MerchantId'        => 'An optional parameter <br/>You can use to filter search results and offer listings to only include items sold by Amazon. By default, Product Advertising API returns items sold by various merchants including Amazon. Use the Amazon to limit the response to only items sold by Amazon. Valid values include: All, Amazon, Featured, FeaturedBuyBoxMerchant.',
    'MinPercentageOff'  => 'An optional parameter <br />Specifies the minimum percentage off for the items to return.',
);

?>