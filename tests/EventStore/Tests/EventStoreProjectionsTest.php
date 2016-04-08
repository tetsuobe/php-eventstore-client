<?php

namespace EventStore\Tests;

use EventStore\EventStore;
use EventStore\Projections\Projection;
use EventStore\Projections\RunMode;
use EventStore\Projections\Statistics;

class EventStoreProjectionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventStore
     */
    private $es;

    protected function setUp()
    {
        $this->es = new EventStore('http://127.0.0.1:2113');
        $this->es->setAuthorization('admin', 'changeit');
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
     * @expectedException \EventStore\Exception\ProjectionAlreadyExistsException
     */
    public function create_duplicated_continuous_partition_projection()
    {
        $projection = $this->prepareLinkToProjection('partitionProjection'.uniqid());

        $this->es->writeProjection($projection);
        $this->es->writeProjection($projection);
    }

    /**
     * @test
     */
    public function force_create_continuous_partition_projection()
    {
        $projection = $this->prepareLinkToProjection('partitionProjection'.uniqid());

        $this->es->writeProjection($projection);
        $this->es->writeProjection($projection, true);

        $this->assertEquals('200', $this->es->getLastResponse()->getStatusCode());
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
        $projection->setBody(
            'fromStream(\'someStream\')
            .when({
              $any:function(state, event) {
                linkTo(\'category-\' + event.data.id, event)
              }
            });'
        );

        return $projection;
    }

    /**
     * @test
     */
    public function update_existing_projection()
    {
        $name = 'partitionProjection'.uniqid();
        $projection = $this->prepareLinkToProjection($name);

        $this->es->writeProjection($projection);

        /** @var Statistics $responseOne */
        $responseOne = $this->es->readProjection($name);

        $projection->setEmit(false);
        $projection->setBody(
            'fromStream(\'someOtherStream\')
                .when({
                  $any:function(state, event) {
                    linkTo(\'type-\' + event.data.type, event)
                  }
                });'
        );

        $this->es->updateProjection($projection);

        sleep(1);
        /** @var Statistics $responseTwo */
        $responseTwo = $this->es->readProjection($name);

        $this->assertEquals('200', $this->es->getLastResponse()->getStatusCode());
        $this->assertTrue($responseTwo->getVersion() == $responseOne->getVersion() + 1, 'Projection was not updated');
    }

    /**
     * @test
     * @expectedException \EventStore\Exception\ProjectionNotFoundException
     */
    public function update_not_existing_projection()
    {
        $name = 'partitionProjection'.uniqid();
        $projection = $this->prepareLinkToProjection($name);

        $projection->setEmit(false);
        $projection->setBody(
            'fromStream(\'someOtherStream\')
            .when({
              $any:function(state, event) {
                linkTo(\'type-\' + event.data.aggregateId, event)
              }
            });');

        $this->es->updateProjection($projection);
    }
}
