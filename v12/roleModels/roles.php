<?php

class Role
{
	private $id;
	private $name;
	private $is_owner;
	private $created_at;
	private $updated_at;
	private $deleted_at;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'is_owner' => $this->is_owner,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
			'deleted_at' => $this->deleted_at,
        ];
    }

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getIs_owner(){
		return $this->is_owner;
	}

	public function setIs_owner($is_owner){
		$this->is_owner = $is_owner;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getCreated_at(){
		return $this->created_at;
	}

	public function setCreated_at($created_at){
		$this->created_at = $created_at;
	}

	public function getUpdated_at(){
		return $this->updated_at;
	}

	public function setUpdated_at($updated_at){
		$this->updated_at = $updated_at;
	}

	public function getDeleted_at(){
		return $this->deleted_at;
	}

	public function setDeleted_at($deleted_at){
		$this->deleted_at = $deleted_at;
	}

}
