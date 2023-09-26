<?php

namespace Stanford\OnCoreProtocolGenerator;

class ePRMS extends Protocols
{
    private int $protocolId;

    /**
     * @param int $protocolId
     */
    public function __construct(int $protocolId)
    {
        $this->protocolId = $protocolId;
    }

    protected function createProtocolEprmsSubmissions($data)
    {
        try {
            $data['protocolId'] = $this->getProtocolId();
            $response = $this->getClient()->post('protocolEprmsSubmissions', $data);
            if ($response->getStatusCode() == 201) {
                $location = $response->getHeader('location');
                $parts = explode('/', end($location));
                $id = end($parts);
                $this->setProtocolId($id);
                return array('status' => 'success', 'protocol_eprms_submission_id' => $id);
            } else {
                $data = json_decode($response->getBody(), true);
                return $data;
            }
        } catch (\Exception $e) {
            \REDCap::logEvent('Could not Create Protocol ePRMS submission. Exception: ' . $e->getMessage());
        }
    }
}