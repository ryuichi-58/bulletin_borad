<?php
require('../dbconnect.php');
session_start();

if (!empty($_POST)) {
	if ($_POST['name'] === '') {
		$error['name'] = 'blank';
	}
	if ($_POST['email'] === '') {
		$error['email'] = 'blank';
	}
	if (strlen($_POST['password']) < 4) {
		$error['password'] = 'length';
	}
	if ($_POST['password'] === '') {
		$error['password'] = 'blank';
	}
	$fileName = $_FILES['image']['name'];
	if (!empty($fileName)) {
		$ext = substr($fileName, -3);
		if ($ext != 'jpg' && $ext != 'gif' && $ext != 'png') {
		$error['image'] = 'type';
		}
	}

	//アカウントの重複チェック
	if(empty($error)) {
		$member = $db->prepare('SELECT COUNT(*) AS cnt FROM members WHERE email=?');
		$member->execute(array($_POST['email']));
		$record = $member->fetch();
		if ($record['cnt'] > 0) {
			$error['email'] = 'duplicate';
		}
	}

	if (empty($error)) {
			$image = date('YmdHis') . $_FILES['image']['name'];
			move_uploaded_file($_FILES['image']['tmp_name'], '../member_picture/' . $image);
			$_SESSION['join'] = $_POST;
			$_SESSION['join']['image'] = $image;
			header('Location: check.php');
			exit();
	}
}

if ($_REQUEST['action'] == 'rewrite' && isset($_SESSION['join'])) {
		$_POST = $_SESSION['join'];
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>メンバー登録</title>

	<link rel="stylesheet" href="../style.css" />
</head>
<body>
	<div class="form_wrapper">
		<h1>新規メンバー登録</h1>
		<form action="" method="post" enctype="multipart/form-data">
		<h2>以下の項目をご記入の上、「次へ」を押して下さい。</h2>
			<dl class="form_item">
				<dd>
					<input type="text" name="name" maxlength="50" required="required" placeholder="Name" value="<?php print (htmlspecialchars($_POST['name'], ENT_QUOTES)); ?>" />
					<?php if ($error['name'] === 'blank'): ?>
					<p class="error">* 名前を入力して下さい</p>
					<?php endif; ?>
				</dd>
				<dd>
					<input type="text" name="email" maxlength="50" required="required" placeholder="Email Address" value="<?php print (htmlspecialchars($_POST['email'], ENT_QUOTES)); ?>" />
					<?php if ($error['email'] === 'blank'): ?>
					<p class="error">* メールアドレスを入力して下さい</p>
					<?php endif; ?>
					<?php if ($error['email'] === 'duplicate'): ?>
					<p class="error">* 指定されたメールアドレスは、既に登録されています</p>
					<?php endif; ?>
				<dd>
					<input type="password" name="password" maxlength="20" required="required" placeholder="Pass Word" value="<?php print (htmlspecialchars($_POST['password'], ENT_QUOTES)); ?>" />
					<?php if ($error['password'] === 'length'): ?>
					<p class="error">* パスワードは4文字以上で入力して下さい</p>
					<?php endif; ?>
					<?php if ($error['password'] === 'blank'): ?>
					<p class="error">* パスワードを入力して下さい</p>
					<?php endif; ?>
				</dd>
			</dl>
				<dd>
					<p class="icon">アイコン用画像&#12296;任意&#12297;</p>
					<input type="file" class="file_button" name="image" size="35" value="test"  />
					<?php if ($error['image'] === 'type'): ?>
					<p class="error">* 「.gif」「.jpg」「.png」の画像を指定して下さい</p>
					<?php endif; ?>
					<?php if (!empty($error)): ?>
					<p class="error">* もう一度画像を指定して下さい</p>
					<?php endif; ?>
				</dd>
				<div class="button_panel">
					<input type="submit" class="next_button" value="次へ" />
				</div>
		</form>
	</div>
</body>
</html>
