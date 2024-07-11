<?php

class Membership
{
	private $id;
	private $user_phone;
	private $master_id;
	private $point;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'user_phone' => $this->user_phone,
			'master_id' => $this->master_id,
			'point' => $this->point,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getUser_phone(){
		return $this->user_phone;
	}

	public function setUser_phone($user_phone){
		$this->user_phone = $user_phone;
	}

	public function getMaster_id(){
		return $this->master_id;
	}

	public function setMaster_id($master_id){
		$this->master_id = $master_id;
	}

	public function getPoint(){
		return $this->point;
	}

	public function setPoint($point){
		$this->point = $point;
	}
}
