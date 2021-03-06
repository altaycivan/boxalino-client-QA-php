<?php

/**
 * In this example, we take a very simple XML file with product data, generate the specifications, load them, publish them and push the data to Boxalino Data Intelligence
 */

//include the Boxalino Client SDK php files
$libPath = '../lib'; //path to the lib folder with the Boxalino Client SDK and PHP Thrift Client files
require_once(__DIR__ . '/../vendor/autoload.php');
use com\boxalino\bxclient\v1\BxClient;
use com\boxalino\bxclient\v1\BxData;
BxClient::LOAD_CLASSES($libPath);

//required parameters you should set for this example to work
//$account = ""; // your account name
//$password = ""; // your account password
$domain = ""; // your web-site domain (e.g.: www.abc.com)
$languages = array('en'); //declare the list of available languages
$isDev = false; //are the data to be pushed dev or prod data?
$isDelta = false; //are the data to be pushed full data (reset index) or delta (add/modify index)?
$logs = array(); //optional, just used here in example to collect logs

//Create the Boxalino Data SDK instance
$bxData = new BxData(new BxClient($account, $password, $domain), $languages, $isDev, $isDelta);

try {

    $file = '../sample_data/products.xml'; //xml file of all the products
    $itemIdColumn = 'id'; //the element of the xml with the unique id of each item
    $xPath = '/products/product'; //path from the root to the products

    //add a xml file as main product file
    $sourceKey = $bxData->addMainXMLItemFile($file, $itemIdColumn, $xPath);

    //this part is only necessary to do when you push your data in full, as no specifications changes should not be published without a full data sync following next
    //even when you publish your data in full, you don't need to repush your data specifications if you know they didn't change, however, it is totally fine (and suggested) to push them everytime if you are not sure if something changed or not
    if(!$isDelta) {

        //declare the fields
        $bxData->addSourceTitleField($sourceKey, array("en"=>"name/translation[@locale='en']"));
        $bxData->addSourceDescriptionField($sourceKey, array("en"=>"description/translation[@locale='en']",));
        $bxData->addSourceListPriceField($sourceKey, "list_price");
        $bxData->addSourceDiscountedPriceField($sourceKey, "discounted_price");
        $bxData->addSourceLocalizedTextField($sourceKey, "short_description", array("en"=>"short_description/translation[@locale='en']"));
        $bxData->addSourceStringField($sourceKey, "sku", "sku");

        $logs[] = "publish the data specifications";
        $bxData->pushDataSpecifications();

        $logs[] = "publish the api owner changes"; //if the specifications have changed since the last time they were pushed
        $bxData->publishChanges();
    }

    $logs[] = "push the data for data sync";
    $bxData->pushData();
    if(!isset($print) || $print) {
        echo implode("<br/>", $logs);
    }

} catch(\Exception $e) {

    //be careful not to print the error message on your publish web-site as sensitive information like credentials might be indicated for debug purposes
    $exception = $e->getMessage();
    if(!isset($print) || $print) {
        echo $exception;
    }
}
