<?php

namespace Registry;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ApiNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ApiRegistry;
use InfluxDB2\QueryApi;
use InfluxDB2\WriteApi;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiRegistryTest extends TestCase
{
    protected const PREFIX = 'babymarkt_influxdb2.';

    protected MockObject|WriteApi $writeApi;
    protected MockObject|QueryApi $queryApi;

    protected function setUp(): void
    {
        $this->writeApi = $this->getMockBuilder(WriteApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryApi = $this->getMockBuilder(QueryApi::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAddAndHasQueryApi()
    {
        $registry = new ApiRegistry();
        $registry->addQueryApi(self::PREFIX . 'default_query_api', $this->queryApi);
        $registry->addQueryApi('some-api', $this->queryApi);

        $this->assertTrue($registry->hasQueryApi('default'));
        $this->assertTrue($registry->hasQueryApi('some-api'));
        $this->assertFalse($registry->hasQueryApi('unknown-api'));
    }

    public function testAddAndGetWriteApi()
    {
        $registry = new ApiRegistry();
        $registry->addQueryApi(self::PREFIX . 'default_query_api', $this->queryApi);
        $registry->addQueryApi('some-api', $this->queryApi);

        $this->assertSame($this->queryApi, $registry->getQueryApi('default'));
        $this->assertSame($this->queryApi, $registry->getQueryApi('some-api'));
    }

    public function testAddAndHasWriteApi()
    {
        $registry = new ApiRegistry();
        $registry->addWriteApi(self::PREFIX . 'default_write_api', $this->writeApi);
        $registry->addWriteApi('some-api', $this->writeApi);

        $this->assertTrue($registry->hasWriteApi('default'));
        $this->assertTrue($registry->hasWriteApi('some-api'));
        $this->assertFalse($registry->hasWriteApi('unknown-api'));
    }

    public function testAddAndGetQueryApi()
    {
        $registry = new ApiRegistry();
        $registry->addWriteApi(self::PREFIX . 'default_write_api', $this->writeApi);
        $registry->addWriteApi('some-api', $this->writeApi);

        $this->assertSame($this->writeApi, $registry->getWriteApi('default'));
        $this->assertSame($this->writeApi, $registry->getWriteApi('some-api'));
    }

    public function testWithNoWriteApiFound()
    {
        $this->expectException(ApiNotFoundException::class);
        $registry = new ApiRegistry();
        $registry->getWriteApi('default');
    }

    public function testWithNoQueryApiFound()
    {
        $this->expectException(ApiNotFoundException::class);
        $registry = new ApiRegistry();
        $registry->getQueryApi('default');
    }


}
