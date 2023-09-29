<?php

namespace Stanford\OnCoreProtocolGenerator;

use ExternalModules\User;

class Contacts extends Clients
{
    private ?User $redcapUser = null;

    private array $oncoreContact = [];

    /**
     * @param $PREFIX
     * @param $mapping
     * @param $redcapUser
     */
    public function __construct($PREFIX, $mapping, $redcapUser)
    {
        parent::__construct($PREFIX, $mapping);
        $this->setRedcapUser($redcapUser);
    }

    private function getContactDetails()
    {
        try {
            $email = $this->getRedcapUser()->getEmail();
            $response = $this->get('contacts?email=' . $this->getRedcapUser()->getEmail());

            if ($response->getStatusCode() < 300) {
                $data = json_decode($response->getBody(), true);
                if (!empty($data)) {
                    $this->setOncoreContact($data[0]);
                }
            }
        } catch (\Exception $e) {
            $responseBodyAsString = $e->getResponse()->getBody()->getContents();
            \REDCap::logEvent('Could not get OnCore Contact Data for email ' . $email . '. ', $responseBodyAsString);
        }
    }

    public function getRedcapUser(): ?User
    {
        return $this->redcapUser;
    }

    public function setRedcapUser(?User $redcapUser): void
    {
        $this->redcapUser = $redcapUser;
        $this->getContactDetails();
    }



    public function getOncoreContact(): array
    {
        return $this->oncoreContact;
    }

    public function setOncoreContact(array $oncoreContact): void
    {
        $this->oncoreContact = $oncoreContact;
    }


}