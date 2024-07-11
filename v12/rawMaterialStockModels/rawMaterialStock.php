<?php

class RawMaterialStock
{
	private $id;
	private $id_raw_material;
	private $stock;
	private $id_metric;
	private $exp_date;
	private $id_goods_receipt_detail;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'id_raw_material' => $this->id_raw_material,
			'stock' => $this->stock,
			'id_metric' => $this->id_metric,
			'exp_date' => $this->exp_date,
			'id_goods_receipt_detail' => $this->id_goods_receipt_detail,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_raw_material(){
		return $this->id_raw_material;
	}

	public function setId_raw_material($id_raw_material){
		$this->id_raw_material = $id_raw_material;
	}

	public function getStock(){
		return $this->stock;
	}

	public function setStock($stock){
		$this->stock = $stock;
	}

	public function getId_metric(){
		return $this->id_metric;
	}

	public function setId_metric($id_metric){
		$this->id_metric = $id_metric;
	}

	public function getExp_date(){
		return $this->exp_date;
	}

	public function setExp_date($exp_date){
		$this->exp_date = $exp_date;
	}

	public function getId_goods_receipt_detail(){
		return $this->id_goods_receipt_detail;
	}

	public function setId_goods_receipt_detail($id_goods_receipt_detail){
		$this->id_goods_receipt_detail = $id_goods_receipt_detail;
	}
	
}
