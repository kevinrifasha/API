<?php

class Printer
{
	private $id;
	private $partnerId;
	private $ip;
	private $name;
	private $mac_address;
	private $isReceipt;
	private $isFullChecker;
	private $isCategoryChecker;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'partnerId' => $this->partnerId,
			'ip' => $this->ip,
            'name' => $this->name,
            'mac_address' => $this->mac_address,
            'isReceipt' => $this->isReceipt,
            'isFullChecker' => $this->isFullChecker ,
            'isCategoryChecker' => $this->isCategoryChecker ,
        ];
    }
    
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

    public function getPartnerId(){
		return $this->partnerId;
	}

	public function setPartnerId($partnerId){
		$this->partnerId = $partnerId;
	}

	public function getIp(){
		return $this->ip;
	}

	public function setIp($ip){
		$this->ip = $ip;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getMacAddress(){
		return $this->mac_address;
	}

	public function setName($mac_address){
		$this->mac_address = $mac_address;
	}

	public function getIsReceipt(){
		return $this->isReceipt;
	}

	public function setIsReceipt($isReceipt){
		$this->isReceipt = $isReceipt;
	}

	public function getIsFullChecker(){
		return $this->isFullChecker;
	}

	public function setIsFullChecker($isFullChecker){
		$this->isFullChecker = $isFullChecker;
	}

	public function getIsCategoryChecker(){
		return $this->isCategoryChecker;
	}

	public function setIsCategoryChecker($isCategoryChecker){
		$this->isCategoryChecker = $isCategoryChecker;
	}
	
}