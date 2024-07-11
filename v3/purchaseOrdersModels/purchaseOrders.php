<?php

class PurchaseOrders
{
	private $id;
	private $supplier_id;
	private $master_id;
	private $partner_id;
	private $total;
	private $created_at;
	private $created_by;
	private $no;


	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'supplier_id' => $this->supplier_id,
			'partner_id' => $this->partner_id,
			'master_id' => $this->master_id,
			'total' => $this->total,
			'created_at' => $this->created_at,
			'created_by' => $this->created_by,
			'no'=>$this->no,
        ];
    }
    public function getNo(){
		return $this->no;
	}

	public function setNo($no){
		$this->no = $no;
	}
	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getSupplier_id(){
		return $this->supplier_id;
	}

	public function setSupplier_id($supplier_id){
		$this->supplier_id = $supplier_id;
	}

	public function getTotal(){
		return $this->total;
	}

	public function setTotal($total){
		$this->total = $total;
	}

	public function getPartner_id(){
		return $this->partner_id;
	}

	public function setPartner_id($partner_id){
		$this->partner_id = $partner_id;
	}

	public function getMaster_id(){
		return $this->master_id;
	}

	public function setMaster_id($master_id){
		$this->master_id = $master_id;
	}

	public function getCreated_by(){
		return $this->created_by;
	}

	public function setCreated_by($created_by){
		$this->created_by = $created_by;
	}

}
