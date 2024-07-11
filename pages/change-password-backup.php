<!-- pattern="(?=^.{4,8}$)(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s)[0-9a-zA-Z!@#$%^&*()]*$" -->
<!-- pattern="(?=^.{4,8}$)(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s)[0-9a-zA-Z!@#$%^&*()]*$" -->
<html>
    <head>
        <title>Change Your Password</title>
        <style>
        .card {
        margin-top: 25%;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        transition: 0.3s;
        width: 60%;
        height: 50%;
        padding-top:20%;
        /* margin: auto; */
        }

        .card:hover {
        box-shadow: 0 8px 16px 0 rgba(0,0,0,0.2);
        }

        .form-control{
            margin-top: 50px;
            width: 50%;
            padding: 12px 20px;
            box-sizing: border-box;
        }
        .btn{
            margin-top:50px;
            margin-bottom:50px;
            background-color: #1FB0E6; /* Green */
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
        }
        .muted{
            font-size: 12px;
            color: lightgray;
        }
        </style>
    </head>
    <body style="margin: 100" align="center">
    
            <!-- ID : <input type="text" name="phone" value="<?php echo $_GET['id']?>" disabled="true">
            <br>
            Password : <input type="password" name="password" placeholder="Password">
            <br>
            Confirm Password : <input type="password"  name="confpass" placeholder="Confirm Password">
            <br>
            <input type="submit" value="Change Password">
        </form> -->
        <div class="container">
            <div class="card">
                <form action=<?php echo $_SERVER['PHP_SELF']."?id=".$_GET['id']; ?> method="post">
                    <input type="password" id="inputPassword" name="password" class="form-control" placeholder="Password" required>
                    <br>
                    <input type="password" id="inputPassword" name="confpass" class="form-control" placeholder="Confirmation Password" required>
                    <br>
                    <small class="muted">Panjang Password 8 sampai 32</small>
                    <br>
                    <small class="muted">mengandung huruf besar, huruf kecil,dan Angka</small>
                    <!-- <small class="muted">Panjang Password 8 sampai 32, mengandung huruf besar, huruf kecil,dan Angka</small> -->
                    <br>
                    <button class="btn" type="submit"  name="login">Change Password</button>

                </form>
                
            </div>
        </div>
    </body>
</html>

<?php
    require dirname(__FILE__)."/../includes/DbOperation.php";

    $db = new DbOperation();

    if(isset($_POST['password']))
    {
        if(isset($_POST['password']) &&
       isset($_POST['confpass']))
        {
            $phone = $_GET['id'];
            $password = $_POST['password'];
            // $password = "123Abc";
            $confpass = $_POST['confpass'];

            // echo "T/F : ".preg_match("/^(?=^.{4,8})(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s)[0-9a-zA-Z!@#%^&*()]*$/", $password);

        if(strlen($password) <= 32 && strlen($password) >= 8)
        {

        
            if(preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/", $password))
            {
                if($password == $confpass)
                {
                    $db->updatePasswordMD5($password, $phone);
                    echo "Update Password Successfully";
                    // echo "<script>window.close();</script>";
                }
                else{
                    // Error Handling
                    echo "Some error occured.";
                }
            }
            else
            {
                echo "Password yang anda masukkan tidak sesuai dengan format yang ada.";
            }
        }else{
            echo "Panjang password yang anda masukkan tidak memenuhi kriteria kami.";
        }
        }
        else {
            echo "Required Parameter Missing";
        }
    }
?>
