<?php

class User
{
	private $id;
	private $username;
	private $password;
	private $email;
	private $name;
	private $phone;
	private $TglLahir;
	private $Gender;
	private $dev_token;
	private $alamat;
	private $created_at;

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
    }

	public function getDetails()
	{
		return [
            'id'=>$this->id,
            'username'=>$this->username,
            'password'=>$this->password,
            'email'=>$this->email,
            'name'=>$this->name,
            'phone'=>$this->phone,
            'TglLahir'=>$this->TglLahir,
            'Gender'=>$this->Gender,
            'dev_token'=>$this->dev_token,
            'alamat'=>$this->alamat,
            'created_at'=>$this->created_at,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getUsername(){
		return $this->username;
	}

	public function setUsername($username){
		$this->username = $username;
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

	public function getTglLahir(){
		return $this->TglLahir;
	}

	public function setTglLahir($TglLahir){
		$this->TglLahir = $TglLahir;
	}

	public function getGender(){
		return $this->Gender;
	}

	public function setGender($Gender){
		$this->Gender = $Gender;
	}

	public function getDev_token(){
		return $this->dev_token;
	}

	public function setDev_token($dev_token){
		$this->dev_token = $dev_token;
	}

	public function getAlamat(){
		return $this->alamat;
	}

	public function setAlamat($alamat){
		$this->alamat = $alamat;
	}

	public function getCreated_at(){
		return $this->created_at;
	}

	public function setCreated_at($created_at){
		$this->created_at = $created_at;
	}

}
