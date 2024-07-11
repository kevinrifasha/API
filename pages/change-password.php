<html lang="id">

<head>
    <title>Ganti PIN | UR - Easy & Quick Order</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.1/css/bootstrap.min.css" integrity="sha384-VCmXjywReHh4PwowAiWNagnWcLhlEJLA5buUprzK8rxFgeH0kww/aWY76TfkUoSX" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />

    <script>
        function togglePIN() {
            var x = document.getElementById("inputPIN");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }

        function togglePINConfirm() {
            var x = document.getElementById("inputPINConfirm");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</head>

<body>
    <div class="container pt-5">
        <div class="row justify-content-center mb-5">
            <div class="col-8 d-flex align-items-center justify-content-center">
                <img src="https://ur-hub.s3-us-west-2.amazonaws.com/assets/misc/logo-blue-boxless.png" alt="logo-ur" class="mr-2" width="35" />
                <h3 class="mb-0">Ganti PIN</h3>
            </div>
            <div class="col-10"></div>
        </div>
        <div class="row justify-content-center">
            <div class="col-8">
                <form action=<?php echo $_SERVER['PHP_SELF'] . "?id=" . $_GET['id']; ?> method="post">
                    <div class="form-group row align-items-center">
                        <div class="col-12">
                            <label for="inputPIN">PIN Baru</label>
                        </div>
                        <div class="col-12 col-md-9">
                            <input autofocus type="password" onkeypress="return isNumberKey(event)" id="inputPIN" name="pin" class="form-control" placeholder="PIN" required/>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check my-2">
                                <input class="form-check-input" type="checkbox" id="checkPIN" onclick="togglePIN()" />
                                <label class="form-check-label" for="checkPIN">
                                    Tampilkan
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Panjang PIN harus 4 Angka.</small>
                        </div`>
                    </div>
                    <div class="form-group row align-items-center">
                        <div class="col-12">
                            <label for="inputPINConfirm">Ketik Ulang PIN</label>
                        </div>
                        <div class="col-12 col-md-9">
                            <input type="password" id="inputPINConfirm" onkeypress="return isNumberKey(event)" name="confpass" class="form-control" placeholder="Ketik Ulang PIN" required />
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check my-2">
                                <input class="form-check-input" type="checkbox" id="checkPINConfirm" onclick="togglePINConfirm()" />
                                <label class="form-check-label" for="checkPINConfirm">
                                    Tampilkan
                                </label>
                            </div>
                        </div>
                    </div>
                    <button class="btn text-light" type="submit" name="login" style="background-color: #1FB0e6;">
                        Konfirmasi PIN Baru
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
<script>
function isNumberKey(evt)
{
	var charCode = (evt.which) ? evt.which : event.keyCode
	if (charCode > 31 && (charCode < 48 || charCode > 57))
	return false;

	return true;
}
</script>
<?php
require dirname(__FILE__) . "/../includes/DbOperation.php";

$db = new DbOperation();

if (isset($_POST['pin'])) {
    if (
        isset($_POST['pin']) &&
        isset($_POST['confpass'])
    ) {
        $phone = $_GET['id'];
        $pin = $_POST['pin'];
        $confpass = $_POST['confpass'];

        if (strlen($pin) == 4) {
            if(is_numeric($pin)){

                if ($pin == $confpass) {
                        $db->updatePINMD5($pin, $phone);
                        echo "PIN Updated Successfully";
                    } else {
                            // Error Handling
                            echo "An error occured.";
                        }
            }else{

                echo "PIN hanya Boleh terdiri dari Angka";
            }
        } else {
            echo "Panjang PIN harus 4 angka";
        }
    } else {
        echo "Required Parameter Missing";
    }
}
?>