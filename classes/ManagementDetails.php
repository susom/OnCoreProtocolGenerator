<?php

namespace Stanford\OnCoreProtocolGenerator;

class ManagementDetails extends Clients
{
    private int $protocolId;

    private array $managementDetails = [];

    /**
     * @param int $protocolId
     */
    public function __construct($PREFIX, $mapping, int $protocolId, $redcapRecord)
    {
        parent::__construct($PREFIX, $mapping);
        $this->setProtocolId($protocolId);
        $this->setRedcapRecord($redcapRecord);
        $this->setManagementDetails();
    }


    public function prepareManagementDetailsData()
    {
        $mapping = $this->getMapping()[OnCoreProtocolGenerator::PROTOCOL_MANAGEMENT_DETAILS_MAPPING];
        $data = [];
        foreach ($mapping as $field) {
            if ($this->getRedcapRecord()[$field[OnCoreProtocolGenerator::REDCAP_PMD_FIELD]]) {
                $data[$field[OnCoreProtocolGenerator::ONCORE_PMD_FIELD]] = $this->getRedcapRecord()[$field[OnCoreProtocolGenerator::REDCAP_PMD_FIELD]];
            } else {
                $data[$field[OnCoreProtocolGenerator::ONCORE_PMD_FIELD]] = $field[OnCoreProtocolGenerator::ONCORE_PMD_DEFAULT_VALUE];
            }
        }
        return $data;
    }

    public function updateProtocolManagementDetails()
    {
        try {
            $data = $this->prepareManagementDetailsData();
            // if management details not defined then no need to update.
            if ($data) {
                $response = $this->put('protocolManagementDetails/' . $this->getProtocolId(), $data);
                if ($response->getStatusCode() == 204) {
                    // reset management details
                    $this->managementDetails = [];
                    $this->getManagementDetails();
                    return array('status' => 'success');
                } else {
                    return json_decode($response->getBody(), true);
                }
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not update Protocol Management Details. ' , $responseBodyAsString);
        }
    }

    public function getManagementDetails(): array
    {
        if (!$this->managementDetails) {
            $this->setManagementDetails();
        }
        return $this->managementDetails;
    }

    public function setManagementDetails(): void
    {
        try {
            if (!$this->protocolId) {
                throw new \Exception('Protocol Id not Found');
            }
            $response = $this->get('protocolManagementDetails/' . $this->getProtocolId());

            if ($response->getStatusCode() < 300) {
                $data = json_decode($response->getBody(), true);
                if (!empty($data)) {
                    $this->managementDetails = $data;
                }
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not get Protocol Data for protocolId ' . $this->protocolId . '. ' , $responseBodyAsString);
        }
    }

    public function getProtocolId(): int
    {
        return $this->protocolId;
    }

    public function setProtocolId(int $protocolId): void
    {
        $this->protocolId = $protocolId;
    }


}