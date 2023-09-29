<?php

namespace Stanford\OnCoreProtocolGenerator;

class Protocols extends Clients
{

    private int $protocolId;

    private array $protocol = [];


    private ?\Stanford\OnCoreProtocolGenerator\ManagementDetails $managementDetails = null;
    private ?\Stanford\OnCoreProtocolGenerator\ePRMS $ePRMS = null;

    public function __construct($PREFIX, $mapping, $redcapRecord)
    {
        parent::__construct($PREFIX, $mapping);
        $this->setRedcapRecord($redcapRecord);
    }


    public function getProtocolId(): int
    {
        return $this->protocolId;
    }

    public function setProtocolId(int $protocolId): void
    {
        $this->protocolId = $protocolId;
        $this->setManagementDetails(new ManagementDetails($this->getPREFIX(), $this->getMapping(), $protocolId, $this->getRedcapRecord()));
        //$this->setEPRMS(new ePRMS($this->getPREFIX(), $this->getMapping(), $protocolId));
    }

    public function prepareProtocolData()
    {
        $mapping = $this->getMapping()[OnCoreProtocolGenerator::PROTOCOL_MAPPING];
        $data = [];
        foreach ($mapping as $field) {
            if ($this->getRedcapRecord()[$field[OnCoreProtocolGenerator::REDCAP_PROTOCOL_FIELD]]) {
                $data[$field[OnCoreProtocolGenerator::ONCORE_PROTOCOL_FIELD]] = $this->getRedcapRecord()[$field[OnCoreProtocolGenerator::REDCAP_PROTOCOL_FIELD]];
            } else {
                $data[$field[OnCoreProtocolGenerator::ONCORE_PROTOCOL_FIELD]] = $field[OnCoreProtocolGenerator::ONCORE_PROTOCOL_DEFAULT_VALUE];
            }
        }
        return $data;
    }

    public function addProtocolStaff($contact, $role, $additionalOrganizationAccess)
    {
        try {
            $data = array(
                'protocolId' => $this->getProtocolId(),
                'contactId' => $contact['contactId'],
                'role' => $role,
            );
            $response = $this->post("protocolStaff/", $data);
            if ($response->getStatusCode() == 201) {
                \REDCap::logEvent('New Protocol Staff was added.', 'Contact ' . $contact['email'] . ' was added to Protocol ' . $this->getProtocolId());
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not Create Protocol.', $responseBodyAsString);
        }
    }

    public function createProtocol()
    {
        try {
            $data = $this->prepareProtocolData();
            $response = $this->post("protocols/", $data);
            if ($response->getStatusCode() == 201) {
                $location = $response->getHeader('location');
                $parts = explode('/', end($location));
                $id = end($parts);
                $this->setProtocolId($id);

                // now try to update Protocol Management Details with REDCap record data.
                $this->getManagementDetails()->updateProtocolManagementDetails();

                return array('status' => 'success', 'protocol_id' => $id);
            } else {
                throw new \Exception('Error status code: ' . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not Create Protocol.', $responseBodyAsString);
        }
    }

    public function getProtocol(): array
    {
        if (!$this->protocol) {
            $this->setProtocol();
        }
        return $this->protocol;
    }

    public function setProtocol(): void
    {
        try {
            if (!$this->protocolId) {
                throw new \Exception('Protocol Id not Found');
            }
            $response = $this->get('protocols/' . $this->protocolId);
            if ($response->getStatusCode() < 300) {
                $temp = $response->getBody()->getContents();
                $temp = str_replace('\"', '', $temp);
                $protocol = json_decode($temp, true);
                $this->protocol = $protocol;
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not get Protocol Data for protocolId ' . $this->protocolId . '. ', $responseBodyAsString);
        }
    }

    public function getManagementDetails(): ?ManagementDetails
    {
        return $this->managementDetails;
    }

    public function setManagementDetails(?ManagementDetails $managementDetails): void
    {
        $this->managementDetails = $managementDetails;
    }

    public function getEPRMS(): ?ePRMS
    {
        return $this->ePRMS;
    }

    public function setEPRMS(?ePRMS $ePRMS): void
    {
        $this->ePRMS = $ePRMS;
    }

}