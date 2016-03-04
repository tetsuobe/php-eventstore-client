<?php

namespace EventStore\Projections;

class Statistics
{
    /** @var int */
    private $coreProcessingTime;

    /** @var int */
    private $version;

    /** @var int */
    private $epoch;

    /** @var string */
    private $effectiveName;

    /** @var int */
    private $writesInProgress;

    /** @var int */
    private $readsInProgress;

    /** @var int */
    private $partitionsCached;

    /** @var string */
    private $status;

    /** @var string */
    private $stateReason;

    /** @var string */
    private $name;

    /** @var string */
    private $mode;

    /** @var string */
    private $position;

    /** @var int */
    private $progress;

    /** @var string */
    private $lastCheckpoint;

    /** @var int */
    private $eventsProcessedAfterRestart;

    /** @var string */
    private $statusUrl;

    /** @var string */
    private $stateUrl;

    /** @var string */
    private $resultUrl;

    /** @var string */
    private $queryUrl;

    /** @var string */
    private $enableCommandUrl;

    /** @var string */
    private $disableCommandUrl;

    /** @var string */
    private $checkpointStatus;

    /** @var int */
    private $bufferedEvents;

    /** @var int */
    private $writePendingEventsBeforeCheckpoint;

    /** @var int */
    private $writePendingEventsAfterCheckpoint;

    /**
     * @return int
     */
    public function getCoreProcessingTime()
    {
        return $this->coreProcessingTime;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getEpoch()
    {
        return $this->epoch;
    }

    /**
     * @return string
     */
    public function getEffectiveName()
    {
        return $this->effectiveName;
    }

    /**
     * @return int
     */
    public function getWritesInProgress()
    {
        return $this->writesInProgress;
    }

    /**
     * @return int
     */
    public function getReadsInProgress()
    {
        return $this->readsInProgress;
    }

    /**
     * @return int
     */
    public function getPartitionsCached()
    {
        return $this->partitionsCached;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStateReason()
    {
        return $this->stateReason;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @return string
     */
    public function getLastCheckpoint()
    {
        return $this->lastCheckpoint;
    }

    /**
     * @return int
     */
    public function getEventsProcessedAfterRestart()
    {
        return $this->eventsProcessedAfterRestart;
    }

    /**
     * @return string
     */
    public function getStatusUrl()
    {
        return $this->statusUrl;
    }

    /**
     * @return string
     */
    public function getStateUrl()
    {
        return $this->stateUrl;
    }

    /**
     * @return string
     */
    public function getResultUrl()
    {
        return $this->resultUrl;
    }

    /**
     * @return string
     */
    public function getQueryUrl()
    {
        return $this->queryUrl;
    }

    /**
     * @return string
     */
    public function getEnableCommandUrl()
    {
        return $this->enableCommandUrl;
    }

    /**
     * @return string
     */
    public function getDisableCommandUrl()
    {
        return $this->disableCommandUrl;
    }

    /**
     * @return string
     */
    public function getCheckpointStatus()
    {
        return $this->checkpointStatus;
    }

    /**
     * @return int
     */
    public function getBufferedEvents()
    {
        return $this->bufferedEvents;
    }

    /**
     * @return int
     */
    public function getWritePendingEventsBeforeCheckpoint()
    {
        return $this->writePendingEventsBeforeCheckpoint;
    }

    /**
     * @return int
     */
    public function getWritePendingEventsAfterCheckpoint()
    {
        return $this->writePendingEventsAfterCheckpoint;
    }

    /**
     * @param array $response
     * @return Statistics
     */
    public function create(array $response)
    {
        foreach ($response as $name => $value) {
            $this->$name = $value;
        }

        return $this;
    }
}
