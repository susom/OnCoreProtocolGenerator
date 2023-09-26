<?php

namespace Stanford\OnCoreProtocolGenerator;

class ManagementDetails extends Protocols
{
    private int $protocolId;

    /**
     * @param int $protocolId
     */
    public function __construct(int $protocolId)
    {
        $this->protocolId = $protocolId;
    }

    protected function updateProtocolManagementDetails($data)
    {
        try {
            $response = $this->getClient()->put('protocolManagementDetails/' . $this->getProtocolId(), $data);
            if ($response->getStatusCode() == 204) {
                return array('status' => 'success');
            } else {
                $data = json_decode($response->getBody(), true);
                return $data;
            }
        } catch (\Exception $e) {
            \REDCap::logEvent('Could not Create Protocol ePRMS submission. Exception: ' . $e->getMessage());
        }
    }
}