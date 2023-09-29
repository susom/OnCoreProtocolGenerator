<?php

namespace Stanford\OnCoreProtocolGenerator;

require_once "classes/Clients.php";
require_once "classes/Protocols.php";
require_once "classes/Contacts.php";
require_once "classes/ManagementDetails.php";
require_once "emLoggerTrait.php";

use Stanford\OnCoreProtocolGenerator\Clients;

class OnCoreProtocolGenerator extends \ExternalModules\AbstractExternalModule
{

    const PROTOCOL_MAPPING = 'protocol_mapping';

    const PROTOCOL_MANAGEMENT_DETAILS_MAPPING = 'protocol_management_details_mapping';

    const EPRMS_MAPPING = 'eprms_mapping';

    const ONCORE_PROTOCOL_ID = 'oncore-protocol-id';
    const ONCORE_PROTOCOL_FIELD = 'oncore-protocol-field';

    const REDCAP_PROTOCOL_FIELD = 'redcap-protocol-field';

    const ONCORE_PROTOCOL_DEFAULT_VALUE = 'oncore-protocol-default-value';
    const ONCORE_PMD_FIELD = 'oncore-pmd-field';

    const REDCAP_PMD_FIELD = 'redcap-pmd-field';

    const ONCORE_PMD_DEFAULT_VALUE = 'oncore-pmd-default-value';
    const ONCORE_EPRMS_FIELD = 'oncore-eprms-field';

    const REDCAP_EPRMS_FIELD = 'redcap-eprms-field';

    const ONCORE_EPRMS_DEFAULT_VALUE = 'oncore-eprms-default-value';
    use emLoggerTrait;

    private ?\Stanford\OnCoreProtocolGenerator\Clients $client = null;

    private ?\Stanford\OnCoreProtocolGenerator\Protocols $protocol = null;


    private ?\Stanford\OnCoreProtocolGenerator\Contacts $contact = null;

    private array $redcapRecord = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function redcap_save_record($project_id, $record = null, $instrument, $event_id, $group_id = null, $survey_hash = null, $response_id = null, $repeat_instance = 1)
    {
        if ($record) {
            $this->setRedcapRecord($record);

            // if record satisfy trigger logic.
            if ($instrument == $this->getProjectSetting('trigger-instrument') and $this->getRedcapRecord() and $this->getContact()->getOncoreContact()) {
                $this->getProtocol()->createProtocol();

                // if protocol was created then add logged-in user as a staff.
                if($this->getProtocol()->getProtocolId()){
                    \REDCap::logEvent('New OnCore Protocol '.$this->getProtocol()->getProtocolId().' Created for record ' . $record);
                    $this->getProtocol()->addProtocolStaff($this->getContact()->getOncoreContact(), $this->getProjectSetting('default-protocol-staff-role'), $this->getProjectSetting('default-oncore-additional-organization-access'));

                    // if we defined field to save oncore protocol id. then update redcap record with protocol id.
                    if($this->getProjectSetting(self::ONCORE_PROTOCOL_ID) != ''){
                        $data[\REDCap::getRecordIdField()] = $record;
                        $data['redcap_event_name'] = $this->getFirstEventId();
                        $data[$this->getProjectSetting(self::ONCORE_PROTOCOL_ID)] = $this->getProtocol()->getProtocolId();
                        $this->updateREDCapRecord($data);
                    }


                }
            } elseif(!$this->getContact()->getOncoreContact()) {
                \REDCap::logEvent('No Contact Found.', 'No OnCore Contact found for ' . $this->framework->getUser()->getUsername());
            }

            // if protocol already created for this record just load it from OnCore.
            $oncoreProtocolIdField = $this->getProjectSetting(self::ONCORE_PROTOCOL_ID);
            if($this->getRedcapRecord()[$oncoreProtocolIdField]){
                $this->getProtocol()->setProtocolId($this->getRedcapRecord()[$oncoreProtocolIdField]);

                // testing
                $this->getProtocol()->addProtocolStaff($this->getContact()->getOncoreContact(), $this->getProjectSetting('default-protocol-staff-role'), $this->getProjectSetting('default-oncore-additional-organization-access'));

            }
        }
    }

    public function updateREDCapRecord($data){
        $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
        if(!empty($response['errors'])){
            \REDCap::logEvent('Could not update REDCap Record'. implode(',', $response['errors']));
        }
    }

    public function getMapping(): array
    {
        return array(
            'protocol_mapping' => $this->getSubSettings('protocol_mapping', $this->getProjectId()),
            'protocol_management_details_mapping' => $this->getSubSettings('protocol_management_details_mapping', $this->getProjectId()),
            'eprms_mapping' => $this->getSubSettings('eprms_mapping', $this->getProjectId()),
        );
    }

    public function getProtocol(): ?Protocols
    {
        if (!$this->protocol) {

            $this->setProtocol(new Protocols($this->PREFIX, $this->getMapping(), $this->getRedcapRecord()));
        }
        return $this->protocol;
    }

    public function setProtocol(?Protocols $protocol): void
    {
        $this->protocol = $protocol;
    }

    public function getRedcapRecord(): array
    {
        return $this->redcapRecord;
    }

    public function setRedcapRecord(int $redcapId): void
    {
        $param = array(
            'project_id' => $this->getProjectId(),
            'return_format' => 'array',
            'records' => [$redcapId],
            'filterLogic' => $this->getProjectSetting('logic-trigger')
//            'events' => $this->getBranchEventId()
        );
        $data = \REDCap::getData($param);
        $this->redcapRecord = $data[$redcapId][$this->getFirstEventId()];
    }

    public function getContact(): ?Contacts
    {
        if (!$this->contact) {
            $user = $this->framework->getUser();
            $this->setContact(new Contacts($this->PREFIX, $this->getMapping(), $user));
        }
        return $this->contact;
    }

    public function setContact(?Contacts $contact): void
    {
        $this->contact = $contact;
    }
}
