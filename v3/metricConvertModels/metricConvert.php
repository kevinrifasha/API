<?php

class MetricConvert
{
	private $id;
	private $id_metric1;
	private $id_metric2;
	private $value;
    

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'id_metric1' => $this->id_metric1,
			'id_metric2' => $this->id_metric2,
			'value' => $this->value,
        ];
    }

    public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}

	public function getId_metric1(){
		return $this->id_metric1;
	}

	public function setId_metric1($id_metric1){
		$this->id_metric1 = $id_metric1;
	}

	public function getId_metric2(){
		return $this->id_metric2;
	}

	public function setId_metric2($id_metric2){
		$this->id_metric2 = $id_metric2;
	}

	public function getValue(){
		return $this->value;
	}

	public function setValue($value){
		$this->value = $value;
	}
	
}
