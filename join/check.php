<?php
session_start();
require('../dbconnect.php');

if (!isset($_SESSION['join'])) {
	header('Location: index.php');
	exit();
}

if (!empty($_POST)) {
	$statement = $db->prepare('INSERT INTO members SET name=?, email=?, password=?, picture=?, created=NOW()');
	echo $ret = $statement->execute(array(
		$_SESSION['join']['name'],
		$_SESSION['join']['email'],
		sha1($_SESSION['join']['password']),
		$_SESSION['join']['image']
	));
	unset($_SESSION['join']);

	header('Location: thanks.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>確認画面</title>

	<link rel="stylesheet" href="../style.css" />
</head>
<body>
	<div class="confirmation_wrapper">
		<p class="announce">以下の内容で間違いなければ、「登録する」を押してください。</p>
		<form action="" method="post">
	<input type="hidden" name="action" value="submit" />
	<dl class="border">
		<dt class="sub_title">ネーム</dt>
		<dd class="input_value">
		<?php print(htmlspecialchars($_SESSION['join']['name'], ENT_QUOTES)); ?>
		</dd>
	</dl>
	<dl class="border">
		<dt class="sub_title">メールアドレス</dt>
		<dd class="input_value">
		<?php print(htmlspecialchars($_SESSION['join']['email'], ENT_QUOTES)); ?>
		</dd>
	</dl>
	<dl class="border">
		<dt class="sub_title">パスワード</dt>
		<dd class="input_value">
		【表示されません】
		</dd>
	</dl>
	<dl class="border">
		<dt class="sub_title">アイコン用画像</dt>
		<dd class="input_value">
		<?php if ($_SESSION['join']['image'] !== ''): ?>
		<img src="../member_picture/<?php print(htmlspecialchars($_SESSION['join']['image'], ENT_QUOTES)); ?>">
		<?php endif; ?>
		</dd>
	</dl>
	<div class="verification">
		<a href="index.php?action=rewrite" class="return_button">戻る</a>
		<input type="submit" class="button" value="登録する" />
	</div>
</form>
</body>
</html>
