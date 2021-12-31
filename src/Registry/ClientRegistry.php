<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Registry;

use InfluxDB2\Client;

class ClientRegistry
{
    protected array $clientClosures = [];

    /**
     * Adds a new InfluxDB2 client to the registry.
     * @param string $id
     * @param Client $client
     * @return void
     */
    public function addClient(string $id, Client $client): void
    {
        if (str_starts_with($id, "babymarkt_influxdb2.")) {
            if (preg_match('#babymarkt_influxdb2\.([^\s]+?)_client#is', $id, $matches)) {
                $id = $matches[1];
            }
        }

        $this->clientClosures[$id] = $client;
    }

    /**
     * Checks if a specific client exists.
     * @param string $name Client name
     * @return bool
     */
    public function hasClient(string $name): bool
    {
        return array_key_exists($name, $this->clientClosures);
    }

    /**
     * @param string $name Client name
     * @return Client
     */
    public function getClient(string $name): Client
    {
        if (!$this->hasClient($name)) {
            throw new ClientNotFoundException(sprintf('Client "%s" not found', $name));
        }

        return $this->clientClosures[$name];
    }

}
