<?php
require('dbconnect.php');

session_start();

if ($_COOKIE['email'] != '') {
    $_POST['email'] = $_COOKIE['email'];
    $_POST['password'] = $_COOKIE['password'];
    $_POST['save'] = 'on';
}

if (!empty($_POST)) {
    // ログイン処理
    if ($_POST['email'] != '' && $_POST['password'] != '') {
        $login = $db->prepare('SELECT * FROM members WHERE email=? AND password=?');
        $login->execute(array(
            $_POST['email'],
            sha1($_POST['password'])
        ));
        $member = $login->fetch();

        if ($member) {
            // ログイン成功
            $_SESSION['id'] = $member['id'];
            $_SESSION['time'] = time();

            // ログイン情報を記録する
            if ($_POST['save'] == 'on') {
                setcookie('email', $_POST['email'], time()+ 60 * 60 * 24 * 14);
                setcookie('password', $_POST['password'], time() + 60 * 60 * 24 * 14);
            }

            header('Location: index.php');
            exit();
        } else {
            $error['login'] = 'failed';
        }
    } else {
        $error['login'] = 'blank';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<title>SIGN IN</title>
</head>
<body>
	<div class="form_wrapper">
		<h1>Member Login</h1>
        <form action="" method="post">
		    <div class="form_item">
                <label for="email"></label>
                <input type="email" name="email" required="required" placeholder="Email Address" value="<?php echo htmlspecialchars($_POST['email']); ?>"/></input>
                <?php if ($error['login'] == 'blank'): ?>
                <p class="error">* メールアドレスが正しくありません</p>
                <?php endif; ?>
                <?php if ($error['login'] == 'failed'): ?>
                <p class="error">* ログインに失敗しました。</p>
                <?php endif; ?>
            </div>
            <div class="form_item">
                <label for="password"></label>
                <input type="password" name="password" required="required" placeholder="Password" value="<?php echo htmlspecialchars($_POST['password']); ?>" /></input>
            </div>
            <?php if ($error['password'] == 'blank'): ?>
            <p class="error">* パスワードが正しくありません</p>
            <?php endif; ?>
            <div class="button_panel">
                <input type="submit" class="button" title="login" value="login"></input>
            </div>
            <div class="form_footer">
                <input id="save" type="checkbox" name="save" value="on">
                <label for="save"><a>ログインを保存</a></label>
                <p class="new_created"><a href="join/">新規アカウントを作成</a></p>
            </div>
        </form>
    </div>
</body>
</html>
