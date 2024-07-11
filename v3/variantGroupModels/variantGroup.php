<?php

class VariantGroup
{
	private $id;
	private $id_menu;
	private $id_master;
	private $name;
	private $type;
	private $partner_id;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'id_menu' => $this->id_menu,
			'id_master' => $this->id_master,
			'name' => $this->name,
			'type' => $this->type,
			'partner_id' => $this->partner_id,
        ];
    }
    
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_menu(){
		return $this->id_menu;
	}

	public function setId_menu($id_menu){
		$this->id_menu = $id_menu;
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

	public function getType(){
		return $this->type;
	}

	public function setType($type){
		$this->type = $type;
	}
	
	public function getPartner_id(){
		return $this->partner_id;
	}

	public function setPartner_id($partner_id){
		$this->partner_id = $partner_id;
	}
	
}
