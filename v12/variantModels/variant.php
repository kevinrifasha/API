<?php

class Variant
{
	private $id;
	private $id_variant_group;
	private $name;
	private $price;
	private $stock;
	private $is_recipe;
	private $cogs;

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'id_variant_group' => $this->id_variant_group,
			'name' => $this->name,
			'price' => $this->price,
			'stock' => $this->stock,
			'is_recipe' => $this->is_recipe,
			'cogs' => $this->cogs,
        ];
    }
    
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_variant_group(){
		return $this->id_variant_group;
	}

	public function setId_variant_group($id_variant_group){
		$this->id_variant_group = $id_variant_group;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getPrice(){
		return $this->price;
	}

	public function setPrice($price){
		$this->price = $price;
	}

	public function getStock(){
		return $this->stock;
	}

	public function setStock($stock){
		$this->stock = $stock;
	}

	public function getIs_recipe(){
		return $this->is_recipe;
	}

	public function setIs_recipe($is_recipe){
		$this->is_recipe = $is_recipe;
	}
	
	public function getCogs(){
		return $this->cogs;
	}

	public function setCogs($cogs){
		$this->cogs = $cogs;
	}
	
}
