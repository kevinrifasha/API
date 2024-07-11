<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
require '../db_connection.php';
$source = '[
  {
    "id": 5715,
    "nama": "Lomie Ayam Baso Pangsit",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5717,
    "nama": "Lomie Ayam Polos",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5718,
    "nama": "Lomie Ayam Baso",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5719,
    "nama": "Lomie Ayam Pangsit",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5720,
    "nama": "Yamien Asin/Manis Polos",
    "dinein": 0,
    "gofood": 8000,
    "grab": 8000,
    "shopee": 8000
  },
  {
    "id": 5721,
    "nama": "Yamien Asin/Manis Baso",
    "dinein": 0,
    "gofood": 10000,
    "grab": 10000,
    "shopee": 10000
  },
  {
    "id": 5722,
    "nama": "Yamien Asin/Manis Pangsit",
    "dinein": 0,
    "gofood": 10000,
    "grab": 10000,
    "shopee": 10000
  },
  {
    "id": 5723,
    "nama": "Yamien Asin/Manis Baso Pangsit",
    "dinein": 0,
    "gofood": 12000,
    "grab": 12000,
    "shopee": 12000
  },
  {
    "id": 5724,
    "nama": "Mie Rica Asin/Manis Polos",
    "dinein": 0,
    "gofood": 9000,
    "grab": 9000,
    "shopee": 9000
  },
  {
    "id": 5725,
    "nama": "Mie Rica Asin/Manis Baso",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5726,
    "nama": "Mie Rica Asin/Manis Pangsit",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5727,
    "nama": "Mie Rica Asin/Manis Baso Pangsit",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5728,
    "nama": "Misoa Kuah Polos",
    "dinein": 0,
    "gofood": 9000,
    "grab": 9000,
    "shopee": 9000
  },
  {
    "id": 5729,
    "nama": "Misoa Kuah Baso",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5730,
    "nama": "Misoa Kuah Pangsit",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5731,
    "nama": "Misoa Kuah Baso Pangsit",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5732,
    "nama": "Nasi Capcay",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5733,
    "nama": "Nasi Sapo Tahu",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5734,
    "nama": "Nasi Ayam Cah Jamur",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5735,
    "nama": "Nasi Sapi Lada Hitam",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5736,
    "nama": "Nasi Bistik Sapi",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5737,
    "nama": "Nasi Ayam Kungpao",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5738,
    "nama": "Nasi Bistik Ayam",
    "dinein": 0,
    "gofood": 10000,
    "grab": 10000,
    "shopee": 10000
  },
  {
    "id": 5739,
    "nama": "Bistik Saja",
    "dinein": 0,
    "gofood": 13500,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5740,
    "nama": "Nasi Tim",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5741,
    "nama": "Nasi Ayam Telur Asin",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5742,
    "nama": "Nasi Udang Telur Asin",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5743,
    "nama": "Nasi Ayam Mayo",
    "dinein": 0,
    "gofood": 12000,
    "grab": 12000,
    "shopee": 12000
  },
  {
    "id": 5744,
    "nama": "Mie Goreng",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5745,
    "nama": "Mie Goreng",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5746,
    "nama": "Mie Goreng Seafood",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5747,
    "nama": "Mie Goreng Seafood",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5748,
    "nama": "Mie Masak",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5749,
    "nama": "Misoa Masak",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5750,
    "nama": "Baso pangsit kuah /10pcs",
    "dinein": 0,
    "gofood": 12000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5751,
    "nama": "Sup juan lo",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5752,
    "nama": "Nasi goreng belacan",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5753,
    "nama": "Nasi goreng seafood",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5754,
    "nama": "Nasi goreng special",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5755,
    "nama": "Nasi goreng",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5756,
    "nama": "Kwetiaw siram seafood",
    "dinein": 0,
    "gofood": 16000,
    "grab": 16000,
    "shopee": 16000
  },
  {
    "id": 5757,
    "nama": "Kwetiaw goreng singapore",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5758,
    "nama": "Kwetiaw siram sapi",
    "dinein": 0,
    "gofood": 16000,
    "grab": 16000,
    "shopee": 16000
  },
  {
    "id": 5759,
    "nama": "Kwetiaw goreng sapi",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5760,
    "nama": "Kwetiaw goreng seafood",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5761,
    "nama": "Kwetiaw goreng belacan",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5762,
    "nama": "Mie kepiting",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5763,
    "nama": "Mie casau ayam madu",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5764,
    "nama": "Kwetiaw goreng",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5765,
    "nama": "Kwetiaw siram",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5766,
    "nama": "Ifumie",
    "dinein": 0,
    "gofood": 15000,
    "grab": 15000,
    "shopee": 15000
  },
  {
    "id": 5767,
    "nama": "Baso campur cuanki spesial",
    "dinein": 0,
    "gofood": 12000,
    "grab": 12000,
    "shopee": 8000
  },
  {
    "id": 5768,
    "nama": "Baso goreng /pcs",
    "dinein": 0,
    "gofood": 1500,
    "grab": 1500,
    "shopee": 1500
  },
  {
    "id": 5769,
    "nama": "Baso tahu",
    "dinein": 0,
    "gofood": 1500,
    "grab": 1500,
    "shopee": 1500
  },
  {
    "id": 5770,
    "nama": "Siomai goreng",
    "dinein": 0,
    "gofood": 2500,
    "grab": 2500,
    "shopee": 2500
  },
  {
    "id": 5771,
    "nama": "Siomai kukus",
    "dinein": 0,
    "gofood": 1500,
    "grab": 1500,
    "shopee": 1500
  },
  {
    "id": 5772,
    "nama": "Batagor tengiri",
    "dinein": 0,
    "gofood": 2500,
    "grab": 2500,
    "shopee": 2500
  },
  {
    "id": 5773,
    "nama": "Kekian",
    "dinein": 0,
    "gofood": 5000,
    "grab": 5000,
    "shopee": 5000
  },
  {
    "id": 5774,
    "nama": "Es mangga serut",
    "dinein": 0,
    "gofood": 7500,
    "grab": 7500,
    "shopee": 7500
  },
  {
    "id": 5775,
    "nama": "Kopi ice tiramisu latte",
    "dinein": 0,
    "gofood": 26000,
    "grab": 25000,
    "shopee": 25000
  },
  {
    "id": 5776,
    "nama": "Kopi ice durian latte ice",
    "dinein": 0,
    "gofood": 29000,
    "grab": 29000,
    "shopee": 29000
  },
  {
    "id": 5777,
    "nama": "Kopi O",
    "dinein": 0,
    "gofood": 4000,
    "grab": 4000,
    "shopee": 4000
  },
  {
    "id": 5778,
    "nama": "Kopi O",
    "dinein": 0,
    "gofood": 5000,
    "grab": 5000,
    "shopee": 4000
  },
  {
    "id": 5779,
    "nama": "Kopi susu (kopi tiam)",
    "dinein": 0,
    "gofood": 5000,
    "grab": 5000,
    "shopee": 3000
  },
  {
    "id": 5780,
    "nama": "Kopi susu (kopi tiam)",
    "dinein": 0,
    "gofood": 6000,
    "grab": 6000,
    "shopee": 5000
  },
  {
    "id": 5781,
    "nama": "Jus strawberry",
    "dinein": 0,
    "gofood": 7500,
    "grab": 7500,
    "shopee": 7500
  },
  {
    "id": 5782,
    "nama": "Es jeruk",
    "dinein": 0,
    "gofood": 7500,
    "grab": 7500,
    "shopee": 7500
  },
  {
    "id": 5783,
    "nama": "Kopi gula aren (koguren)",
    "dinein": 0,
    "gofood": 6000,
    "grab": 6000,
    "shopee": 6000
  },
  {
    "id": 5784,
    "nama": "Peuyeum goreng /pcs",
    "dinein": 0,
    "gofood": 1500,
    "grab": 1500,
    "shopee": 1500
  },
  {
    "id": 5785,
    "nama": "Wonton",
    "dinein": 0,
    "gofood": 6000,
    "grab": 7500,
    "shopee": 7500
  },
  {
    "id": 5786,
    "nama": "Pangsit goreng /pcs",
    "dinein": 0,
    "gofood": 1000,
    "grab": 1000,
    "shopee": 1000
  },
  {
    "id": 5787,
    "nama": "Ote ote tiram",
    "dinein": 0,
    "gofood": 4000,
    "grab": 4000,
    "shopee": 4000
  },
  {
    "id": 5788,
    "nama": "Pisang goreng godo godo /pcs",
    "dinein": 0,
    "gofood": 1500,
    "grab": 1500,
    "shopee": 1000
  },
  {
    "id": 5789,
    "nama": "Dimsum kaki ayam",
    "dinein": 0,
    "gofood": 11000,
    "grab": 12000,
    "shopee": 12000
  },
  {
    "id": 5790,
    "nama": "Dimsum hakau",
    "dinein": 0,
    "gofood": 9000,
    "grab": 9000,
    "shopee": 9000
  },
  {
    "id": 5791,
    "nama": "Dimsum lumpiah kulit tahu",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5792,
    "nama": "Dimsum siomai",
    "dinein": 0,
    "gofood": 10000,
    "grab": 10000,
    "shopee": 10000
  },
  {
    "id": 5793,
    "nama": "Kuotie",
    "dinein": 0,
    "gofood": 8500,
    "grab": 8500,
    "shopee": 6000
  },
  {
    "id": 5794,
    "nama": "Lomisoa baso pangsit",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5795,
    "nama": "Jus mangga",
    "dinein": 0,
    "gofood": 5500,
    "grab": 7500,
    "shopee": 7500
  },
  {
    "id": 5796,
    "nama": "Bapao pasir mas isi3",
    "dinein": 0,
    "gofood": 4000,
    "grab": 4000,
    "shopee": 4000
  },
  {
    "id": 5797,
    "nama": "Bapao dimsum ayam isi3",
    "dinein": 0,
    "gofood": 6000,
    "grab": 6000,
    "shopee": 6000
  },
  {
    "id": 5803,
    "nama": "teh manis",
    "dinein": 0,
    "gofood": 3000,
    "grab": 3000,
    "shopee": 3000
  },
  {
    "id": 5804,
    "nama": "teh manis",
    "dinein": 0,
    "gofood": 3000,
    "grab": 3000,
    "shopee": 3000
  },
  {
    "id": 5809,
    "nama": "ice caramel latte",
    "dinein": 0,
    "gofood": 6000,
    "grab": 6000,
    "shopee": 6000
  },
  {
    "id": 5819,
    "nama": "nasi goreng nanas",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5820,
    "nama": "kwetiaw goreng vegetarian",
    "dinein": 0,
    "gofood": 9000,
    "grab": 9000,
    "shopee": 9000
  },
  {
    "id": 5821,
    "nama": "kwetiaw siram vegetarian",
    "dinein": 0,
    "gofood": 9000,
    "grab": 9000,
    "shopee": 9000
  },
  {
    "id": 5824,
    "nama": "nasi udang mayo",
    "dinein": 0,
    "gofood": 13000,
    "grab": 13000,
    "shopee": 13000
  },
  {
    "id": 5825,
    "nama": "mie goreng belacan",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5826,
    "nama": "mie goreng belacan",
    "dinein": 0,
    "gofood": 14000,
    "grab": 14000,
    "shopee": 14000
  },
  {
    "id": 5829,
    "nama": "misoa masak seafood",
    "dinein": 0,
    "gofood": 11000,
    "grab": 11000,
    "shopee": 11000
  },
  {
    "id": 5830,
    "nama": "Krupuk tahu",
    "dinein": 0,
    "gofood": 7500,
    "grab": 5000,
    "shopee": 5000
  },
  {
    "id": 5831,
    "nama": "Krupuk gendar 6000",
    "dinein": 0,
    "gofood": 3000,
    "grab": 2000,
    "shopee": 2000
  },
  {
    "id": 5841,
    "nama": "Jus black. Berry",
    "dinein": 0,
    "gofood": 5500,
    "grab": 7500,
    "shopee": 7500
  },
  {
    "id": 5851,
    "nama": "Jus jambu",
    "dinein": 0,
    "gofood": 7500,
    "grab": 7500,
    "shopee": 7500
  }
]';
$data = json_decode($source);
foreach($data as $x){
  $menuID = $x->id;
  $menuName = $x->nama;
  $dinein = $x->dinein;
  $gofood = $x->gofood;
  $grab = $x->grab;
  $shopee = $x->shopee;
  // $insertVG = mysqli_query($db_conn, "INSERT INTO variant_group SET id_master='514', name='$menuName', type=1");
  // if($insertVG){
  //   $vgID = mysqli_insert_id($db_conn);
  //   $insertV = mysqli_query($db_conn, "INSERT INTO variant (id_variant_group,name,price,stock,is_recipe) VALUES ('$vgID', 'Dine In', 0, 99999, 0), ('$vgID', 'Gofood', '$gofood', 99999, 0), ('$vgID', 'Grab', '$grab', 99999, 0), ('$vgID', 'Shopee', '$shopee', 99999, 0)");
  //   $inserMVG = mysqli_query($db_conn, "INSERT INTO menus_variantgroups SET menu_id='$menuID', variant_group_id='$vgID'");
  //   if($inserMVG){
  //     echo " \n Berhasil buat varian ".$menuName;
  //   }
  // }
  $updateMenu = mysqli_query($db_conn, "UPDATE menu SET is_variant=1 WHERE id='$menuID'");
  if($updateMenu){
    echo " \n Berhasil update varian ".$menuName;
  }
}
?>