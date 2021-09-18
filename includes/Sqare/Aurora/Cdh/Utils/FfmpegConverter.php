<?php

namespace Sqare\Aurora\Cdh\Utils;

class FfmpegConverter
{
    /**
     * @var array
     */
    private array $config;

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): FfmpegConverter
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param $command
     * @param $assetToConvert
     * @param $storePath
     * @return string|null
     */
    public function formatVideo($command, $assetToConvert, $storePath)
    {
        if ($this->config['path']) {
            $fileName = basename($assetToConvert);
            $videoOutputPath = $storePath. DIRECTORY_SEPARATOR . $fileName;
            $command = str_replace('[filePath]', $assetToConvert, $command);
            $command = str_replace('[convertedFileName]',$videoOutputPath , $command);
            error_log("+++++ debug purposes: " . $command);
            system($command);
            return  $videoOutputPath;
        }
        return null;
    }
}