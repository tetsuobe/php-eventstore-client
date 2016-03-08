<?php

namespace EventStore;

use EventStore\Exception\ConnectionFailedException;
use EventStore\Exception\InvalidCommandException;
use EventStore\Exception\ProjectionNotFoundException;
use EventStore\Exception\StreamDeletedException;
use EventStore\Exception\StreamNotFoundException;
use EventStore\Exception\UnauthorizedException;
use EventStore\Exception\WrongExpectedVersionException;
use EventStore\Http\Exception\ClientException;
use EventStore\Http\Exception\RequestException;
use EventStore\Http\GuzzleHttpClient;
use EventStore\Http\HttpClientInterface;
use EventStore\Http\ResponseCode;
use EventStore\Projections\Command;
use EventStore\Projections\Projection;
use EventStore\Projections\Statistics;
use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\LinkRelation;
use EventStore\StreamFeed\StreamFeed;
use EventStore\StreamFeed\StreamFeedIterator;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EventStore
 * @package EventStore
 */
final class EventStore implements EventStoreInterface
{
    const URL_STREAMS = 'streams';
    const URL_PROJECTION = 'projection';
    const URL_PROJECTIONS = 'projections';

    /**
     * @var string
     */
    private $url;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var ResponseInterface
     */
    private $lastResponse;

    /**
     * @var array
     */
    private $badCodeHandlers = [];

    /**
     * @param string $url Endpoint of the EventStore HTTP API
     * @param HttpClientInterface $httpClient the http client
     */
    public function __construct($url, HttpClientInterface $httpClient = null)
    {
        $this->url = $url;

        $this->httpClient = $httpClient ?: new GuzzleHttpClient();
        $this->checkConnection();
        $this->initBadCodeHandlers();
    }

    /**
     * Delete a stream
     *
     * @param string $streamName Name of the stream
     * @param StreamDeletion $mode Deletion mode (soft or hard)
     */
    public function deleteStream($streamName, StreamDeletion $mode)
    {
        $request = new Request('DELETE', $this->getUrl(EventStore::URL_STREAMS, $streamName));

        if ($mode == StreamDeletion::HARD) {
            $request = $request->withHeader('ES-HardDelete', 'true');
        }

        $this->sendRequest($request);
    }

    /**
     * Get the response from the last HTTP call to the EventStore API
     *
     * @return ResponseInterface
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Navigate stream feed through link relations
     *
     * @param  StreamFeed $streamFeed The stream feed to navigate through
     * @param  LinkRelation $relation The "direction" expressed as link relation
     * @return null|StreamFeed
     */
    public function navigateStreamFeed(StreamFeed $streamFeed, LinkRelation $relation)
    {
        $url = $streamFeed->getLinkUrl($relation);

        if (empty($url)) {
            return null;
        }

        return $this->readStreamFeed($url, $streamFeed->getEntryEmbedMode());
    }

    /**
     * Open a stream feed for read and navigation
     *
     * @param  string $streamName The stream name
     * @param  EntryEmbedMode $embedMode The event entries embed mode (none, rich or body)
     * @return StreamFeed
     */
    public function openStreamFeed($streamName, EntryEmbedMode $embedMode = null)
    {
        $url = $this->getUrl(EventStore::URL_STREAMS, $streamName);

        return $this->readStreamFeed($url, $embedMode);
    }

    /**
     * Read a single event
     *
     * @param  string $eventUrl The url of the event
     * @return Event
     */
    public function readEvent($eventUrl)
    {
        $request = $this->getJsonRequest($eventUrl);
        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($eventUrl);

        $jsonResponse = $this->lastResponseAsJson();

        return $this->createEventFromResponseContent($jsonResponse['content']);
    }

    /**
     * Read a single event
     *
     * @param array $eventUrls The url of the event
     * @return Event
     */
    public function readEventBatch(array $eventUrls)
    {
        $requests = array_map(
            function ($eventUrl) {
                return $this->getJsonRequest($eventUrl);
            },
            $eventUrls
        );

        $responses = $this->httpClient->sendRequestBatch($requests);

        return array_map(
            function ($response) {
                return $this
                    ->createEventFromResponseContent(
                        json_decode($response->getBody(), true)['content']
                    );
            },
            $responses
        );
    }

    /**
     * @param  array $content
     * @return Event
     */
    protected function createEventFromResponseContent(array $content)
    {
        $type = $content['eventType'];
        $version = (integer)$content['eventNumber'];
        $data = $content['data'];
        $metadata = (!empty($content['metadata'])) ? $content['metadata'] : null;

        return new Event($type, $version, $data, $metadata);
    }

    /**
     * Write one or more events to a stream
     *
     * @param  string $streamName The stream name
     * @param  WritableToStream $events Single event or a collection of events
     * @param  int $expectedVersion The expected version of the stream
     * @throws Exception\WrongExpectedVersionException
     */
    public function writeToStream($streamName, WritableToStream $events, $expectedVersion = ExpectedVersion::ANY)
    {
        if ($events instanceof WritableEvent) {
            $events = new WritableEventCollection([$events]);
        }

        $request = new Request(
            'POST',
            $this->getUrl(EventStore::URL_STREAMS, $streamName),
            [
                'ES-ExpectedVersion' => intval($expectedVersion),
                'Content-Type' => 'application/vnd.eventstore.events+json',
            ],
            json_encode($events->toStreamData())
        );

        $this->sendRequest($request);

        $responseStatusCode = $this->getLastResponse()->getStatusCode();

        if (ResponseCode::HTTP_BAD_REQUEST == $responseStatusCode) {
            throw new WrongExpectedVersionException();
        }
    }

    /**
     * @param  string $streamName
     * @return StreamFeedIterator
     */
    public function forwardStreamFeedIterator($streamName)
    {
        return StreamFeedIterator::forward($this, $streamName);
    }

    /**
     * @param  string $streamName
     * @return StreamFeedIterator
     */
    public function backwardStreamFeedIterator($streamName)
    {
        return StreamFeedIterator::backward($this, $streamName);
    }

    /**
     * @throws Exception\ConnectionFailedException
     */
    private function checkConnection()
    {
        try {
            $request = new Request('GET', $this->url);
            $this->sendRequest($request);
        } catch (RequestException $e) {
            throw new ConnectionFailedException($e->getMessage());
        }
    }

    /**
     * @return string
     */
    protected function getAuthorizationKey()
    {
        return 'Basic '.base64_encode('admin:changeit');
    }

    /**
     * @param  string $streamName
     * @return string
     */
    private function getStreamUrl($streamName)
    {
        return sprintf('%s/streams/%s', $this->url, $streamName);
    }

    /**
     * @param  string $streamUrl
     * @param  EntryEmbedMode $embedMode
     * @return StreamFeed
     * @throws Exception\StreamDeletedException
     * @throws Exception\StreamNotFoundException
     */
    private function readStreamFeed($streamUrl, EntryEmbedMode $embedMode = null)
    {
        $request = $this->getJsonRequest($streamUrl);

        if ($embedMode != null && $embedMode != EntryEmbedMode::NONE()) {
            $uri = Uri::withQueryValue(
                $request->getUri(),
                'embed',
                $embedMode->toNative()
            );

            $request = $request->withUri($uri);
        }

        $this->sendRequest($request);

        $this->ensureStatusCodeIsGood($streamUrl);

        return new StreamFeed($this->lastResponseAsJson(), $embedMode);
    }

    /**
     * @param  string $uri
     * @return Request|RequestInterface
     */
    protected function getJsonRequest($uri)
    {
        return new Request(
            'GET',
            $uri,
            [
                'Accept' => 'application/vnd.eventstore.atom+json',
            ]
        );
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return ResponseInterface|void
     */
    protected function sendRequest(RequestInterface $request, array $options = [])
    {
        try {
            $this->lastResponse = $this->httpClient->send($request, $options);
        } catch (ClientException $e) {
            $this->lastResponse = $e->getResponse();
        }
    }

    /**
     * @param  string $streamUrl
     * @throws Exception\StreamDeletedException
     * @throws Exception\StreamNotFoundException
     * @throws Exception\UnauthorizedException
     */
    protected function ensureStatusCodeIsGood($streamUrl)
    {
        $code = $this->lastResponse->getStatusCode();

        if (array_key_exists($code, $this->badCodeHandlers)) {
            $this->badCodeHandlers[$code]($streamUrl);
        }
    }

    /**
     *
     */
    private function initBadCodeHandlers()
    {
        $this->badCodeHandlers = [
            ResponseCode::HTTP_NOT_FOUND => function ($streamUrl) {
                throw new StreamNotFoundException(
                    sprintf(
                        'No stream found at %s',
                        $streamUrl
                    )
                );
            },

            ResponseCode::HTTP_GONE => function ($streamUrl) {
                throw new StreamDeletedException(
                    sprintf(
                        'Stream at %s has been permanently deleted',
                        $streamUrl
                    )
                );
            },

            ResponseCode::HTTP_UNAUTHORIZED => function ($streamUrl) {
                throw new UnauthorizedException(
                    sprintf(
                        'Tried to open stream %s got 401',
                        $streamUrl
                    )
                );
            },
        ];
    }

    /**
     * @return mixed
     */
    protected function lastResponseAsJson()
    {
        return json_decode($this->lastResponse->getBody(), true);
    }

    /**
     * @param string $path
     * @param string $params
     * @return string
     * @internal param string $name
     */
    protected function getUrl($path, $params)
    {
        return sprintf('%s/%s/%s', $this->url, $path, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function writeProjection(Projection $projection, $force = false)
    {
        if ($force) {
            $this->deleteProjection($projection->getName());
        }

        $request = new Request(
            'POST',
            $this->getUrl(EventStore::URL_PROJECTIONS, $projection->getUrlParams()),
            [
                'Authorization' => $this->getAuthorizationKey(),
                'Content-Type' => 'application/json',
            ],
            $projection->getBody()
        );

        $this->sendRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function readProjection($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Projection name cannot be empty.');
        }

        $url = $this->getUrl(EventStore::URL_PROJECTION, $name);
        $request = new Request(
            'GET',
            $url
        );

        $this->sendRequest($request);

        if ($this->getLastResponse()->getStatusCode() == ResponseCode::HTTP_NOT_FOUND) {
            throw new ProjectionNotFoundException();
        }

        $this->ensureStatusCodeIsGood($url);

        return $this->createStatisticsFromResponse($this->lastResponseAsJson());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteProjection($name, $withCheckpoints = false, $withStreams = false)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Projection name cannot be empty.');
        }

        $this->commandProjection(Command::DISABLE, $name);

        $url = $this->getUrl(EventStore::URL_PROJECTION, $name);
        $url .= '?'.http_build_query(
                [
                    'deleteCheckpointStream' => $withCheckpoints ? 'yes' : 'no',
                    'deleteStateStream' => $withStreams ? 'yes' : 'no',
                ]
            );

        $request = new Request(
            'DELETE',
            $url,
            [
                'Authorization' => $this->getAuthorizationKey(),
                'Content-Type' => 'application/json',
            ]
        );

        $this->sendRequest($request);
    }

    /**
     * @param array $response
     * @return Statistics
     */
    private function createStatisticsFromResponse(array $response)
    {
        $statistics = new Statistics();

        return $statistics->create($response);
    }

    /**
     * @param string $command
     * @param string $name
     * @throws InvalidCommandException
     */
    public function commandProjection($command, $name)
    {
        if (!Command::isAllowed($command)) {
            throw new InvalidCommandException();
        }

        $url = $this->getUrl(EventStore::URL_PROJECTION, $name);
        $request = new Request(
            'POST',
            $url.'/command/'.$command,
            [
                'Authorization' => $this->getAuthorizationKey(),
                'Content-Type' => 'application/json',
            ]
        );
        $this->sendRequest($request);
    }

    /**
     * {@inheritDoc}
     */
    public function updateProjection(Projection $projection, $reset = false)
    {
        $url = $this->getUrl(
            EventStore::URL_PROJECTION,
            $projection->getUrlQuery(['emit' => $projection->isEmit() ? 'yes' : 'no'])
        );
        $request = new Request(
            'PUT',
            $url,
            [
                'Authorization' => $this->getAuthorizationKey(),
                'Content-Type' => 'application/json',
            ],
            $projection->getBody()
        );
        $this->sendRequest($request);

        if ($reset) {
            $this->commandProjection(Command::RESET(), $projection->getName());
        }
    }
}
