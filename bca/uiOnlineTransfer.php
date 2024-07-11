<!doctype html>
<html lang="en">
  <head>
    <script>
        var nama = prompt("Masukkan Secret Code", "");
        if(nama != "hi7wvvJ7pkYMITs0XzBg"){
            location.reload(true)
        }
    </script>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">

    <title>Online Transfer</title>
  </head>
  <body>

    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ho+j7jyWK8fNQe+A12Hb8AhRq26LrZ/JpcUGGOn+Y7RsweNrtN/tE3MoK7ZeZDyx" crossorigin="anonymous"></script>

    <div class="container">
    <div class="row justify-content-md-center">
        <div class="col-sm-auto">
        <h1>Online Transfer</h1>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            <div class="form-group">
                <label for="SourceAccountNumber">Nomor Rekening Pengirim</label>
                <input type="Text" class="form-control" id="SourceAccountNumber" name="SourceAccountNumber" placeholder="0123456789" Required>
            </div>
            <div class="form-group">
                <label for="BeneficiaryAccountNumber">Nomor Rekening Penerima</label>
                <input type="Text" class="form-control" id="BeneficiaryAccountNumber" name="BeneficiaryAccountNumber" placeholder="0123456789">
            </div>
            <div class="form-group">
                <label for="Amount">Nominal</label>
                <input type="Number" class="form-control" id="Amount" name="Amount" placeholder="100000">
            </div>
            <div class="form-group">
                <label for="Remark1">Berita</label>
                <input type="Text" class="form-control" id="Remark1" name="Remark1" placeholder="Maksimal 18 Karakter">
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
    </div>

    <?php
    // Checking for a POST request
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $BeneficiaryAccountNumber = test_input($_POST["BeneficiaryAccountNumber"]);
            $Amount = test_input($_POST["Amount"]);
            $SourceAccountNumber = test_input($_POST["SourceAccountNumber"]);
            $Remark1 = test_input($_POST["Remark1"]);
            if(strlen($Remark1)>18){
                $Remark1 = substr($Remark1,0,18);
            }
        }

      // Removing the redundant HTML characters if any exist.
        function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

    date_default_timezone_set('Asia/Jakarta');
    //Get time and ISO8601 formar
    $timestamp = new DateTime();
    // $time =  $timestamp->format(DateTime::ISO8601);
    $time = $timestamp->format('Y-m-d\TG:i:s.vP');
    $timeNow = $timestamp->format('Y-m-d');
    // $tranId = "00000001";
    $tranId = mt_rand(10000000,99999999);
    $referenceID = mt_rand(10000000,99999999);
    //get toket 0Auth2.0
    require_once 'auth.php';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        //getting values
        if (isset($SourceAccountNumber)
        && isset($Amount)
        && isset($BeneficiaryAccountNumber)
        && isset($Remark1)
        && !empty($SourceAccountNumber)
        && !empty($Amount)
        && !empty($BeneficiaryAccountNumber)
        && !empty($Remark1)) {

            $Remark1HMAC = $string = str_replace(' ', '', $Remark1);
            $auth = new auth();
            $token = $auth->getAuth();
            $token = json_decode(json_encode(json_decode($token)));
            // echo gettype($token);
            foreach ($token as $key => $object) {
                if ($key=="access_token") {
                    $access_token = $object;
                }
            }
            //req body
            $td['CorporateID'] = "KBBRAHMATT";
            $td['SourceAccountNumber'] = $SourceAccountNumber;
            $td['TransactionID'] = "$tranId";
            $td['TransactionDate'] = "$timeNow";
            $td['ReferenceID'] = "$referenceID";
            $td['CurrencyCode'] = "IDR";
            $td['Amount'] = $Amount;
            $td['BeneficiaryAccountNumber'] = $BeneficiaryAccountNumber;
            $td['Remark1'] = "$Remark1";
            $td['Remark2'] = "123456789";

            //req body
            $ts['CorporateID'] = "KBBRAHMATT";
            $ts['SourceAccountNumber'] = $SourceAccountNumber;
            $ts['TransactionID'] = "$tranId";
            $ts['TransactionDate'] = "$timeNow";
            $ts['ReferenceID'] = "$referenceID";
            $ts['CurrencyCode'] = "IDR";
            $ts['Amount'] = $Amount;
            $ts['BeneficiaryAccountNumber'] = $BeneficiaryAccountNumber;
            $ts['Remark1'] = "$Remark1HMAC";
            $ts['Remark2'] = "123456789";

            //merge set array diatas agar dapat di execute oleh API midtran
            $reqBody = json_encode($ts);
            $reqBody1 = json_encode($td);
            $reqBody = strtolower(hash('sha256', $reqBody));

            //set API Secret
            $apiScrt = "3e397b87-128c-473d-a83b-aecde6194925";

            //url api
            $url = 'https://api.klikbca.com:443/banking/corporates/transfers';
            $relativeUrl = '/banking/corporates/transfers';

            //http method
            $httpMethod = "POST";

            $stringToSign = $httpMethod.":".$relativeUrl.":".$access_token.":".$reqBody.":".$time;
            $sig = hash_hmac('sha256', $stringToSign, $apiScrt);

            //curl
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $reqBody1);


            $headers = array();
            $headers[] = 'Authorization: Bearer '. $access_token;
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Origin: ur-hub.com';
            $headers[] = 'X-Bca-Key: f8f02dfc-b7cb-4c54-b4b3-64a7d70b1042';
            $headers[] = 'X-Bca-Timestamp: '.$time;
            $headers[] = 'X-Bca-Signature: '.$sig;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }

            $result = json_decode(json_encode(json_decode($result)));
            foreach ($result as $key => $object) {
                if ($key=="Status") {
                    $status = $object;
                }
            }
            if($status=="Success"){
                echo "<script>alert('Berhasil Mengirimkan Dana')</script>";
            }else{
                echo "<script>alert('Gagal Mengirimkan Dana')</script>";
            }
            curl_close($ch);
        }
    }

    ?>
  </body>
</html>
