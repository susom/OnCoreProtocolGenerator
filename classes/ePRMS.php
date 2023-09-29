<?php

namespace Stanford\OnCoreProtocolGenerator;

class ePRMS extends Clients
{
    private int $protocolId;

    private array $eprmsSubmisions = [];
    /**
     * @param int $protocolId
     */
    public function __construct($PREFIX, $mapping, int $protocolId, $redcapRecord)
    {
        parent::__construct($PREFIX, $mapping);
        $this->setProtocolId($protocolId);
        $this->setRedcapRecord($redcapRecord);
        $this->setEprmsSubmisions();
    }

    protected function createProtocolEprmsSubmission($data)
    {
        try {
            $data['protocolId'] = $this->getProtocolId();
            $response = $this->post('protocolEprmsSubmissions', $data);
            if ($response->getStatusCode() == 201) {
                $location = $response->getHeader('location');
                $parts = explode('/', end($location));
                $id = end($parts);
                $this->setProtocolId($id);

                // update ePRMS list. API does not provide endpoint to pull single submission.
                $this->setEprmsSubmisions();
                return array('status' => 'success', 'protocol_eprms_submission_id' => $id);
            } else {
                $data = json_decode($response->getBody(), true);
                return $data;
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not Create Protocol ePRMS submission. Exception: ' , $responseBodyAsString);
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

    public function getEprmsSubmisions(): array
    {
        return $this->eprmsSubmisions;
    }

    public function setEprmsSubmisions(): void
    {
        try {
            if (!$this->protocolId) {
                throw new \Exception('Protocol Id not Found');
            }
            $response = $this->get('protocolEprmsSubmissions?protocolId=' . $this->getProtocolId());

            if ($response->getStatusCode() < 300) {
                $data = json_decode($response->getBody(), true);
                if (!empty($data)) {
                    $this->eprmsSubmisions = $data;
                }
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not get Protocol Data for protocolId ' . $this->protocolId . '. ' , $responseBodyAsString);
        }
    }


}