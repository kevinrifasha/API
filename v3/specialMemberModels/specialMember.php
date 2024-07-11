<?php

class SpecialMember
{
	private $id;
	private $id_master;
	private $phone;
	private $max_disc;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'id_master' => $this->id_master,
			'phone' => $this->phone,
			'max_disc' => $this->max_disc,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_master(){
		return $this->id_master;
	}

	public function setId_master($id_master){
		$this->id_master = $id_master;
	}

	public function getPhone(){
		return $this->phone;
	}

	public function setPhone($phone){
		$this->phone = $phone;
	}

	public function getMax_disc(){
		return $this->max_disc;
	}

	public function setMax_disc($max_disc){
		$this->max_disc = $max_disc;
	}

}
