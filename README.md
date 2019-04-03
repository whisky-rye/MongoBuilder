# MongoBuilder

- Simple package MongoDB driver for PHP

**Install** 
- 1.composer require mysticzhong/mongobuilder

- 2.add line in ./vendor/composer/autoload_classmap.php
    'mysticzhong\\mongobuilder' => $vendorDir . '/mysticzhong/mongobuilder/src/mongobuilder.php',

- 3.add line in ./vendor/composer/autoload_psr4.php
    'mysticzhong\\' => array($vendorDir . '/mysticzhong/mongobuilder/src'),

- 4.add line in ./vendor/composer/autoload_static.php
    'mysticzhong\\mongobuilder' => __DIR__ . '/..' . '/mysticzhong/mongobuilder/src/mongobuilder.php',

- step 2-4, maybe up to v1.0.2 can automatic solve it.


**Usage**
- Some Example


    - $MongoBuilder = new \extend\MongoBuilder(Config::get('mongo'));
    - $table = 'msgTrackPoint';
    - // query 
    - $queryData['UserID'] = $MyID;
    - $c = $MongoBuilder->filterQuery($table, $queryData);
    - // insert 
    - $logData['lastHeadimgUrl'] = Request::instance()->domain() . "/?uid=" . $TouchID . "&t=" . time();
    - $logData['UserID'] = $MyID;
    - $logData['Nums'] = 1;
    - $MongoBuilder->doInsertOne($table, $logData);
    - // update 
    - $logData2['lastHeadimgUrl'] = Request::instance()->domain() . "/?uid=" . $TouchID . "&t=" . time();
    - $logData2['Nums'] = $c[0]['Nums'] + 1;
    - $MongoBuilder->doUpdate($table, ['UserID' => $MyID], $logData2, false);

 
[![LICENSE](https://img.shields.io/badge/license-NPL%20(The%20996%20Prohibited%20License)-blue.svg)](https://github.com/996icu/996.ICU/blob/master/LICENSE)
[![996.icu](https://img.shields.io/badge/link-996.icu-red.svg)](https://996.icu)






