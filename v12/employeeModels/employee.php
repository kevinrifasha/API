<?php

class Employee
{
	private $id;
	private $nik;
	private $nama;
	private $gender;
	private $phone;
	private $email;
	private $pin;
	private $id_master;
	private $id_partner;
	private $role_id;
	private $pattern_id;
	private $show_as_server;
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
			'nik' => $this->nik,
			'nama' => $this->nama,
			'gender' => $this->gender,
			'phone' => $this->phone,
			'email' => $this->email,
			// 'pin' => $this->pin,
			'id_master' => $this->id_master,
			'id_partner' => $this->id_partner,
			'role_id' => $this->role_id,
			'pattern_id' => $this->pattern_id,
			'show_as_server' => $this->show_as_server,
			'organization' => $this->organization,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getNik(){
		return $this->nik;
	}

	public function setNik($nik){
		$this->nik = $nik;
	}

	public function getNama(){
		return $this->nama;
	}

	public function setNama($nama){
		$this->nama = $nama;
	}

	public function getGender(){
		return $this->gender;
	}

	public function setGender($gender){
		$this->gender = $gender;
	}

	public function getPhone(){
		return $this->phone;
	}

	public function setPhone($phone){
		$this->phone = $phone;
	}

	public function getEmail(){
		return $this->email;
	}

	public function setEmail($email){
		$this->email = $email;
	}

	public function getPin(){
		return $this->pin;
	}

	public function setPin($pin){
		$this->pin = $pin;
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
	public function getRole_id(){
		return $this->role_id;
	}

	public function setRole_id($role_id){
		$this->role_id = $role_id;
	}

	public function getPattern_id(){
		return $this->pattern_id;
	}

	public function setPattern_id($pattern_id){
		$this->pattern_id = $pattern_id;
	}
	
	public function getShow_as_server(){
		return $this->show_as_server;
	}

	public function setShow_as_server($show_as_server){
		$this->show_as_server = $show_as_server;
	}
	
	public function getOrganization(){
		return $this->organization;
	}

	public function setOrganization($organization){
		$this->organization = $organization;
	}

}
