<?php

class MenusVariantGroups
{
	private $id;
	private $menu_id;
	private $variant_group_id;
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
			'menu_id' => $this->menu_id,
			'variant_group_id' => $this->variant_group_id,
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

	public function getMenu_id(){
		return $this->menu_id;
	}

	public function setMenu_id($menu_id){
		$this->menu_id = $menu_id;
	}

	public function getVariant_group_id(){
		return $this->variant_group_id;
	}

	public function setVariant_group_id($variant_group_id){
		$this->variant_group_id = $variant_group_id;
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
