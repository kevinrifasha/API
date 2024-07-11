<?php

class Metric
{
	private $id;
	private $name;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
            'name' => $this->name,
        ];
    }
    
    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
    }
    
    public function getName(){
        return $this->name;
    }

    public function setName($name){
        $this->name = $name;
    }
	
}
