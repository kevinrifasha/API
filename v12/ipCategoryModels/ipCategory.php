<?php

class IpCategory
{
	private $id;
	private $ip;
	private $id_category;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'ip' => $this->ip,
			'id_category' => $this->id_category,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getIp(){
		return $this->ip;
	}

	public function setIp($ip){
		$this->ip = $ip;
	}

	public function getId_category(){
		return $this->id_category;
	}

	public function setId_category($id_category){
		$this->id_category = $id_category;
	}
}
