<?php

namespace EventStore\Projections;

class Projection
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $body;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var boolean
     */
    protected $checkpoints;

    /**
     * @var boolean
     */
    protected $emit;

    /**
     * @var boolean
     */
    protected $enable;

    /**
     * Projection constructor.
     * @param string $name
     * @param string $mode
     */
    public function __construct($mode, $name)
    {
        $this->mode = $mode;
        $this->name = $name;
        $this->checkpoints = true;
        $this->emit = true;
        $this->enable = true;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function getUrlParams()
    {
        return $this->getMode().'?'.http_build_query($this->prepareParams());
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return array
     */
    private function prepareParams()
    {
        $params = [
            'name' => $this->getName(),
            'emit' => $this->isEmit() ? 'yes' : 'no',
            'checkpoints' => $this->isCheckpoints() ? 'yes' : 'no',
            'enable' => $this->isEnable() ? 'yes' : 'no',
        ];

        return $params;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isEmit()
    {
        return $this->emit;// ? 'yes' : 'no';
    }

    /**
     * @return boolean
     */
    public function isCheckpoints()
    {
        return $this->checkpoints;// ? 'yes' : 'no';
    }

    /**
     * @return boolean
     */
    public function isEnable()
    {
        return $this->enable;// ? 'yes' : 'no';
    }

    /**
     * @param boolean $checkpoints
     */
    public function setCheckpoints($checkpoints)
    {
        $this->checkpoints = $checkpoints;
    }

    /**
     * @param boolean $emit
     */
    public function setEmit($emit)
    {
        $this->emit = $emit;
    }

    /**
     * @param boolean $enable
     */
    public function setEnable($enable)
    {
        $this->enable = $enable;
    }
}
