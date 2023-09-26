<?php
namespace Stanford\OnCoreProtocolGenerator;

require_once "classes/Clients.php";
require_once "classes/Protocols.php";
require_once "emLoggerTrait.php";

use Stanford\OnCoreProtocolGenerator\Clients;
class OnCoreProtocolGenerator extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    private ?\Stanford\OnCoreProtocolGenerator\Clients $client = null;

    private ?\Stanford\OnCoreProtocolGenerator\Protocols $protocol = null;

    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}

    public function getClient(): Clients
    {
        if(!$this->client){
            $this->setClient(new Clients($this->PREFIX));
        }
        return $this->client;
    }

    public function setClient(Clients $client): void
    {
        $this->client = $client;
    }

    public function getProtocol(): ?Protocols
    {
        if(!$this->protocol){
            $this->setProtocol(new Protocols($this->getClient()));
        }
        return $this->protocol;
    }

    public function setProtocol(?Protocols $protocol): void
    {
        $this->protocol = $protocol;
    }


}
