<?php

/**
 * Use this to define the SQAURORACHD_CONFIG to use
 */
define('SQAURORACHD_ENVIRONMENT', 'dev');

/**
 * Config
 */
define('SQAURORACHD_CONFIG', serialize([
    'dev' => [
        // Display Error Details
        // usefull for debugging, set to false for production
        "displayErrorDetails" => true,
        // Logging
        "logging"   => [
            // Log level, valid levels are:
            // DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT and EMERGENCY
            "loglevel" => "DEBUG",
            // Log location, including trailing slash
            "folder"    => __DIR__ . "/../logs"
        ],
        "db"    => [
            "driver"    =>  "mysql",
            "host"      =>  "localhost",
            "database"  =>  "sqauroracdh",
            "username"  =>  "root",
            "password"  =>  "123",
            "charset"   =>  "utf8",
            "collation" =>  "utf8_unicode_ci",
            "prefix"    =>  "",
            // "timezone"  =>  "Europe/Amsterdam" // Optional setting to force the database to use this timezone
                                                  // note that for this to work the mysql time_zone_* tables need to have
                                                  // correct information.
                                                  // see: https://dev.mysql.com/doc/refman/8.0/en/time-zone-support.html
                                                  // (mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u <user> -p mysql)
        ],
        // Twig Settings
        "twigSettings"    => [
            // cache folder, comment out to disable the template cache.
            // Make sure the webserver has write permissions to this directoy
            // "cache" => __DIR__ .'/../app/twigcache'
            'debug' => true,
        ],
        // Cache settings
        "cacheSettings" => [
            'folder' =>   __DIR__ .'/../app/cache',
            'defaultLifetime'   => 3600
        ],
        // Auth settings
        "ticketExpires" => 3600,
        // Backing folder
        "backing" => [
            "folder" => __DIR__ . "/../Temp/InceptionExport/backing"
        ],
        "storage" => [
            "folder" => __DIR__ . "/../Temp/InceptionExport/storage"
        ],
        "ignoreTopicOwner"  => true,
        // Folder where wkhtmltopdf executable is stored
        "wkhtmltopdfPath"   => '/usr/local/bin',
        // Image magick path used by the Image post process module
        "imageMagickPath"   => 'C:/Program Files/ImageMagick-7.1.0-Q16-HDRI',
        // Purge all queue entries which have been processed more the x seconds ago
        "queuePurge" => 60*15,
        "transport" =>[
            "sns" => [
                "purge" => 60*30,
            ],
        ]
    ],'test' => [

        // Display Error Details
        // usefull for debugging, set to false for production
        "displayErrorDetails" => true,
        // Logging
        "logging"   => [
            // Log level, valid levels are:
            // DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT and EMERGENCY
            "loglevel" => "DEBUG",
            // Log location, including trailing slash
            "folder"    => "/wwdata/servicespqcloud-tst/debug/"
        ],
        "db"    => [
            "driver"    =>  "mysql",
            "host"      =>  "pq-acc.cmq8dtpsycxn.eu-central-1.rds.amazonaws.com",
            "database"  =>  "services_publiqare_test",
            "username"  =>  "dbuser_services",
            "password"  =>  "!@service#$",
            "charset"   =>  "utf8",
            "collation" =>  "utf8_unicode_ci",
            "prefix"    =>  ""
        ],
        // Twig Settings
        "twigSettings"    => [
            // cache folder, comment out to disable the template cache.
            // Make sure the webserver has write permissions to this directoy
            // "cache" => __DIR__ .'/../data/twigcache',
            'debug' => false,
        ],
        // Cache settings
        "cacheSettings" => [
            'folder' =>  '/cdhdata/test/cache',
            'defaultLifetime'   => 14400
        ],
        // Auth settings
        "ticketExpires" => 14400,
        // Backing folder
        "backing" => [
            "folder" =>  "/cdhdata/test/backing"
        ],
        "storage" => [
            "folder" =>  "/cdhdata/test/storage"
        ],
        "ignoreTopicOwner"  => true,
        // Folder where wkhtmltopdf executable is stored
        //"wkhtmltopdfPath"   => '/opt/vhosts/servicespqcloud-acc/htdocs/includes/Sqare/Aurora/Cdh/Export/Channel/PreProcess/wkhtmltopdf/bin',
        "wkhtmltopdfPath"   => '/usr/local/bin',
        // Image magick path used by the Image post process module
        "imageMagickPath"   => '/usr/bin',
        // Purge all queue entries which have been processed more the x seconds ago
        "queuePurge" => 86400 * 7 , // one week
        "transport" =>[
            "sns" => [
                "purge" => 60*30,
            ],
        ]
    ],
    'production' => [

        // Display Error Details
        // usefull for debugging, set to false for production
        "displayErrorDetails" => true,
        // Logging
        "logging"   => [
            // Log level, valid levels are:
            // DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT and EMERGENCY
            "loglevel" => "DEBUG",
            // Log location, including trailing slash
            "folder"    => __DIR__ . "/../data/logs"
        ],
        "db"    => [
            "driver"    =>  "mysql",
            "host"      =>  "pqcloud.cmq8dtpsycxn.eu-central-1.rds.amazonaws.com",
            "database"  =>  "services_publiqare_production",
            "username"  =>  "dbuser_services",
            "password"  =>  "!@service#$",
            "charset"   =>  "utf8",
            "collation" =>  "utf8_unicode_ci",
            "prefix"    =>  ""
        ],
        // Twig Settings
        "twigSettings"    => [
            // cache folder, comment out to disable the template cache.
            // Make sure the webserver has write permissions to this directoy
             "cache" => __DIR__ .'/../app/twigcache',
            'debug' => false,
        ],
        // Cache settings
        "cacheSettings" => [
            'folder' =>   __DIR__ .'/../app/cache',
            'defaultLifetime'   => 3600
        ],
        // Auth settings
        "ticketExpires" => 14400,
        // Backing folder
        "backing" => [
            "folder" => __DIR__ . "/../data/backing"
        ],
        "storage" => [
            "folder" => __DIR__ . "/../data/storage"
        ],
        "ignoreTopicOwner"  => true,
        // Folder where wkhtmltopdf executable is stored
        "wkhtmltopdfPath"   => '/opt/vhosts/servicespqcloud-prod/htdocs/includes/Sqare/Aurora/Cdh/Export/Channel/PreProcess/wkhtmltopdf/bin',
        // Image magick path used by the Image post process module
        "imageMagickPath"   => '/usr/local/bin',
        // Purge all queue entries which have been processed more the x seconds ago
        "queuePurge" => 86400 * 7 , // one week
        "transport" =>[
            "sns" => [
                "purge" => 60*30,
            ],
        ]
    ]
]));


// Please don't change anything below this line
require_once "constants.php";