<?php

class TransactionDetail
{
	private $id;
	private $id_transaksi;
	private $id_menu;
	private $harga_satuan;
	private $qty;
	private $notes;
	private $harga;
	private $variant;
	private $status;
	private $surcharge_change;

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
    }

	public function getDetails()
	{
		return [
            'id'=>$this->id,
            'id_transaksi'=>$this->id_transaksi,
            'id_menu'=>$this->id_menu,
            'harga_satuan'=>$this->harga_satuan,
            'qty'=>$this->qty,
            'notes'=>$this->notes,
            'harga'=>$this->harga,
            'variant'=>$this->variant,
            'status'=>$this->status,
            'surcharge_change'=>$this->surcharge_change,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_transaksi(){
		return $this->id_transaksi;
	}

	public function setId_transaksi($id_transaksi){
		$this->id_transaksi = $id_transaksi;
	}

	public function getId_menu(){
		return $this->id_menu;
	}

	public function setId_menu($id_menu){
		$this->id_menu = $id_menu;
	}

	public function getHarga_satuan(){
		return $this->harga_satuan;
	}

	public function setHarga_satuan($harga_satuan){
		$this->harga_satuan = $harga_satuan;
	}

	public function getQty(){
		return $this->qty;
	}

	public function setQty($qty){
		$this->qty = $qty;
	}

	public function getNotes(){
		return $this->notes;
	}

	public function setNotes($notes){
		$this->notes = $notes;
	}

	public function getHarga(){
		return $this->harga;
	}

	public function setHarga($harga){
		$this->harga = $harga;
	}

	public function getVariant(){
		$cut = $this->variant;
		$cut = substr($cut, 11);
		$cut = substr($cut, 0, -1);
		$cut = str_replace("'",'"',$cut);
		return $cut;
	}

	public function setVariant($variant){
		$this->variant = $variant;
	}

	public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		$this->status = $status;
	}

	public function getSurcharge_change(){
		return $this->surcharge_change;
	}

	public function setSurcharge_change($surcharge_change){
		$this->surcharge_change = $surcharge_change;
	}
    
}
