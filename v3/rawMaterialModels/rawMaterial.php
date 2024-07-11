<?php

class RawMaterial
{
	private $id;
	private $id_master;
	private $id_partner;
	private $name;
	private $reminder_allert;
	private $id_metric;
	private $unit_price;
	private $id_metric_price;
	private $category_id;
	private $categoryName;
	private $yieldRM;
    

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
			'id_partner' => $this->id_partner,
			'name' => $this->name,
			'reminder_allert' => $this->reminder_allert,
			'id_metric' => $this->id_metric,
			'unit_price' => $this->unit_price,
			'id_metric_price' => $this->id_metric_price,
			'category_id' => $this->category_id,
			'categoryName'=>$this->categoryName,
			'yieldRM'=>$this->yieldRM
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

	public function getId_partner(){
		return $this->id_partner;
	}

	public function setId_partner($id_partner){
		$this->id_partner = $id_partner;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getReminder_allert(){
		return $this->reminder_allert;
	}

	public function setReminder_allert($reminder_allert){
		$this->reminder_allert = $reminder_allert;
	}

	public function getId_metric(){
		return $this->id_metric;
	}

	public function setId_metric($id_metric){
		$this->id_metric = $id_metric;
	}
	public function getUnit_price(){
		return $this->unit_price;
	}

	public function setUnit_price($unit_price){
		$this->unit_price = $unit_price;
	}

	public function getId_metric_price(){
		return $this->id_metric_price;
	}

	public function setId_metric_price($id_metric_price){
		$this->id_metric_price = $id_metric_price;
	}
	public function getCategory_id(){
		return $this->category_id;
	}

	public function setCategory_id($category_id){
		$this->category_id = $category_id;
	}
	public function getCategoryName(){
		return $this->categoryName;
	}

	public function setCategoryName($categoryName){
		$this->categoryName = $categoryName;
	}
	
	public function getYieldRM(){
		return $this->yieldRM;
	}
	
	public function setYieldRM($yieldRM){
		$this->yieldRM = $yieldRM;
	}
}
