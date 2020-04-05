# Mage2 Module Ktd LogHandler

    ``ktd/custom-log``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Improve the log for magento 2 with custom format

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Ktd`
 - Enable the module by running `php bin/magento module:enable Ktd_LogHandler`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require ktd/custom-log`
 - enable the module by running `php bin/magento module:enable Ktd_LogHandler`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Use

    
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
       /** @var \Ktd\LogHandler\Helper\Data $log */
    $log = $objectManager->get('\Ktd\LogHandler\Helper\Data');
    
    $log->setFuncDebug('saleorder'); //set filename
    $logType = 'ORDER';
    $logApiType = 'SO-ORDER';
    
    $req = ['sample' => 1];
    $res = ['success' => 1];
    
    $apiname = 'http://URL_API';
    $req = json_encode($req);
    $res = json_encode($res);
    
    $log->debuglog($logType, array(
        $logApiType,
        "API : $apiname",
        "REQ : $req",
        "RES : $res"
    ));


## Result in log 

    Log path: MAGENTO_ROOT_FOLDER/var/log/api/saleorder_debug_2020-04-05.log
    
    2020-04-05 13:08:25|DEBUG|8aprmocv1b38g4n3d809m8pr79|ORDER|SO-ORDER|API : http://URL_API|REQ : {"sample":1}|RES : {"success":1}




