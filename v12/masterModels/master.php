<?php

class Master
{
	private $id;
	private $password;
	private $email;
	private $name;
	private $phone;
	private $office_number;
	private $address;
	private $harga_point;
	private $point_pay;
	private $transaction_point_max;
	private $img;
	private $is_foodcourt;
	private $no_rekening;
	private $status;
	private $created_at;
	private $trial_untill;
	private $hold_untill;
	private $referrer;
	private $deposit_balance;
	private $organization;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'email' => $this->email,
			'name' => $this->name,
			'phone' => $this->phone,
			'office_number' => $this->office_number,
			'address' => $this->address,
			'harga_point' => $this->harga_point,
			'point_pay' => $this->point_pay,
			'transaction_point_max' => $this->transaction_point_max,
			'img' => $this->img,
			'is_foodcourt' => $this->is_foodcourt,
			'no_rekening' => $this->no_rekening,
			'status' => $this->status,
			'created_at' => $this->created_at,
			'trial_until' => $this->trial_until,
			'hold_untill' => $this->hold_untill,
			'referrer' => $this->referrer,
			'deposit_balance' => $this->deposit_balance,
			'organization' => $this->organization,
        ];
	}
	
	public function getFullDetails()
	{
		return [
			'id' => $this->id,
			'password' => $this->password,
			'email' => $this->email,
			'name' => $this->name,
			'phone' => $this->phone,
			'office_number' => $this->office_number,
			'address' => $this->address,
			'harga_point' => $this->harga_point,
			'point_pay' => $this->point_pay,
			'transaction_point_max' => $this->transaction_point_max,
			'img' => $this->img,
			'is_foodcourt' => $this->is_foodcourt,
			'no_rekening' => $this->no_rekening,
			'status' => $this->status,
			'created_at' => $this->created_at,
			'trial_until' => $this->trial_until,
			'hold_untill' => $this->hold_untill,
			'referrer' => $this->referrer,
			'deposit_balance' => $this->deposit_balance,
			'organization' => $this->organization,
        ];
	}
	
	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getPassword(){
		return $this->password;
	}

	public function setPassword($password){
		$this->password = $password;
	}

	public function getEmail(){
		return $this->email;
	}

	public function setEmail($email){
		$this->email = $email;
	}

	public function getName(){
		return $this->name;
	}

	public function setName($name){
		$this->name = $name;
	}

	public function getPhone(){
		return $this->phone;
	}

	public function setPhone($phone){
		$this->phone = $phone;
	}

	public function getOffice_number(){
		return $this->office_number;
	}

	public function setOffice_number($office_number){
		$this->office_number = $office_number;
	}

	public function getAddress(){
		return $this->address;
	}

	public function setAddress($address){
		$this->address = $address;
	}

	public function getHarga_point(){
		return $this->harga_point;
	}

	public function setHarga_point($harga_point){
		$this->harga_point = $harga_point;
	}

	public function getPoint_pay(){
		return $this->point_pay;
	}

	public function setPoint_pay($point_pay){
		$this->point_pay = $point_pay;
	}

	public function getTransaction_point_max(){
		return $this->transaction_point_max;
	}

	public function setTransaction_point_max($transaction_point_max){
		$this->transaction_point_max = $transaction_point_max;
	}

	public function getImg(){
		return $this->img;
	}

	public function setImg($img){
		$this->img = $img;
	}

	public function getIs_foodcourt(){
		return $this->is_foodcourt;
	}

	public function setIs_foodcourt($is_foodcourt){
		$this->is_foodcourt = $is_foodcourt;
	}

	public function getNo_rekening(){
		return $this->no_rekening;
	}

	public function setNo_rekening($no_rekening){
		$this->no_rekening = $no_rekening;
	}

	public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		$this->status = $status;
	}

	public function getCreated_at(){
		return $this->created_at;
	}

	public function setCreated_at($created_at){
		$this->created_at = $created_at;
	}

	public function getTrial_untill(){
		return $this->trial_untill;
	}

	public function setTrial_untill($trial_untill){
		$this->trial_untill = $trial_untill;
	}

	public function getHold_untill(){
		return $this->hold_untill;
	}

	public function setHold_untill($hold_untill){
		$this->hold_untill = $hold_untill;
	}

	public function getReferrer(){
		return $this->referrer;
	}

	public function setReferrer($referrer){
		$this->referrer = $referrer;
	}

	public function getDeposit_balance(){
		return $this->deposit_balance;
	}

	public function setDeposit_balance($deposit_balance){
		$this->deposit_balance = $deposit_balance;
	}
	public function getOrganization(){
		return $this->organization;
	}

	public function setOrganization($organization){
		$this->organization = $organization;
	}

}
