<?php

class GoodsReceipt
{
	private $id;
	private $delivery_order_number;
	private $id_master;
	private $sender;
	private $recieve_date;
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
			'delivery_order_number' => $this->delivery_order_number,
			'id_master' => $this->id_master,
			'id_partner' => $this->id_partner,
			'sender' => $this->sender,
			'recieve_date' => $this->recieve_date,
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
    
    public function getDelivery_order_number(){
		return $this->delivery_order_number;
	}

	public function setDelivery_order_number($delivery_order_number){
		$this->delivery_order_number = $delivery_order_number;
	}

    public function getId_master(){
		return $this->id_master;
	}

	public function setId_master($id_masyer){
		$this->id_masyer = $id_masyer;
	}

    public function getId_partner(){
		return $this->id_partner;
	}

	public function setId_partner($id_partner){
		$this->id_partner = $id_partner;
	}

    public function getSender(){
		return $this->sender;
	}

	public function setSender($sender){
		$this->sender = $sender;
	}

    public function getRecieve_date(){
		return $this->recieve_date;
	}

	public function setRecieve_date($recieve_date){
		$this->recieve_date = $recieve_date;
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
