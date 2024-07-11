<!-- pattern="(?=^.{4,8}$)(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s)[0-9a-zA-Z!@#$%^&*()]*$" -->
<html>
    <head>
        <title>Change Your Password</title>
        <style>
        .card {
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        transition: 0.3s;
        width: 40%;
        margin: auto;
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
            background-color: #4CAF50; /* Green */
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
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
                    <button class="btn" type="submit"  name="login">Change Password</button>
                </form>

            </div>
        </div>
    </body>
</html>

<?php
    require "../../includes/DbOperation.php";
    require "../db_connection.php";

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

        // if(strlen($password) <= 32 && strlen($password) >= 8)
        // {

                if($password == $confpass)
                {
                    $oldPass = mysqli_query($db_conn, "SELECT DATE(created_at) AS created_at FROM `reset_password` WHERE token = '$phone'");
                    $pa = mysqli_fetch_all($oldPass, MYSQLI_ASSOC);
                    $ca = $pa[0]['created_at'];
                    $today = date("Y-m-d");
                    $password = md5($password);

                    if($ca == $today){
                        $temp = $db->updatePasswordEmployee($password, $phone);
                        if($temp == USER_UPDATED){
                            echo "Update Password Successfully";
                            echo "<script>if(confirm('Your Password Sucessfully Updated. Now Login')){document.location.href='http://partner.ur-hub.com'};</script>";
                            // header('Location: http://partner.ur-hub.com');
                        }else{
                            echo "Fail Update Password Successfully";
                        }
                    }else{
                        echo $pa;
                        // echo "Maaf request anda sudah tidak berlaku";
                    }
                    // echo "<script>window.close();</script>";
                }
                else{
                    // Error Handling
                    echo "ketik ulang password tidak sama.";
                }

        // }else{
        //     echo "Panjang password yang anda masukkan tidak memenuhi kriteria kami.";
        // }
        }
        else {
            echo "Required Parameter Missing";
        }
    }
?>
