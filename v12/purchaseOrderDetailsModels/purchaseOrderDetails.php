<?php

class PurchaseOrderDetails
{
	private $id;
	private $purchase_order_id;
	private $raw_id;
	private $menu_id;
	private $qty;
	private $metric_id;
	private $price;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'purchase_order_id' => $this->purchase_order_id,
			'raw_id' => $this->raw_id,
			'menu_id' => $this->menu_id,
			'qty' => $this->qty,
			'metric_id' => $this->metric_id,
			'price' => $this->price,
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

	public function getPurchase_order_id(){
		return $this->purchase_order_id;
	}
	
	public function setPurchase_order_id($purchase_order_id){
		$this->purchase_order_id = $purchase_order_id;
	}

	public function getRaw_id(){
		return $this->raw_id;
	}
	
	public function setRaw_id($raw_id){
		$this->raw_id = $raw_id;
	}

	public function getQty(){
		return $this->qty;
	}
	
	public function setQty($qty){
		$this->qty = $qty;
	}

	public function getMetric_id(){
		return $this->metric_id;
	}
	
	public function setMetric_id($metric_id){
		$this->metric_id = $metric_id;
	}

	public function getPrice(){
		return $this->price;
	}
	
	public function setPrice($price){
		$this->price = $price;
	}

}
