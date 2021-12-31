<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\InfluxDb;

use Closure;
use InfluxDB2\QueryApi;
use InfluxDB2\WriteApi;

class ApiRegistry
{
    protected array $queryClosures = [];
    protected array $writeClosures = [];

    /**
     * Adds a new InfluxDB2 query API to the registry.
     * @param string $name Client name
     * @param Closure $apiClosure
     * @return void
     */
    public function addQueryApi(string $name, Closure $apiClosure): void
    {
        $this->queryClosures[$name] = $apiClosure;
    }

    /**
     * Adds a new InfluxDB2 write API to the registry.
     * @param string $name Client name
     * @param Closure $apiClosure
     * @return void
     */
    public function addWriteApi(string $name, Closure $apiClosure): void
    {
        $this->writeClosures[$name] = $apiClosure;
    }

    /**
     * Checks if a specific query API exists.
     * @param string $name Client name
     * @return bool
     */
    public function hasQueryApi(string $name): bool
    {
        return array_key_exists($name, $this->queryClosures) && $this->queryClosures;
    }

    /**
     * Checks if a specific write API exists.
     * @param string $name Client name
     * @return bool
     */
    public function hasWriteApi(string $name): bool
    {
        return array_key_exists($name, $this->queryClosures);
    }

    /**
     * @param string $name API name
     * @return QueryApi
     */
    public function getQueryApi(string $name): QueryApi
    {
        if (!$this->hasQueryApi($name)) {
            throw new \InvalidArgumentException(sprintf('Query API "%s" not found', $name));
        }

        return $this->queryClosures[$name]();
    }

    /**
     * @param string $name API name
     * @return WriteApi
     */
    public function getWriteApi(string $name): WriteApi
    {
        if (!$this->hasWriteApi($name)) {
            throw new \InvalidArgumentException(sprintf('Write API "%s" not found', $name));
        }

        return $this->queryClosures[$name]();
    }

}
