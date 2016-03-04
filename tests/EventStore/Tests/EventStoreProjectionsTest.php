<?php

namespace EventStore\Tests;

use EventStore\EventStore;
use EventStore\Projections\Projection;
use EventStore\Projections\RunMode;

class EventStoreProjectionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventStore
     */
    private $es;

    protected function setUp()
    {
        $this->es = new EventStore('http://127.0.0.1:2113');
    }

    /**
     * @test
     */
    public function create_continuous_partition_projection()
    {
        $projection = $this->prepareLinkToProjection('partitionProjection'.uniqid());

        $this->es->writeProjection($projection);

        $this->assertEquals('201', $this->es->getLastResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function create_duplicated_continuous_partition_projection()
    {
        $projection = $this->prepareLinkToProjection('partitionProjection'.uniqid());

        $this->es->writeProjection($projection);
        $this->es->writeProjection($projection);

        $this->assertEquals('409', $this->es->getLastResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function read_projection_status()
    {
        $name = 'partitionProjection'.uniqid();
        $projection = $this->prepareLinkToProjection($name);

        $this->es->writeProjection($projection);

        $response = $this->es->readProjection($name);

        $this->assertEquals('200', $this->es->getLastResponse()->getStatusCode());
        $this->assertInstanceOf('EventStore\Projections\Statistics', $response);
    }

    /**
     * @test
     * @expectedException \EventStore\Exception\ProjectionNotFoundException
     */
    public function read_not_exiting_projection_status()
    {
        $this->es->readProjection('partitionProjection'.uniqid());
    }

    /**
     * @test
     * @expectedException \EventStore\Exception\ProjectionNotFoundException
     */
    public function delete_existing_projection()
    {
        $name = 'partitionProjection'.uniqid();
        $projection = $this->prepareLinkToProjection($name);

        $this->es->writeProjection($projection);

        $this->es->deleteProjection($name);

        $this->es->readProjection($name);
    }

    /**
     * @test
     * @expectedException \EventStore\Exception\ProjectionNotFoundException
     */
    public function delete_not_existing_projection()
    {
        $name = 'partitionProjection'.uniqid();

        $this->es->deleteProjection($name);

        $this->es->readProjection($name);
    }

    /**
     * @param string $name
     * @return Projection
     */
    private function prepareLinkToProjection($name = '')
    {
        if (empty($name)) {
            $name = 'partitionProjection';
        }

        $projection = new Projection(RunMode::CONTINUOUS(), $name);
        $projection->setBody('fromStream(\'someStream\')
            .when({
              $any:function(state, event) {
                linkTo(\'category-\' + event.data.id, event)
              }
            });');

        return $projection;
    }
}
