<?php
/**
 * @copyright    PubliQare BV All Rights Reserved.
 * @author        Ricardo ricardo.parada@publiqare.com
 */

namespace Sqare\Aurora\Cdh\Export\Transport;

use DOMNode;
use Exception;
use Guzzle\Common\Exception\GuzzleException;
use GuzzleHttp\Client;
use Slim\Container;
use Sqare\Aurora\Cdh\Export\Channel\AbstractChannel;
use Sqare\Aurora\Cdh\Export\Product;
use Sqare\Aurora\Cdh\Export\Transport\Hootsuite\HootsuiteClient;
use Sqare\Aurora\Cdh\Export\Transport\Hootsuite\HootsuiteLocator;
use Sqare\Aurora\Cdh\Utils\FfmpegConverter;

/*
 *  DATABASE: sqauroracdh
 *
 *  INSERT INTO `transport` (`id`, `name`, `displayname`, `type`, `created_at`) VALUES (NULL,
 *  'hootsuite', 'Hootsuite Transport Module', 'Hootsuite', '0000-00-00 00:00:00.000000');
 */

class Hootsuite extends AbstractTransport
{
    public function __construct(Container $Container, Product $Product, AbstractChannel $Channel, $mappingId, $transportMappingId)
    {
        $this->Container = $Container;
        $this->Product = $Product;
        $this->Channel = $Channel;
        $this->mappingId = $mappingId;
        $this->transportMappingId = $transportMappingId;
    }

    protected array $requiredConfigProperties = [
        'client_id',
        'client_secret',
        'member_id',
        'base_uri',
        'socialmedia_profiles'
    ];

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * @var HootsuiteClient
     */
    private HootsuiteClient $client;

    /**
     * @param array $params
     * @throws Exception
     */

    //  1) Store and get configuration options inside class
    //  2) Do some validation

    public function setConfig($params): void
    {
        //initialize debug message
        $this->Container->get('logger')->debug("+++++++++ Hootsuite started");

        if (!is_array($params)) {
            throw new \Exception("Expecting an array as params property value");
        }

        // required config params
        if (!$this->hasRequiredConfigProperties($params, $missing)) {
            throw new \Exception(sprintf("Missing required parameter: %s", $missing));
        }

        // making sure primary_image_cmd is correctly set
        $params['primary_image_cmd'] = strtoupper($params['primary_image_cmd']);
        if (substr($params['primary_image_cmd'], 0, 2) != 'C_') {
            $params['primary_image_cmd'] = 'C_' . $params['primary_image_cmd'];
        }

        // checking if ffmpeg path is correctly set
        if (
            isset($params['ffmpeg']['path']) &&
            (!file_exists($params['ffmpeg']['path']) || !is_executable($params['ffmpeg']['path']))
        ) {
            throw new \Exception("FFMpeg part not set or executable.");
        }

        $this->config = $params;

        //listing hootsuite transport module set up params on the CDH UI
        $this->Container->get('logger')->debug("List of set up params: " . json_encode($params, true));
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     */
    public function process(): void
    {
        /**
         * This is going to create an array like this:
         *
         * Array
         * (
         *      [images] => Array
         *      (
         *          [image] => 2822
         *      )
         *      [text] => This is the headline
         *          This is the Into
         * )
         */
        $xml = simplexml_load_file($this->Channel->getOutputPath(), 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        $extractedDataFromArticle = json_decode($json, true);
        $this->Container->get('logger')->debug(sprintf("+++XML %s", print_r($extractedDataFromArticle, true)));

        $httpClient = new Client([
            'base_uri' => $this->config['base_uri'],
            'verify' => false
        ]);

        $ipLocator = new HootsuiteLocator();
        $hootsuiteClient = new HootsuiteClient($this->Container, $httpClient, $ipLocator);


        $hootsuiteClient->setBaseUri($this->config['base_uri'])
            ->setHootsuiteClientId($this->config['client_id'])
            ->setHootsuiteClientSecret($this->config['client_secret'])
            ->setHootsuiteClientMemberId($this->config['member_id'])
            ->setHootsuitePrimaryImage($this->config['primary_image_cmd'])
            ->setHootsuiteSocialmediaProfiles($this->config['socialmedia_profiles']);

        $this->client = $hootsuiteClient;

        $hootsuiteClient->getAccessToken();
        $hootsuiteClient->me();
        $hootsuiteClient->getSocialMediaProfiles();

        //  gives a list of images, either they are already converted
        //  otherwise this will return the default images already stored
        $this->loadImages();
        $this->Container->get('logger')->debug(sprintf("++++Images:\n%s", print_r($this->images, true)));

        if ($this->images) {
            $i = 0;
            foreach ($this->images as $image) {
                $imageSourcePath = $this->Product->Backingfolder->getStoryFolder()
                    . DIRECTORY_SEPARATOR . 'article' . DIRECTORY_SEPARATOR . $image['href'];
                $imageId = preg_replace('/\D/', '', $image['href']);
                $this->Container->get('logger')->debug(sprintf("+++File ID: %s", $imageId));
                $this->Container->get('logger')->debug(sprintf("+++Image source path: %s", $imageSourcePath));
                if (count($extractedDataFromArticle['images']['image']) == 1) {
                    foreach ($extractedDataFromArticle['images'] as $imagesImage) {
                        {
                            if ($imageId == $imagesImage) {
                                $this->Container->get('logger')->debug(
                                    sprintf("+++This image is toggled on ContentStation: %s",
                                        $imageId)
                                );
                                $this->toggledImage[$i] = $imageSourcePath;
                                $i++;
                            }
                        }
                    }
                } else {
                    foreach ($extractedDataFromArticle['images']['image'] as  $imagesImage) {
                        if ($imageId == $imagesImage) {
                            $this->Container->get('logger')->debug(
                                sprintf("+++This image is toggled on ContentStation: %s",
                                    $imageId)
                            );
                            $this->toggledImage[$i] = $imageSourcePath;
                            $i++;
                        }
                    }
                }
            }
        }
        $this->Container->get('logger')->debug(sprintf("+++Images array: \n%s",
            print_r($this->toggledImage, true)));

        $ffmpeg = new FfmpegConverter($this->config['ffmpeg']);
        $ffmpeg->setConfig($this->config['ffmpeg']);
        // Get assets
        // Collects video files found from dossier
        // Download them from StudioServer to the backing folder in an asset folder
        $assets = $this->Product->getChannelData($this->mappingId, 'assets');
        count($assets) > 0 ?:
            $this->Container->get('logger')->debug(sprintf("+++Assets:\n%s", print_r($assets, true)));

        $assetToUpload = null;
        if(count(  $this->toggledImage) > 0)
        {
            $assetToUpload = $this->toggledImage[0];
            $this->Container->get('logger')->debug(sprintf("+++Asset selected is an image %s", $assetToUpload));
        }

        if(!$this->toggledImage)
        {
            $assetToUpload = $assets[0];
            $this->Container->get('logger')->debug(sprintf("+++Asset selected is a video %s", $assetToUpload));
        }

        if (isset($assetToUpload)) {
            $this->Container->get('logger')->debug(sprintf("+++Asset selected is still: %s", $assetToUpload));
            foreach ($this->config['socialmedia_profiles'] as $key => $socialmedia) {
                if( (pathinfo($assetToUpload, PATHINFO_EXTENSION) == 'mp4') || (pathinfo($assetToUpload, PATHINFO_EXTENSION) == 'mov') )
                {
                    $fileToUpload = 'video';
                    $formatVideo = $this->config['ffmpeg']['format_video'][$key];
                    $this->Container->get('logger')->debug(sprintf("+++Asset current path: \n%s", print_r($assetToUpload, true)));

                    $formattedFilePath = $this->Product->Backingfolder->getStoryFolder()
                        . DIRECTORY_SEPARATOR . 'article' . DIRECTORY_SEPARATOR
                        . 'converted_videos' . DIRECTORY_SEPARATOR . $key;
                    if(!file_exists($formattedFilePath) && !mkdir($formattedFilePath, 0777, true))
                    {
                        throw new Exception(sprintf("Could not create folder: %s", $formattedFilePath));
                    }
                    $converted = $ffmpeg->formatVideo(
                        $formatVideo,
                        $assetToUpload,
                        $formattedFilePath
                    );
                }
                $fileToUpload == 'video' ? $fileToUpload = $converted : $fileToUpload = $this->toggledImage[0];
                $this->postWithMedia($extractedDataFromArticle['text'],
                    $socialmedia,
                    '2021-09-10T16:40:00Z',
                    $fileToUpload,
                );
            }
        } else {
            $this->sendText($extractedDataFromArticle['text'],
                $this->config['socialmedia_profiles'],
                '2021-09-10T16:40:00Z'
            );
        }
    }

    /**
     * @param string|array $text
     * @param array $socialMediaProfiles
     * @param string $scheduleSendTime
     */
    public
    function sendText(string $text, array $socialMediaProfiles, string $scheduleSendTime)
    {
        $this->client->getAccessToken();
        $this->client->post($text, $socialMediaProfiles, $scheduleSendTime);
    }

    /**
     * @param string|array $text
     * @param int $socialMediaProfileId
     * @param string $scheduleSendTime
     * @param string $filePath
     */
    public
    function postWithMedia(string $text, int $socialMediaProfileId, string $scheduleSendTime, string $filePath)
    {
        $this->client->getAccessToken();
        $this->client->postMediaRequestGetUrl($filePath);
        $this->client->putMediaRequest($filePath);

        while ($this->client->getMediaUploadStatus() != "READY") {
            $this->Container->get('logger')->debug("Waiting for upload to complete");
            sleep(2);
        }

        $this->client->scheduleMessageWithUpload($text, $socialMediaProfileId, $scheduleSendTime);
    }

    protected function hasRequiredConfigProperties($params, &$missing): bool
    {
        foreach ($this->requiredConfigProperties as $property) {
            if (!isset($params[$property])) {
                $missing = $property;
                return false;
            }
        }
        return true;
    }
}