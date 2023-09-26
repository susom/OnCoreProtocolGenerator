<?php

namespace Stanford\OnCoreProtocolGenerator;

class Protocols
{

    private $client;

    private int $protocolId;

    /**
     * @param Clients $client
     */
    public function __construct(Clients $client)
    {
        $this->setClient($client);
    }

    /**
     * @return Clients
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Clients $client
     */
    public function setClient($client): void
    {
        $this->client = $client;
    }

    public function getProtocolId(): int
    {
        return $this->protocolId;
    }

    public function setProtocolId(int $protocolId): void
    {
        $this->protocolId = $protocolId;
    }


    protected function createProtocol($data)
    {
        try {
            $response = $this->getClient()->post("protocols/", $data);
            if ($response->getStatusCode() == 201) {
                $location = $response->getHeader('location');
                $parts = explode('/', end($location));
                $id = end($parts);
                $this->setProtocolId($id);
                return array('status' => 'success', 'protocol_id' => $id);
            } else {
                $data = json_decode($response->getBody(), true);
                return $data;
            }
        } catch (\Exception $e) {
            \REDCap::logEvent('Could not Create Protocol. Exception: ' . $e->getMessage());
        }
    }
}