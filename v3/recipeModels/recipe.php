<?php

class Recipe
{
	private $id;
	private $id_menu;
	private $id_raw;
	private $qty;
	private $id_metric;
	private $id_variant;
	private $id_partner;

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
			'id_raw' => $this->id_raw,
			'qty' => $this->qty,
			'id_metric' => $this->id_metric,
			'id_variant' => $this->id_variant,
			'id_partner' => $this->id_partner,
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

	public function getId_raw(){
		return $this->id_raw;
	}

	public function setId_raw($id_raw){
		$this->id_raw = $id_raw;
	}

	public function getQty(){
		return $this->qty;
	}

	public function setQty($qty){
		$this->qty = $qty;
	}

	public function getId_metric(){
		return $this->id_metric;
	}

	public function setId_metric($id_metric){
		$this->id_metric = $id_metric;
	}

	public function getId_variant(){
		return $this->id_variant;
	}

	public function setId_variant($id_variant){
		$this->id_variant = $id_variant;
	}
	
	public function getId_partner(){
		return $this->id_partner;
	}

	public function setId_partner($id_partner){
		$this->id_partner = $id_partner;
	}
}
