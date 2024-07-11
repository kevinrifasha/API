<?php

class Category
{
	private $id;
	private $id_master;
	private $name;
	private $sequence;
	private $department_id;

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
			'name' => $this->name,
			'sequence' => $this->sequence,
			'department_id' => $this->department_id,
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

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getSequence(){
		return $this->sequence;
	}

	public function setSequence($sequence){
		$this->sequence = $sequence;
	}
	public function getDepartment_id(){
		return $this->department_id;
	}

	public function setDepartment_id($department_id){
		$this->department_id = $department_id;
	}
}
