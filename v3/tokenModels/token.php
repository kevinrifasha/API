<?php

class Token
{
	private $token;
    private $type;
    private $expires_in;
    private $created_at;

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
    }

	public function getDetails()
	{
		return [
            'token'=>$this->token,
            'type'=>$this->type,
            'expires_in'=>$this->expires_in,
            'created_at'=>$this->created_at,
        ];
    }
    
    public function getToken(){
		return $this->token;
	}

	public function setToken($token){
		$this->token = $token;
	}

	public function getType(){
		return $this->type;
	}

	public function setType($type){
		$this->type = $type;
	}

	public function getExpires_in(){
		return $this->expires_in;
	}

    public function setExpires_in($expires_in){
		$this->expires_in = $expires_in;
	}

	public function getCreated_at(){
		return $this->created_at;
	}

	public function setCreated_at($created_at){
		$this->created_at = $created_at;
    }
    
}
