<?php

class TransactionDelivery
{
	private $id;
	private $transaksi_id;
	private $alamat;
	private $longitude;
	private $latitude;
	private $notes;
	private $ongkir;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'transaksi_id' => $this->transaksi_id,
			'alamat' => $this->alamat,
			'longitude' => $this->longitude,
			'latitude' => $this->latitude,
			'notes' => $this->notes,
			'ongkir' => $this->ongkir,
        ];
    }
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getTransaksi_id(){
		return $this->transaksi_id;
	}

	public function setTransaksi_id($transaksi_id){
		$this->transaksi_id = $transaksi_id;
	}

	public function getAlamat(){
		return $this->alamat;
	}

	public function setAlamat($alamat){
		$this->alamat = $alamat;
	}

	public function getLongitude(){
		return $this->longitude;
	}

	public function setLongitude($longitude){
		$this->longitude = $longitude;
	}

	public function getLatitude(){
		return $this->latitude;
	}

	public function setLatitude($latitude){
		$this->latitude = $latitude;
	}

	public function getNotes(){
		return $this->notes;
	}

	public function setNotes($notes){
		$this->notes = $notes;
	}

	public function getOngkir(){
		return $this->ongkir;
	}

	public function setOngkir($ongkir){
		$this->ongkir = $ongkir;
	}
}
