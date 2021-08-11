<?php
require_once("config.php");
require_once("functions.php");

if(isset($_POST["register"])) {
    $errors = array();

    $name = clearText($_POST["name"]);
    $email = clearEmail($_POST["email"]);
    $confirmEmail = clearEmail($_POST["email2"]);
    $password = clear($_POST["password"]);
    $confirmPassword = clear($_POST["password2"]);

    $con = DB::connect();

    if(!empty(validateName($name))) {
        foreach(validateName($name) as $err) {
            $errors[] = $err;
        }    
    }

    if(!empty(validateEmail($con, $email, $confirmEmail))) {
        foreach(validateEmail($con, $email, $confirmEmail) as $err) {
            $errors[] = $err;
        }    
    }

    if(!empty(validatePasswords($password, $confirmPassword))) {
        foreach(validatePasswords($password, $confirmPassword) as $err) {
            $errors[] = $err;
        }    
    }

    if(empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = $con->prepare("INSERT INTO users (`name`, `email`, `password`)
        VALUES (:name, :email, :pw)");

        if($query->execute([
            'name' => $name,
            'email' => $email,
            'pw' => $hashedPassword,
        ])) {
            $_SESSION["userLoggedIn"] = $email;
            $_SESSION["chatToken"] = md5(uniqid());
            header('Location: /chat/home.php');
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css">
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="form-body">
        <div class="form-header">
            <h1>Create A New Account</h1>
        </div>
        <?php 
            if(isset($_POST["register"])) {
                if(!empty($errors)) {
                    echo "<div class='errors'>";
                    foreach($errors as $error) {
                        echo $error;
                    }
                    echo "</div>";
                }
            } 
        ?>
        <form action="register.php" method="POST">
            <input type="text" id="name" class="input" name="name" value="<?php lastValue('name') ?>" placeholder="First Name" autocomplete="off" required>
            <input type="email" id="email" class="input" name="email" value="<?php lastValue('email') ?>" placeholder="Email" autocomplete="off" required>
            <input type="email" id="email2" class="input" name="email2" value="<?php lastValue('email') ?>" placeholder="Confirm Email" autocomplete="off" required>
            <input type="password" id="password" class="input" name="password" placeholder="Password" autocomplete="off" required>
            <input type="password" id="password2" class="input" name="password2" placeholder="Confirm Password" autocomplete="off" required>
            <input type="submit" id="register" class="btn" name="register" value="Register">
        </form>
        <a href="login.php" class="form-text">Already Have an Account? Login here!</a>
    </div>
</body>
</html>

