<?php


namespace Sqare\Aurora\Cdh\Utils;


use Monolog\Formatter\LineFormatter;
use Monolog\Logger as Monolog;
use Slim\Container;

abstract class Logger
{

    public static function setLogFolder($container, &$logger , $logFolder = ""){
        $logger->popHandler();
        $instanceUid = $container->get('instanceUid');

        $folder = rtrim($container->get('settings')['logging']['folder'],'/');
        if(!empty($logFolder)){
            $folder = $folder . '/' . $logFolder;
        }

        $formatter = new LineFormatter(
            "[%datetime%]\t".$instanceUid."\t[%level_name%]\t%message%\n",
            "Y-m-d H:i:s.u",
            true
        );

        $file_handler = new \Monolog\Handler\RotatingFileHandler(
            $folder ."/api.log"
        );

        $file_handler->setFormatter($formatter)
            ->setLevel(constant("Monolog\Logger::".$container->get('settings')['logging']['loglevel']));

        $logger->pushHandler($file_handler);
    }

}