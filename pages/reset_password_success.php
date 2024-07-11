<?php
    
    require_once dirname(__FILE__) . '/../includes/DbOperation.php';

    $db = new DbOperation();

    
    if(isset($_POST['phone']))
    {
        $phone = $_POST['phone'];
        $db->forgotPassword($phone);

        echo "<html>"
            ."<head>"
            ."    <title>Request forgot password succeded.</title>"
            ."</head>"
            ."<body>"
            ."    <h1>We've done for now.</h1>"
            ."    <p>Kami sudah mengirimkan surel yang berisikan instruksi pengembalian akun ke akun surel anda. Apabila nomor telepon yang anda masukkan tersimpan di dalam basis data kami. Silahkan cek akun surel anda untuk mengetahui langkah selanjutnya dalam mengembalikan akun anda.</p>"
            ."</body>"
            ."</html>";
}

?>