<?php

class Partner
{
	private $id;
	private $name;
	private $address;
	private $phone;
	private $status;
	// private $saldo_ewallet;
	private $tax;
	private $service;
	// private $email;
	// private $password;
	private $restaurant_number;
	// private $delivery_fee;
	private $id_ovo;
	private $id_dana;
	private $id_linkaja;
	private $id_master;
	private $is_foodcourt;
	private $max_people_reservation;
	// private $device_token;
	private $longitude;
	private $latitude;
	private $img_map;
	private $logo;
	private $desc_map;
	private $is_delivery;
	private $is_takeaway;
	private $is_open;
	private $jam_buka;
	private $jam_tutup;
	private $wifi_ssid;
	private $wifi_password;
	// private $ip_checker;
	// private $ip_receipt;
	private $is_booked;
	private $booked_before;
	private $created_at;
	private $hide_charge;
	private $ovo_active;
	private $dana_active;
	private $linkaja_active;
	private $cc_active;
	private $debit_active;
	private $qris_active;
	private $shopeepay_active;
	private $partner_type;
	private $is_average_cogs;
	private $shipper_location;
	private $thumbnail;
	private $is_temporary_close;
	private $is_rounding;
	private $rounding_digits;
	private $rounding_down_below;
	private $charge_ur;
	private $charge_ur_shipper;
	private $is_centralized;
	private $fc_parent_id;
	private $deleted_at;
	private $is_ar;
	private $parent_id;
	private $subscription_until;
	private $trial_until;
	private $subscription_status;
	private $primary_subscription_id;
	private $is_reservation;
	private $is_temporary_qr;

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
			'address' => $this->address,
			'phone' => $this->phone,
			'status' => $this->status,
			// 'saldo_ewallet' => $this->saldo_ewallet,
			'tax' => $this->tax,
			'service' => $this->service,
			// 'email' => $this->email,
			'restaurant_number' => $this->restaurant_number,
			// 'delivery_fee' => $this->delivery_fee,
			'id_ovo' => $this->id_ovo,
			'id_dana' => $this->id_dana,
			'id_linkaja' => $this->id_linkaja,
			'id_master' => $this->id_master,
			'is_foodcourt' => $this->is_foodcourt,
			'max_people_reservation' => $this->max_people_reservation,
			'device_token' => $this->device_token,
			'longitude' => $this->longitude,
			'latitude' => $this->latitude,
			'img_map' => $this->img_map,
			'logo' => $this->logo,
			'desc_map' => $this->desc_map,
			'is_delivery' => $this->is_delivery,
			'is_takeaway' => $this->is_takeaway,
			'is_rounding' => $this->is_rounding,
			'rounding_down_below' => $this->rounding_down_below,
			'rounding_digits' => $this->rounding_digits,
			'is_open' => $this->is_open,
			'jam_buka' => $this->jam_buka,
			'jam_tutup' => $this->jam_tutup,
			'wifi_ssid' => $this->wifi_ssid,
			'wifi_password' => $this->wifi_password,
			'ip_receipt' => $this->ip_receipt,
			'is_booked' => $this->is_booked,
			'booked_before' => $this->booked_before,
			'created_at' => $this->created_at,
			'hide_charge' => $this->hide_charge,
			'ovo_active' => $this->ovo_active,
			'dana_active' => $this->dana_active,
			'linkaja_active' => $this->linkaja_active,
			'cc_active' => $this->cc_active,
			'debit_active' => $this->debit_active,
			'qris_active' => $this->qris_active,
			'shopeepay_active' => $this->shopeepay_active,
			'partner_type' => $this->partner_type,
			'is_average_cogs' => $this->is_average_cogs,
			'shipper_location' => $this->shipper_location,
			'thumbnail' => $this->thumbnail,
			'is_temporary_close' => $this->is_temporary_close,
			'is_temporary_qr' => $this->is_temporary_qr,
			'charge_ur' => $this->charge_ur,
			'charge_ur_shipper' => $this->charge_ur_shipper,
			'is_centralized' => $this->is_centralized,
			'fc_parent_id' => $this->fc_parent_id,
			'deleted_at' => $this->deleted_at,
			'is_ar' => $this->is_ar,
			'parent_id' => $this->parent_id,
			'subscription_status' => $this->subscription_status,
			'subscription_until' => $this->subscription_until,
			'trial_until' => $this->trial_until,
			'primary_subscription_id' => $this->primary_subscription_id,
			'is_reservation' => $this->is_reservation,
			'is_special_reservation' => $this->is_special_reservation
		];
	}

	public function getFullDetails()
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'address' => $this->address,
			'phone' => $this->phone,
			'status' => $this->status,
			// 'saldo_ewallet' => $this->saldo_ewallet,
			'tax' => $this->tax,
			'service' => $this->service,
			// 'email' => $this->email,
			// 'password' => $this->password,
			'restaurant_number' => $this->restaurant_number,
			// 'delivery_fee' => $this->delivery_fee,
			'id_ovo' => $this->id_ovo,
			'id_dana' => $this->id_dana,
			'id_linkaja' => $this->id_linkaja,
			'id_master' => $this->id_master,
			'is_foodcourt' => $this->is_foodcourt,
			'max_people_reservation' => $this->max_people_reservation,
			'device_token' => $this->device_token,
			'longitude' => $this->longitude,
			'latitude' => $this->latitude,
			'img_map' => $this->img_map,
			'logo' => $this->logo,
			'desc_map' => $this->desc_map,
			'is_delivery' => $this->is_delivery,
			'is_takeaway' => $this->is_takeaway,
			'is_open' => $this->is_open,
			'jam_buka' => $this->jam_buka,
			'jam_tutup' => $this->jam_tutup,
			'wifi_ssid' => $this->wifi_ssid,
			'wifi_password' => $this->wifi_password,
			'ip_checker' => $this->ip_checker,
			'ip_receipt' => $this->ip_receipt,
			'is_booked' => $this->is_booked,
			'booked_before' => $this->booked_before,
			'created_at' => $this->created_at,
			'hide_charge' => $this->hide_charge,
			'ovo_active' => $this->ovo_active,
			'dana_active' => $this->dana_active,
			'linkaja_active' => $this->linkaja_active,
			'cc_active' => $this->cc_active,
			'debit_active' => $this->debit_active,
			'qris_active' => $this->qris_active,
			'is_temporary_qr' => $this->is_temporary_qr,
			'shopeepay_active' => $this->shopeepay_active,
			'is_average_cogs' => $this->is_average_cogs,
			'shipper_location' => $this->shipper_location,
			'thumbnail' => $this->thumbnail,
			'is_temporary_close' => $this->is_temporary_close,
			'charge_ur' => $this->charge_ur,
			'charge_ur_shipper' => $this->charge_ur_shipper,
			'is_centralized' => $this->is_centralized,
			'fc_parent_id' => $this->fc_parent_id,
			'deleted_at' => $this->deleted_at,
			'is_ar' => $this->is_ar,
			'parent_id' => $this->parent_id,
			'is_rounding' => $this->is_rounding,
			'rounding_down_below' => $this->rounding_down_below,
			'rounding_digits' => $this->rounding_digits
		];
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getAddress()
	{
		return $this->address;
	}

	public function setAddress($address)
	{
		$this->address = $address;
	}

	public function getPhone()
	{
		return $this->phone;
	}

	public function setPhone($phone)
	{
		$this->phone = $phone;
	}

	public function getStatus()
	{
		return $this->status;
	}

	public function setStatus($status)
	{
		$this->status = $status;
	}

	// public function getSaldo_ewallet(){
	// 	return $this->saldo_ewallet;
	// }

	// public function setSaldo_ewallet($saldo_ewallet){
	// 	$this->saldo_ewallet = $saldo_ewallet;
	// }

	public function getTax()
	{
		return $this->tax;
	}

	public function setTax($tax)
	{
		$this->tax = $tax;
	}

	public function getService()
	{
		return $this->service;
	}

	public function setService($service)
	{
		$this->service = $service;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email)
	{
		$this->email = $email;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function getRestaurant_number()
	{
		return $this->restaurant_number;
	}

	public function setRestaurant_number($restaurant_number)
	{
		$this->restaurant_number = $restaurant_number;
	}

	// public function getDelivery_fee(){
	// 	return $this->delivery_fee;
	// }

	// public function setDelivery_fee($delivery_fee){
	// 	$this->delivery_fee = $delivery_fee;
	// }

	public function getId_ovo()
	{
		return $this->id_ovo;
	}

	public function setId_ovo($id_ovo)
	{
		$this->id_ovo = $id_ovo;
	}

	public function getId_dana()
	{
		return $this->id_dana;
	}

	public function setId_dana($id_dana)
	{
		$this->id_dana = $id_dana;
	}

	public function getId_linkaja()
	{
		return $this->id_linkaja;
	}

	public function setId_linkaja($id_linkaja)
	{
		$this->id_linkaja = $id_linkaja;
	}

	public function getId_master()
	{
		return $this->id_master;
	}

	public function setId_master($id_master)
	{
		$this->id_master = $id_master;
	}

	public function getIs_foodcourt()
	{
		return $this->is_foodcourt;
	}

	public function setIs_foodcourt($is_foodcourt)
	{
		$this->is_foodcourt = $is_foodcourt;
	}
    
    public function getIs_rounding()
	{
		return $this->is_rounding;
	}
	
    public function setIs_rounding($is_rounding)
	{
		$this->is_rounding = $is_rounding;
	}
	
    public function getRounding_digits()
	{
		return $this->rounding_digits;
	}
	
    public function setRounding_digits($rounding_digits)
	{
		$this->rounding_digits = $rounding_digits;
	}
	
    public function getRounding_down_below()
	{
		return $this->rounding_down_below;
	}
	
    public function setRounding_down_below($rounding_down_below)
	{
		$this->rounding_down_below = $rounding_down_below;
	}

	public function getMax_people_reservation()
	{
		return $this->max_people_reservation;
	}

	public function setMax_people_reservation($max_people_reservation)
	{
		$this->max_people_reservation = $max_people_reservation;
	}

	public function getDevice_token()
	{
		return $this->device_token;
	}

	public function setDevice_token($device_token)
	{
		$this->device_token = $device_token;
	}

	public function getLongitude()
	{
		return $this->longitude;
	}

	public function setLongitude($longitude)
	{
		$this->longitude = $longitude;
	}

	public function getLatitude()
	{
		return $this->latitude;
	}

	public function setLatitude($latitude)
	{
		$this->latitude = $latitude;
	}

	public function getImg_map()
	{
		return $this->img_map;
	}

	public function setImg_map($img_map)
	{
		$this->img_map = $img_map;
	}

	public function getDesc_map()
	{
		return $this->desc_map;
	}

	public function setDesc_map($desc_map)
	{
		$this->desc_map = $desc_map;
	}

	public function getIs_delivery()
	{
		return $this->is_delivery;
	}

	public function setIs_delivery($is_delivery)
	{
		$this->is_delivery = $is_delivery;
	}

	public function getIs_takeaway()
	{
		return $this->is_takeaway;
	}

	public function setIs_takeaway($is_takeaway)
	{
		$this->is_takeaway = $is_takeaway;
	}

	public function getIs_open()
	{
		return $this->is_open;
	}

	public function setIs_open($is_open)
	{
		$this->is_open = $is_open;
	}

	public function getJam_buka()
	{
		return $this->jam_buka;
	}

	public function setJam_buka($jam_buka)
	{
		$this->jam_buka = $jam_buka;
	}

	public function getJam_tutup()
	{
		return $this->jam_tutup;
	}

	public function setJam_tutup($jam_tutup)
	{
		$this->jam_tutup = $jam_tutup;
	}

	public function getWifi_ssid()
	{
		return $this->wifi_ssid;
	}

	public function setWifi_ssid($wifi_ssid)
	{
		$this->wifi_ssid = $wifi_ssid;
	}

	public function getWifi_password()
	{
		return $this->wifi_password;
	}

	public function setWifi_password($wifi_password)
	{
		$this->wifi_password = $wifi_password;
	}

	public function getIp_checker()
	{
		return $this->ip_checker;
	}

	public function setIp_checker($ip_checker)
	{
		$this->ip_checker = $ip_checker;
	}

	public function getIp_receipt()
	{
		return $this->ip_receipt;
	}

	public function setIp_receipt($ip_receipt)
	{
		$this->ip_receipt = $ip_receipt;
	}

	public function getIs_booked()
	{
		return $this->is_booked;
	}

	public function setIs_booked($is_booked)
	{
		$this->is_booked = $is_booked;
	}

	public function getBooked_before()
	{
		return $this->booked_before;
	}

	public function setBooked_before($booked_before)
	{
		$this->booked_before = $booked_before;
	}

	public function getCreated_at()
	{
		return $this->created_at;
	}

	public function setCreated_at($created_at)
	{
		$this->created_at = $created_at;
	}

	public function getHide_charge()
	{
		return $this->hide_charge;
	}

	public function setHide_charge($hide_charge)
	{
		$this->hide_charge = $hide_charge;
	}

	public function getOvo_active()
	{
		return $this->ovo_active;
	}

	public function setOvo_active($ovo_active)
	{
		$this->ovo_active = $ovo_active;
	}

	public function getDana_active()
	{
		return $this->dana_active;
	}

	public function setDana_active($dana_active)
	{
		$this->dana_active = $dana_active;
	}

	public function getLinkaja_active()
	{
		return $this->linkaja_active;
	}

	public function setLinkaja_active($linkaja_active)
	{
		$this->linkaja_active = $linkaja_active;
	}

	public function getCc_active()
	{
		return $this->cc_active;
	}

	public function setCc_active($cc_active)
	{
		$this->cc_active = $cc_active;
	}

	public function getDebit_active()
	{
		return $this->debit_active;
	}

	public function setDebit_active($debit_active)
	{
		$this->debit_active = $debit_active;
	}

	public function getQris_active()
	{
		return $this->qris_active;
	}

	public function setQris_active($qris_active)
	{
		$this->qris_active = $qris_active;
	}

	public function getShopeepay_active()
	{
		return $this->shopeepay_active;
	}

	public function setShopeepay_active($shopeepay_active)
	{
		$this->shopeepay_active = $shopeepay_active;
	}

	public function getPartner_type()
	{
		return $this->partner_type;
	}

	public function setPartner_type($partner_type)
	{
		$this->partner_type = $partner_type;
	}

	public function getIs_average_cogs()
	{
		return $this->is_average_cogs;
	}

	public function setIs_average_cogs($is_average_cogs)
	{
		$this->is_average_cogs = $is_average_cogs;
	}

	public function getShipper_location()
	{
		return $this->shipper_location;
	}

	public function setShipper_location($shipper_location)
	{
		$this->shipper_location = $shipper_location;
	}

	public function getThumbnail()
	{
		return $this->thumbnail;
	}

	public function setThumbnail($thumbnail)
	{
		$this->thumbnail = $thumbnail;
	}

	public function getIs_temporary_close()
	{
		return $this->is_temporary_close;
	}

	public function setIs_temporary_close($is_temporary_close)
	{
		$this->is_temporary_close = $is_temporary_close;
	}

	public function getIs_temporary_qr()
	{
		return $this->is_temporary_qr;
	}

	public function setIs_temporary_qr($is_temporary_qr)
	{
		$this->is_temporary_qr = $is_temporary_qr;
	}

	public function setLogo($logo)
	{
		$this->logo = $logo;
	}

	public function getLogo()
	{
		return $this->logo;
	}

	public function getCharge_ur()
	{
		return $this->charge_ur;
	}

	public function setCharge_ur($charge_ur)
	{
		$this->charge_ur = $charge_ur;
	}

	public function getCharge_ur_shipper()
	{
		return $this->charge_ur_shipper;
	}

	public function setCharge_ur_shipper($charge_ur_shipper)
	{
		$this->charge_ur_shipper = $charge_ur_shipper;
	}
	public function getIsCentralized()
	{
		return $this->is_centralized;
	}
	public function setIsCentralized($is_centralized)
	{
		$this->is_centralized = $is_centralized;
	}
	public function getParentID()
	{
		return $this->fc_parent_id;
	}
	public function setParentID($fc_parent_id)
	{
		$this->fc_parent_id = $fc_parent_id;
	}

	public function setDeleted_at($deleted_at)
	{
		$this->deleted_at = $deleted_at;
	}

	public function getDeleted_at()
	{
		return $this->deleted_at;
	}
	
	public function getParent_id()
	{
		return $this->parent_id;
	}
	public function setParent_id($parent_id)
	{
		$this->parent_id = $parent_id;
	}

	public function getTrial_until()
	{
		return $this->trial_until;
	}
	public function setTrial_until($trial_until)
	{
		$this->trial_until = $trial_until;
	}
	
	public function getSubscription_status()
	{
		return $this->subscription_status;
	}
	public function setSubscription_status($subscription_status)
	{
		$this->subscription_status = $subscription_status;
	}

	public function getPrimary_subscription_id()
	{
		return $this->primary_subscription_id;
	}
	public function setPrimary_subscription_id($primary_subscription_id)
	{
		$this->primary_subscription_id = $primary_subscription_id;
	}

	public function getSubscription_until()
	{
		return $this->subscription_until;
	}
	public function setSubscription_until($subscription_until)
	{
		$this->subscription_until = $subscription_until;
	}
}
