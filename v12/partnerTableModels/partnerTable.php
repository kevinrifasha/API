<?php

class PartnerTable
{
	private $id;
	private $idpartner;
	private $idmeja;
	private $is_queue;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'idpartner' => $this->idpartner,
			'idmeja' => $this->idmeja,
			'is_queue' => $this->is_queue,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}
	
    public function getIdpartner(){
		return $this->idpartner;
	}

	public function setIdpartner($idpartner){
		$this->idpartner = $idpartner;
    }

    public function getIdmeja(){
		return $this->idmeja;
	}

	public function setIdmeja($idmeja){
		$this->idmeja = $idmeja;
    }

    public function getIs_queue(){
		return $this->is_queue;
	}

	public function setIs_queue($is_queue){
		$this->is_queue = $is_queue;
    }
    


}
