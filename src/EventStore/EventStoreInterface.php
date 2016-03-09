<?php

namespace EventStore;

use EventStore\Projections\Projection;
use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\Event;
use EventStore\StreamFeed\StreamFeed;
use EventStore\StreamFeed\StreamFeedIterator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface EventStoreInterface
 * @package EventStore
 */
interface EventStoreInterface
{
    /**
     * Get the response from the last HTTP call to the EventStore API
     *
     * @return ResponseInterface
     */
    public function getLastResponse();

    /**
     * Write one or more events to a stream
     *
     * @param  string                                  $streamName      The stream name
     * @param  WritableToStream                        $events          Single event or a collection of events
     * @param  int                                     $expectedVersion The expected version of the stream
     * @throws Exception\WrongExpectedVersionException
     */
    public function writeToStream($streamName, WritableToStream $events, $expectedVersion = ExpectedVersion::ANY);

    /**
     * Read a single event
     *
     * @param  string $eventUrl The url of the event
     * @return Event
     */
    public function readEvent($eventUrl);

    /**
     * Delete a stream
     *
     * @param string         $streamName Name of the stream
     * @param StreamDeletion $mode       Deletion mode (soft or hard)
     */
    public function deleteStream($streamName, StreamDeletion $mode);

    /**
     * Open a stream feed for read and navigation
     *
     * @param  string         $streamName The stream name
     * @param  EntryEmbedMode $embedMode  The event entries embed mode (none, rich or body)
     * @return StreamFeed
     */
    public function openStreamFeed($streamName, EntryEmbedMode $embedMode = null);

    /**
     * @param  string             $streamName
     * @return StreamFeedIterator
     */
    public function forwardStreamFeedIterator($streamName);

    /**
     * @param  string             $streamName
     * @return StreamFeedIterator
     */
    public function backwardStreamFeedIterator($streamName);

    /**
     * Write projection
     *
     * @param Projection $projection
     * @param bool $force
     * @return mixed
     */
    public function writeProjection(Projection $projection, $force = false);

    /**
     * Read projection
     *
     * @param $name
     * @return mixed
     */
    public function readProjection($name);

    /**
     * Delete projection
     *
     * @param $name
     * @param bool $withCheckpoints
     * @param bool $withStreams
     * @return mixed
     */
    public function deleteProjection($name, $withCheckpoints = false, $withStreams = false);

    /**
     * Update existing projection
     *
     * @param Projection $projection
     * @param bool $reset
     * @return mixed
     */
    public function updateProjection(Projection $projection, $reset = false);
}
