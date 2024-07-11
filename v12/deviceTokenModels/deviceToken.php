<?php

class DeviceToken
{
	private $id;
	private $id_partner;
	private $tokens;
	private $created_at;
	private $updated_at;
	private $deleted_at;
	private $deleted;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'id_partner' => $this->id_partner,
			'tokens' => $this->tokens,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
			'deleted_at' => $this->deleted_at,
			'deleted' => $this->deleted,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_partner(){
		return $this->id_partner;
	}

	public function setId_partner($id_partner){
		$this->id_partner = $id_partner;
	}

	public function getTokens(){
		return $this->tokens;
	}

	public function setTokens($tokens){
        $this->tokens = $tokens;
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

	public function getDeleted(){
		return $this->deleted;
	}

	public function setDeleted($deleted){
		$this->deleted = $deleted;
	}

}
