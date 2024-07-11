<?php

class Menu
{
	private $id;
	private $sku;
	private $id_partner;
	private $nama;
	private $harga;
	private $Deskripsi;
	private $category;
	private $id_category;
	private $img_data;
	private $enabled;
	private $stock;
	private $hpp;
	private $harga_diskon;
	private $is_variant;
	private $is_recommended;
	private $is_recipe;
	private $thumbnail;
	private $is_auto_cogs;
	private $show_in_sf;
	private $is_multiple_price;
	private $show_in_waiter;

	public function __construct($items)
	{
		foreach ($items as $name => $val)
			$this->$name = $val;
	}

	public function getDetails()
	{
		return [
			'id' => $this->id,
			'sku' => $this->sku,
			'id_partner' => $this->id_partner,
			'nama' => $this->nama,
			'harga' => $this->harga,
			'Deskripsi' => $this->Deskripsi,
			'category' => $this->category,
			'id_category' => $this->id_category,
			'img_data' => $this->img_data,
			'enabled' => $this->enabled,
			'stock' => $this->stock,
			'hpp' => $this->hpp,
			'harga_diskon' => $this->harga_diskon,
			'is_variant' => $this->is_variant,
			'is_recommended' => $this->is_recommended,
			'is_recipe' => $this->is_recipe,
			'thumbnail' => $this->thumbnail,
			'is_auto_cogs' => $this->is_auto_cogs,
			'show_in_sf' => $this->show_in_sf,
			'show_in_waiter' => $this->show_in_waiter,
			'is_multiple_price' => $this->is_multiple_price
        ];
	}
	
	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}
	public function getSKU(){
		return $this->sku;
	}

	public function setSKU($sku){
		$this->sku = $sku;
	}
	public function getId_partner(){
		return $this->id_partner;
	}

	public function setId_partner($id_partner){
		$this->id_partner = $id_partner;
	}

	public function getNama(){
		return $this->nama;
	}

	public function setNama($nama){
		$this->nama = $nama;
	}

	public function getHarga(){
		return $this->harga;
	}

	public function setHarga($harga){
		$this->harga = $harga;
	}

	public function getDeskripsi(){
		return $this->Deskripsi;
	}

	public function setDeskripsi($Deskripsi){
		$this->Deskripsi = $Deskripsi;
	}

	public function getCategory(){
		return $this->category;
	}

	public function setCategory($category){
		$this->category = $category;
	}

	public function getId_category(){
		return $this->id_category;
	}

	public function setId_category($id_category){
		$this->id_category = $id_category;
	}

	public function getImg_data(){
		return $this->img_data;
	}

	public function setImg_data($img_data){
		$this->img_data = $img_data;
	}

	public function getEnabled(){
		return $this->enabled;
	}

	public function setEnabled($enabled){
		$this->enabled = $enabled;
	}

	public function getStock(){
		return $this->stock;
	}

	public function setStock($stock){
		$this->stock = $stock;
	}

	public function getHpp(){
		return $this->hpp;
	}

	public function setHpp($hpp){
		$this->hpp = $hpp;
	}

	public function getHarga_diskon(){
		return $this->harga_diskon;
	}

	public function setHarga_diskon($harga_diskon){
		$this->harga_diskon = $harga_diskon;
	}

	public function getIs_variant(){
		return $this->is_variant;
	}

	public function setIs_variant($is_variant){
		$this->is_variant = $is_variant;
	}

	public function getIs_recommended(){
		return $this->is_recommended;
	}

	public function setIs_recommended($is_recommended){
		$this->is_recommended = $is_recommended;
	}

	public function getIs_recipe(){
		return $this->is_recipe;
	}

	public function setIs_recipe($is_recipe){
		$this->is_recipe = $is_recipe;
	}
	public function getThumbnail(){
		return $this->thumbnail;
	}

	public function setThumbnail($thumbnail){
		$this->thumbnail = $thumbnail;
	}
	
	public function getIs_auto_cogs(){
		return $this->is_auto_cogs;
	}

	public function setIs_auto_cogs($is_auto_cogs){
		$this->is_auto_cogs = $is_auto_cogs;
	}
	
	public function getShow_in_sf(){
		return $this->show_in_sf;
	}

	public function setShow_in_sf($show_in_sf){
		$this->show_in_sf = $show_in_sf;
	}
	
	public function getShow_in_waiter(){
		return $this->show_in_waiter;
	}

	public function setShow_in_waiter($show_in_waiter){
		$this->show_in_waiter = $show_in_waiter;
	}
	
	public function getIs_multiple_price(){
		return $this->is_multiple_price;
	}

	public function setIs_multiple_price($is_multiple_price){
		$this->is_multiple_price = $is_multiple_price;
	}
}
