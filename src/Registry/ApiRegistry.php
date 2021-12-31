<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Registry;

use InfluxDB2\QueryApi;
use InfluxDB2\WriteApi;

class ApiRegistry
{
    protected array $queryApis = [];
    protected array $writeApis = [];

    /**
     * Adds a new InfluxDB2 query API to the registry.
     * @param string $id Client name
     * @param QueryApi $queryApi
     * @return void
     */
    public function addQueryApi(string $id, QueryApi $queryApi): void
    {
        if (str_starts_with($id, "babymarkt_influxdb2.")) {
            if (preg_match('#babymarkt_influxdb2\.([^\s]+?)_query_api#is', $id, $matches)) {
                $id = $matches[1];
            }
        }

        $this->queryApis[$id] = $queryApi;
    }

    /**
     * Adds a new InfluxDB2 write API to the registry.
     * @param string $id api name
     * @param WriteApi $writeApi
     * @return void
     */
    public function addWriteApi(string $id, WriteApi $writeApi): void
    {
        if (str_starts_with($id, "babymarkt_influxdb2.")) {
            if (preg_match('#babymarkt_influxdb2\.([^\s]+?)_write_api#is', $id, $matches)) {
                $id = $matches[1];
            }
        }

        $this->writeApis[$id] = $writeApi;
    }

    /**
     * Checks if a specific query API exists.
     * @param string $name Client name
     * @return bool
     */
    public function hasQueryApi(string $name): bool
    {
        return array_key_exists($name, $this->queryApis);
    }

    /**
     * Checks if a specific write API exists.
     * @param string $name Client name
     * @return bool
     */
    public function hasWriteApi(string $name): bool
    {
        return array_key_exists($name, $this->writeApis);
    }

    /**
     * @param string $name API name
     * @return QueryApi
     * @throws ApiNotFoundException
     */
    public function getQueryApi(string $name): QueryApi
    {
        if (!$this->hasQueryApi($name)) {
            throw new ApiNotFoundException(sprintf('Query API "%s" not found', $name));
        }

        return $this->queryApis[$name];
    }

    /**
     * @param string $name API name
     * @return WriteApi
     * @throws ApiNotFoundException
     */
    public function getWriteApi(string $name): WriteApi
    {
        if (!$this->hasWriteApi($name)) {
            throw new ApiNotFoundException(sprintf('Write API "%s" not found', $name));
        }

        return $this->writeApis[$name];
    }

}
