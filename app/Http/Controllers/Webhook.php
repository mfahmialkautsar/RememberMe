<?php

namespace App\Http\Controllers;

use App\Repositories\EventLogRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Log\Logger;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use Services\WebhookService;

class Webhook extends Controller
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var EventLogRepository
     */
    private $logRepository;
    /**
     * @var WebhookService
     */
    private $webhookService;

    public function __construct(
        Request $request,
        Response $response,
        Logger $logger,
        EventLogRepository $logRepository,
        WebhookService $webhookService
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->logRepository = $logRepository;
        $this->webhookService = $webhookService;

        // create bot object
        $httpClient = new CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
        $bot = new LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
        $this->webhookService->setBot($bot);
    }

    public function __invoke()
    {
        $channelSecret = getenv('CHANNEL_SECRET'); // Channel secret string
        $httpRequestBody = $this->request->getContent(); // Request body string
        $hash = hash_hmac('sha256', $httpRequestBody, $channelSecret, true);
        $signature = base64_encode($hash);
        $httpRequestHeader = $this->request->header('x-line-signature');

        // Compare x-line-signature request header string and the signature
        if ($signature !== $httpRequestHeader) {
            $this->response->setContent("Forbidden");
            $this->response->setStatusCode(403);
            return $this->response;
        }

        // get request
        $body = $this->request->all();

        // debug data
        $this->logger->debug('Body', $body);

        // save log
        $signature = $this->request->server('HTTP_X_LINE_SIGNATURE') ?: '-';
        $this->logRepository->saveLog($signature, json_encode($body, true));

        $this->response = $this->webhookService->handleEvents($this->request->all()) ?? $this->response;
        return $this->response;
    }
}
