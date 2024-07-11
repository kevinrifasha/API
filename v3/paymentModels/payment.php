<?php

class Payment
{
	private $id;
	private $nama;
    

	public function __construct($items)
	{
		foreach ($items as $nama => $val)
			$this->$nama = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
            'nama' => $this->nama,
        ];
    }
    
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
    }
    
    public function getNama(){
        return $this->nama;
    }

    public function setNama($nama){
        $this->nama = $nama;
    }
	
}
