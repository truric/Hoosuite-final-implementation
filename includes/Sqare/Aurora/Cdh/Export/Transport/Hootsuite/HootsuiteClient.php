<?php
/**
 * @copyright    PubliQare BV All Rights Reserved.
 * @author    Ricardo ricardo.parada@publiqare.com
 */

namespace Sqare\Aurora\Cdh\Export\Transport\Hootsuite;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Slim\Container;

/**
 * @property  Container
 */
class HootsuiteClient
{
    /**
     * @var Container
     */
    private $Container;

    /**
     * @var string
     */
    private string $putUrl;

    /**
     * @var string
     */
    private string $base_uri;

    /**
     * @var HootsuiteLocator
     */
    private HootsuiteLocator $locator;

    /**
     * @var array
     */
    private array $hootsuiteSocialmediaProfiles;

    /**
     * @param Container $container
     * @param $httpClient
     * @param HootsuiteLocator $locator
     */
    public function __construct(Container $container, $httpClient, HootsuiteLocator $locator)
    {
        $this->httpClient = $httpClient;
        $this->locator = $locator;
        $this->Container = $container;
    }

    /**
     * @var int
     */
    const MAXTIME = 15 * 60;

    /**
     * @var Client
     */
    private Client $httpClient;

    /**
     * @var int
     */
    const FACEBOOK_CHARACTER_LENGTH = 63206;

    /**
     * @var int
     */
    const LINKEDIN_CHARACTER_LENGTH = 3000;

    /**
     * @var int
     */
    const LINKEDINCOMPANY_CHARACTER_LENGTH = 2000;

    /**
     * @var int
     */
    const TWITTER_CHARACTER_LENGTH = 280;

    /**
     * @var int
     */
    const INSTAGRAM_CHARACTER_LENGTH = 2200;

//    /**
//     * @var HootsuiteLocator
//     */
//    private HootsuiteLocator $locator;

    /**
     * @var string|null
     */
    private ?string $accessToken = null;

    /**
     * @var string|null
     */
    protected ?string $fileId = null;

    /**
     * @var string|null
     */
    protected ?string $scheduleSendTime = null;

    /**
     * @var string|array
     */
    protected string $text;

    /**
     * @var string
     */
    private string $mimeTypeImage = 'image/jpg';

    /**
     * @var string
     */
    private string $mimeTypeVideo = 'video/mp4';

    /**
     * @var string
     */
    private string $fileMimeType;

    /**
     * @var integer
     */
    private int $resolution;

    /**
     * @var string|null
     */
    private ?string $convertedFilePath = null;

    /**
     * @var array
     */
    public array $socialMediaProfiles;

    /**
     * @var object|null
     */
    private ?object $response = null;

    /**
     * @var string|null
     */
    protected ?string $filePath = null;

    /**
     * @var string|null
     */
    private ?string $hootsuiteClientSecret = null;

    /**
     * @var string|null
     */
    private ?string $hootsuiteClientId = null;

    /**
     * @var integer|null
     */
    private ?int $hootsuiteClientMemberId = null;

    /**
     * @var string|null
     */
    private ?string $videoConversionBinPath = null;

    /**
     * @param string $hootsuiteClientId
     * @return HootsuiteClient
     */
    public function setHootsuiteClientId(string $hootsuiteClientId): HootsuiteClient
    {
        $this->hootsuiteClientId = $hootsuiteClientId;
        return $this;
    }

    /**
     * @param string $hootsuiteClientSecret
     * @return $this
     */
    public function setHootsuiteClientSecret(string $hootsuiteClientSecret): HootsuiteClient
    {
        $this->hootsuiteClientSecret = $hootsuiteClientSecret;
        return $this;
    }

    /**
     * @param string $hootsuiteClientMemberId
     * @return $this
     */
    public function setHootsuiteClientMemberId(string $hootsuiteClientMemberId): HootsuiteClient
    {
        $this->hootsuiteClientMemberId = $hootsuiteClientMemberId;
        return $this;
    }

    /**
     * @param string $hootsuitePrimaryImage
     * @return $this
     */
    public function setHootsuitePrimaryImage(string $hootsuitePrimaryImage): HootsuiteClient
    {
        $this->hootsuitePrimaryImage = $hootsuitePrimaryImage;
        return $this;
    }

    /**
     * @param array $hootsuiteSocialmediaProfiles
     * @return $this
     */
    public function setHootsuiteSocialmediaProfiles(array $hootsuiteSocialmediaProfiles): HootsuiteClient
    {
        $this->hootsuiteSocialmediaProfiles = $hootsuiteSocialmediaProfiles;
        return $this;
    }

    /**
     * @param string $base_uri
     * @return $this
     */
    public function setBaseUri(string $base_uri): HootsuiteClient
    {
        $this->base_uri = $base_uri;
        return $this;
    }

    /**
     * @return string
     */
    private function getFileId(): string
    {
        return $this->fileId;
    }

    /**
     * @param $mimeTypeVideo
     */
    public function setMimeTypeVideo($mimeTypeVideo)
    {
        $this->mimeTypeVideo = $mimeTypeVideo;
    }

    /**
     * @param string $mimeTypeImage
     */
    public function setMimeTypeImage(string $mimeTypeImage)
    {
        $this->mimeTypeImage = $mimeTypeImage;
    }

    /**
     * @param string $videoConversionBinPath
     * @return $this
     */
    public function setVideoConversionBinPath(string $videoConversionBinPath): HootsuiteClient
    {
        $this->videoConversionBinPath = $videoConversionBinPath;
        return $this;
    }

    /**
     * @return string
     * @throws GuzzleException
     */
    public function getAccessToken(): string
    {
        try {
            if (isset($this->accessToken)) {
                $this->Container->get('logger')->debug("+++Access Token is still active: " . $this->accessToken);
                return true;
            }

            if (!isset($this->hootsuiteClientMemberId)) {
                $this->Container->get('logger')->debug("+++hootsuiteClientMemberId not set or invalid");
                return false;
            }

            $response = $this->httpClient->post('/auth/oauth/v2/token', [
                'form_params' => [
                    'grant_type' => 'member_app',
                    'member_id' => $this->hootsuiteClientMemberId,
                    'scope' => 'offline',
                ],
                'headers' => [
                    'Authorization' => 'Basic ' .
                        base64_encode($this->hootsuiteClientId . ':' . $this->hootsuiteClientSecret)
                ]
            ]);
        } catch (Exception $e) {
            $this->Container->get('logger')->error($e->getMessage());
        }
        $tokenData = $response->getBody();

        $decode = json_decode($tokenData, true);
        $accessToken = $decode['access_token'];

        $this->Container->get('logger')->debug("+++++Access Token generated: " . $accessToken);

        return $this->accessToken = $accessToken;
    }

    /**
     * @return string
     * @throws GuzzleException
     */
    public function me(): string
    {
        try {
            $response = $this->httpClient->get('/v1/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ]
            ]);
        } catch (Exception $e) {
            $this->Container->get('logger')->error($e->getMessage());
        }
        $tokenData = $response->getBody();
        $this->Container->get('logger')->debug("+++++User info: " . $tokenData);

        return $tokenData->getContents();
    }

    /**
     * @throws GuzzleException
     */
    public function getSocialMediaProfiles(): array
    {
        try {
            $response = $this->httpClient->get('/v1/socialProfiles', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                ]
            ]);
        } catch (Exception $e) {
            $this->Container->get('logger')->error($e->getMessage());
        }

        $tokenData = $response->getBody();
        $tokenData = $tokenData->getContents();

        $decode = json_decode($tokenData, true);
        /*      Counting the number of social profile associated with logged user and listing all the available social profile
                ids, here are the list of possible social profile names: TWITTER, YOUTUBECHANNEL, FACEBOOKPAGE, LINKEDIN,
                FACEBOOK, PINTEREST, INSTAGRAMBUSINESS, LINKEDINCOMPANY    */
        for ($i = 0; $i < count($decode['data']); $i++) {
            $this->socialMediaProfiles[(string)$decode['data'][$i]['type']] = (int)($decode['data'][$i]['id']);
        }
        $this->Container->get('logger')->debug("++++Getting social media profile IDs: " . $this->socialMediaProfiles);
        return $this->socialMediaProfiles;
    }

    /**
     * @param string|array $text
     * @param array $socialMediaProfiles
     * @param string|null $scheduleSendTime
     */
    /*  The time the message is scheduled to be sent is UTC time, ISO-8601 format. Missing or different timezones will
        not be accepted, to ensure there is no ambiguity about scheduled time. Dates must end with 'Z' to be accepted.
        If there is no scheduleSendTime parameter, the message will be sent as soon as it's processed.
        $scheduleSendTime example: "2021-07-15T12:15:00Z"                                                           */

    public function post(string $text, array $socialMediaProfiles, string $scheduleSendTime = null): void
    {
        $oldTime = ini_get('max_execution_time');
        foreach ($socialMediaProfiles as $socialMediaProfile) {
            set_time_limit(self::MAXTIME);

            $form_params = [
                'text' => $this->text = $text,
                'socialProfileIds' => [$socialMediaProfile],
                'scheduledSendTime' => $this->scheduleSendTime = $scheduleSendTime,
                'emailNotification' => true
            ];

            //casting as float, if the user inserts latitude as 'A' for example, it converts into zero so the code won't break
            if ($this->locator) {
                $location = $this->locator::getLocation();
                $form_params['location'] = [
                    'latitude' => (float)$location[0],
                    'longitude' => (float)$location[1]
                ];
            }

            switch ($socialMediaProfile) {

                case $this->socialMediaProfiles['FACEBOOKPAGE'];
                    $this->getMessageCharacterLength($this->text) > self::FACEBOOK_CHARACTER_LENGTH ?
                        $this->Container->get('logger')->error("Text length exceeded, max characters= " .
                            self::FACEBOOK_CHARACTER_LENGTH) :
                        $this->Container->get('logger')->debug(sprintf("Text length OK, number of characters: "
                            . $this->getMessageCharacterLength($this->text)));
                    $this->_tryCatchResponsePost($form_params);
                    break;

                case $this->socialMediaProfiles['LINKEDINCOMPANY'];
                    $this->getMessageCharacterLength($this->text) > self::LINKEDINCOMPANY_CHARACTER_LENGTH ?
                        $this->Container->get('logger')->error("Text length exceeded, max characters= " .
                            self::LINKEDINCOMPANY_CHARACTER_LENGTH) :
                        $this->Container->get('logger')->debug(sprintf("Text length OK, number of characters: "
                            . $this->getMessageCharacterLength($this->text)));
                    $this->_tryCatchResponsePost($form_params);
                    break;

                case $this->socialMediaProfiles['TWITTER'];
                    $this->getMessageCharacterLength($this->text) > self::TWITTER_CHARACTER_LENGTH ?
                        $this->Container->get('logger')->error("Text length exceeded, max characters= " .
                            self::TWITTER_CHARACTER_LENGTH) :
                        $this->Container->get('logger')->debug(sprintf("Text length OK, number of characters: "
                            . $this->getMessageCharacterLength($this->text)));
                    $this->_tryCatchResponsePost($form_params);
                    break;


                case $this->socialMediaProfiles['INSTAGRAMBUSINESS'];
                    $this->getMessageCharacterLength($this->text) > self::INSTAGRAM_CHARACTER_LENGTH ?
                        $this->Container->get('logger')->error("Text length exceeded, max characters= " .
                            self::INSTAGRAM_CHARACTER_LENGTH) :
                        $this->Container->get('logger')->debug(sprintf("Text length OK, number of characters: "
                            . $this->getMessageCharacterLength($this->text)));
                    $this->_tryCatchResponsePost($form_params);
                    break;
            }
        }
        set_time_limit($oldTime);
    }

    /**
     * @param $filePath
     * @param null $fileId
     * @return string
     * @throws GuzzleException
     */
    public
    function postMediaRequestGetUrl($filePath, &$fileId = null): string
    {
        $size = filesize($filePath) ?: 0;
        $this->Container->get('logger')->debug(sprintf("File size: %s", $size));
        $this->Container->get('logger')->debug(sprintf("File path: %s", $filePath));
        $this->fileMimeType = (pathinfo($filePath, PATHINFO_EXTENSION) == 'mp4' || filetype($filePath) == 'mov') ?
            $this->mimeTypeVideo : $this->mimeTypeImage;

        $oldTime = ini_get('max_execution_time');
        set_time_limit(self::MAXTIME);

        try {
            $response = $this->httpClient->post('/v1/media', [
                RequestOptions::JSON => [
                    'sizeBytes' => $size,
                    'mimeType' => $this->fileMimeType
                ],
                RequestOptions::HEADERS => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-type' => 'Application/json',
                ]
            ]);
        } catch (Exception $e) {
            $this->Container->get('logger')->error($e->getMessage());
        }
        $tokenData = $response->getBody();

        $decode = json_decode($tokenData, true);
        $putUrl = $decode['data']['uploadUrl'];

        $this->fileId = $fileId = $decode['data']['id'];
        $this->filePath = $filePath;

        $this->filePath = $filePath;
        set_time_limit($oldTime);

        $this->Container->get('logger')->debug("++++File ID: " . $this->fileId);
        $this->Container->get('logger')->debug("++++File path: " . $this->filePath);
        $this->Container->get('logger')->debug("++++URL generated: " . $putUrl);

        return $this->putUrl = $putUrl;
    }

    /**
     * @param $fileToUpload
     * @return string
     * @throws GuzzleException
     */
    public
    function putMediaRequest($fileToUpload): string
    {
        if (!$this->putUrl) {
            $this->Container->get('logger')->debug("ERROR no upload url");
            return "ERROR no upload url";
        }
        $oldTime = ini_get('max_execution_time');
        set_time_limit(self::MAXTIME);
        $this->filePath = $fileToUpload;

        $fileSize = filesize($fileToUpload) ?: 0;
        try {
            $this->Container->get('logger')->debug(sprintf("FileSize to upload: %s", $fileSize));

            $requestParams = [
                RequestOptions::HEADERS => [
                    'Content-Type' => $this->fileMimeType,
                    'Content-Length' => $fileSize

                ],
                RequestOptions::BODY => file_get_contents($fileToUpload)
            ];

            $response = $this->httpClient->request(
                'PUT',
                $this->putUrl,
                $requestParams
            );
            $this->Container->get('logger')->debug("++++File uploaded successfully to S3 bucket");

            if (!$response->getStatusCode() == 200) {
                throw new Exception(sprintf("API Auth error: %s", $response->getReasonPhrase()));
            }

        } catch (Exception $e) {
            $this->Container->get('logger')->error($e->getMessage());
        }
        set_time_limit($oldTime);
        return true;
    }

    /**
     * @return string
     * @throws GuzzleException
     * @throws Exception
     */
    function getMediaUploadStatus(): string
    {
        $oldTime = ini_get('max_execution_time');
        set_time_limit(self::MAXTIME);

        $this->Container->get('logger')->debug('Getting media upload status.');

        $uri = sprintf("/v1/media/%s", $this->fileId);

        $response = $this->httpClient->request('GET', $uri, [
            'headers' => [
                'Authorization' => sprintf("Bearer %s", $this->accessToken)

            ]
        ]);

        if (!$response->getStatusCode() == 200) {
            throw new Exception(sprintf("API Auth error: %s", $response->getReasonPhrase()));
        }


        if (!$result = json_decode($response->getBody()->getContents(), true)) {
            throw new Exception("API response couldn't be decoded (JSON).");
        }
        set_time_limit($oldTime);
        $this->Container->get('logger')->debug($result['data']['state']);
        return $result['data']['state'];
    }


    /**
     * @param string|array $text
     * @param int $socialMediaProfileId
     * @param string|null $scheduleSendTime
     */
    /*  If your media type is a video, you must schedule it at least 15 minutes into the future.
        Alternatively, you can send a message without a fixed send time and it will automatically be assigned the
        soonest possible send time.
        Note: Specifying a custom thumbnail is not yet supported. A thumbnail will be auto generated for the
        uploaded media. */
    public
    function scheduleMessageWithUpload(string $text, int $socialMediaProfileId, string $scheduleSendTime = null): void
    {

        $oldTime = ini_get('max_execution_time');

        set_time_limit(self::MAXTIME);
        $requestParamsFileTypeVideo = [
            RequestOptions::JSON =>
                [
                    "text" => $this->text = $text,
                    "scheduledSendTime" => $this->scheduleSendTime = $scheduleSendTime,
                    'socialProfileIds' => [$socialMediaProfileId],

                    "media" => [
                        [
                            "id" => $this->fileId,
                            "videoOptions" => [
                                "facebook" => [
                                    "category" => "ENTERTAINMENT"
                                    /*  This is a facebook object, can't input just any string
                                        "BEAUTY_FASHION", "BUSINESS", "CARS_TRUCKS", "COMEDY", "CUTE_ANIMALS", "ENTERTAINMENT",
                                        "FAMILY", "FOOD_HEALTH", "HOME", "LIFESTYLE", "MUSIC", "NEWS", "POLITICS", "SCIENCE",
                                        "SPORTS", "TECHNOLOGY", "VIDEO_GAMING", "OTHER"                                      */
                                ]
                            ]
                        ]
                    ]
                ],
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],

        ];

        $requestParamsFileTypeImage = [
            RequestOptions::JSON =>
                [
                    "text" => $this->text = $text,
                    "scheduledSendTime" => $this->scheduleSendTime = $scheduleSendTime,
                    'socialProfileIds' => [$socialMediaProfileId],

                    "media" => [
                        [
                            "id" => $this->fileId,
                        ]
                    ]
                ],
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],

        ];

        $this->fileMimeType != $this->mimeTypeImage ? $requestParams = $requestParamsFileTypeVideo
            : $requestParams = $requestParamsFileTypeImage;

        $this->Container->get('logger')->debug("File chosen is type: " . $this->fileMimeType);

        $this->response = $this->_tryCatchResponseScheduleMessage($requestParams);

        $this->response == 1 ? $this->Container->get('logger')->debug("Post sent") :
            $this->Container->get('logger')->debug("Post failed");

        if (file_exists($this->convertedFilePath)) {
            unlink($this->convertedFilePath);
        }
        set_time_limit($oldTime);
    }

    /*
     * Trying to avoid code repetition
     * This will be used for each social media
     */
    /**
     * @param $requestParams
     * @return object|null
     * @throws GuzzleException
     * @throws Exception
     */
    private function _tryCatchResponseScheduleMessage($requestParams): ?object
    {
        try {
            $response = $this->httpClient->request('POST', '/v1/messages', $requestParams);
            return $response->getBody();
        } catch (Exception $e) {
            $this->Container->get('logger')->debug($e->getMessage());
            $getErrorMessageAsString = json_encode($e->getMessage(), true);
            $errorMessageParts = explode(':', $getErrorMessageAsString);
            if (substr($errorMessageParts[5], 0, 5) == 40009) {
                throw new Exception('The width of the video is too big. In order to post a video on Twitter, max width = 1280' . PHP_EOL);
            }
        }
        return null;
    }

    /**
     * @param $requestParams
     * @return object|null
     * @throws GuzzleException
     */
    private function _tryCatchResponsePost($requestParams): ?object
    {
        try {
            $response = $this->httpClient->post('/v1/messages', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $requestParams
            ]);
            return $response->getBody();
        } catch (Exception $e) {
            $this->Container->get('logger')->error($e->getMessage());
        }
        return null;
    }

    /**
     * @param $message
     * @return int
     */
    private
    function getMessageCharacterLength($message): int
    {
        return strlen($message);
    }
}