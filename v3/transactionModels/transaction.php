<?php

class Transaction
{
	private $id;
    private $jam;
    private $phone;
    private $id_partner;
    private $no_meja;
    private $no_meja_foodcourt;
    private $status;
    private $total;
    private $id_voucher;
    private $id_voucher_redeemable;
    private $tipe_bayar;
    private $promo;
    private $diskon_spesial;
    private $employee_discount;
    private $point;
    private $queue;
    private $takeaway;
    private $notes;
    private $id_foodcourt;
    private $tax;
    private $service;
    private $charge_ewallet;
    private $charge_xendit;
    private $charge_ur;
    private $confirm_at;
    private $status_callback;
    private $callback_hit;
    private $surcharge_id;
    private $surcharge_percent;
    private $shift_id;
    private $customer_name;

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
    }

	public function getDetails()
	{
		return [
            'id'=>$this->id,
            'jam'=>$this->jam,
            'phone'=>$this->phone,
            'id_partner'=>$this->id_partner,
            'no_meja'=>$this->no_meja,
            'no_meja_foodcourt'=>$this->no_meja_foodcourt,
            'status'=>$this->status,
            'total'=>$this->total,
            'id_voucher'=>$this->id_voucher,
            'id_voucher_redeemable'=>$this->id_voucher_redeemable,
            'tipe_bayar'=>$this->tipe_bayar,
            'promo'=>$this->promo,
            'diskon_spesial'=>$this->diskon_spesial,
            'employee_discount'=>$this->employee_discount,
            'point'=>$this->point,
            'queue'=>$this->queue,
            'takeaway'=>$this->takeaway,
            'notes'=>$this->notes,
            'id_foodcourt'=>$this->id_foodcourt,
            'tax'=>$this->tax,
            'service'=>$this->service,
            'charge_ewallet'=>$this->charge_ewallet,
            'charge_xendit'=>$this->charge_xendit,
            'charge_ur'=>$this->charge_ur,
            'confirm_at'=>$this->confirm_at,
            'status_callback'=>$this->status_callback,
            'callback_hit'=>$this->callback_hit,
            'surcharge_id'=>$this->surcharge_id,
            'surcharge_percent'=>$this->surcharge_percent,
            'shift_id'=>$this->shift_id,
            'customer_name'=>$this->customer_name,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getJam(){
		return $this->jam;
	}

	public function setJam($jam){
		$this->jam = $jam;
	}

	public function getPhone(){
		return $this->phone;
	}

	public function setPhone($phone){
		$this->phone = $phone;
	}

	public function getId_partner(){
		return $this->id_partner;
	}

	public function setId_partner($id_partner){
		$this->id_partner = $id_partner;
	}

	public function getNo_meja(){
		return $this->no_meja;
	}

	public function setNo_meja($no_meja){
		$this->no_meja = $no_meja;
	}

	public function getNo_meja_foodcourt(){
		return $this->no_meja_foodcourt;
	}

	public function setNo_meja_foodcourt($no_meja_foodcourt){
		$this->no_meja_foodcourt = $no_meja_foodcourt;
	}

	public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		$this->status = $status;
	}

	public function getTotal(){
		return $this->total;
	}

	public function setTotal($total){
		$this->total = $total;
	}

	public function getId_voucher(){
		return $this->id_voucher;
	}

	public function setId_voucher($id_voucher){
		$this->id_voucher = $id_voucher;
	}

	public function getId_voucher_redeemable(){
		return $this->id_voucher_redeemable;
	}

	public function setId_voucher_redeemable($id_voucher_redeemable){
		$this->id_voucher_redeemable = $id_voucher_redeemable;
	}

	public function getTipe_bayar(){
		return $this->tipe_bayar;
	}

	public function setTipe_bayar($tipe_bayar){
		$this->tipe_bayar = $tipe_bayar;
	}

	public function getPromo(){
		return $this->promo;
	}

	public function setPromo($promo){
		$this->promo = $promo;
	}

	public function getDiskon_spesial(){
		return $this->diskon_spesial;
	}

	public function setDiskon_spesial($diskon_spesial){
		$this->diskon_spesial = $diskon_spesial;
	}

	public function getEmployee_discount(){
		return $this->employee_discount;
	}

	public function setEmployee_discount($employee_discount){
		$this->employee_discount = $employee_discount;
	}

	public function getPoint(){
		return $this->point;
	}

	public function setPoint($point){
		$this->point = $point;
	}

	public function getQueue(){
		return $this->queue;
	}

	public function setQueue($queue){
		$this->queue = $queue;
	}

	public function getTakeaway(){
		return $this->takeaway;
	}

	public function setTakeaway($takeaway){
		$this->takeaway = $takeaway;
	}

	public function getNotes(){
		return $this->notes;
	}

	public function setNotes($notes){
		$this->notes = $notes;
	}

	public function getId_foodcourt(){
		return $this->id_foodcourt;
	}

	public function setId_foodcourt($id_foodcourt){
		$this->id_foodcourt = $id_foodcourt;
	}

	public function getTax(){
		return $this->tax;
	}

	public function setTax($tax){
		$this->tax = $tax;
	}

	public function getService(){
		return $this->service;
	}

	public function setService($service){
		$this->service = $service;
	}

	public function getCharge_ewallet(){
		return $this->charge_ewallet;
	}

	public function setCharge_ewallet($charge_ewallet){
		$this->charge_ewallet = $charge_ewallet;
	}

	public function getCharge_xendit(){
		return $this->charge_xendit;
	}

	public function setCharge_xendit($charge_xendit){
		$this->charge_xendit = $charge_xendit;
	}

	public function getCharge_ur(){
		return $this->charge_ur;
	}

	public function setCharge_ur($charge_ur){
		$this->charge_ur = $charge_ur;
	}

	public function getConfirm_at(){
		return $this->confirm_at;
	}

	public function setConfirm_at($confirm_at){
		$this->confirm_at = $confirm_at;
	}

	public function getStatus_callback(){
		return $this->status_callback;
	}

	public function setStatus_callback($status_callback){
		$this->status_callback = $status_callback;
	}

	public function getCallback_hit(){
		return $this->callback_hit;
	}

	public function setCallback_hit($callback_hit){
		$this->callback_hit = $callback_hit;
	}

	public function getSurcharge_id(){
		return $this->surcharge_id;
	}

	public function setSurcharge_id($surcharge_id){
		$this->surcharge_id = $surcharge_id;
	}
	
	public function getSurcharge_percent(){
		return $this->surcharge_percent;
	}

	public function setSurcharge_percent($surcharge_percent){
		$this->surcharge_percent = $surcharge_percent;
	}
	
	public function getShift_id(){
		return $this->shift_id;
	}
	
	public function setShift_id($shift_id){
		$this->shift_id = $shift_id;
	}
	
	public function getCustomer_Name(){
		return $this->customer_name;
	}
	
	public function setCustomer_Name($shift_id){
		$this->customer_name = $customer_name;
	}
    
}
