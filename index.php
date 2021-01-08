<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();

    $members = $db->prepare('SELECT * FROM members WHERE id=?');
    $members->execute(array($_SESSION['id']));
    $member = $members->fetch();
} else {
    // ログインしていない
    header('Location: login.php');
    exit();
}

// 投稿を記録する
if (!empty($_POST)) {
    if ($_POST['message'] != '') {
        if (!isset($_REQUEST['res'])) {
            $_POST['reply_post_id'] = 0;
        }
        $message = $db->prepare('INSERT INTO posts
                                    SET member_id=?,
                                    message=?, reply_post_id=?, created=NOW()');
        $message->execute(array(
            $member['id'],
            $_POST['message'],
            $_POST['reply_post_id']
        ));
        header('Location: index.php');
        exit();
    }
}

// 投稿を取得する
$page = $_REQUEST['page'];
if ($page == '') {
    $page = 1;
}
$page = max($page, 1);

// 最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);
$start = ($page - 1) * 5;
$posts = $db->prepare('SELECT m.name, m.picture, p.*
                        FROM members m, posts p
                        WHERE m.id=p.member_id
                        ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

// 返信の場合
if (isset($_REQUEST['res'])) {
    $response = $db->prepare('SELECT m.name, m.picture, p.*
                                FROM members m.posts p
                                WHERE m.id=p.member_id
                                AND p.id=? ORDER BY p.created DESC');
    $response->execute(array($_REQUEST['res']));

    $table = $response->fetch();
    $message = '@' . $table['name'] . ' ' . $table['message'];
}
// hファンクション
function h($value) {
    return htmlentities($value, ENT_QUOTES);
}
// 本文内のURLにリンクを設定します
function makeLink($value) {
    return mb_ereg_replace("(https?)(://[[:alnum]\+\$\;\?\.%,!#~*/:@&=_-]+)", '<a href="\1\2">\1\2</a>', $value);
}

// メッセージ別のいいねされた件数をDBから取り出す
$posts = $db->prepare('SELECT m.name, m.picture, p.*,
                        COUNT(l.post_id) AS like_cnt
                        FROM members m, posts p
                        LEFT JOIN likes l
                        ON p.id=l.post_id
                        WHERE m.id=p.member_id
                        GROUP BY l.post_id
                        ORDER BY p.created DESC LIMIT ?, 5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

// いいねしたユーザー
if (isset($_REQUEST['like'])) {
    $user = $db->prepare('SELECT member_id FROM posts WHERE id=?');
    $user->execute(array($_REQUEST['like']));
    $liked_person = $user->fetch();

    // 投稿者がいいねしていないか確認
    if ($_SESSION['id'] != $liked_person['member_id']) {
    // 過去にいいねしていないか確認
        $pushed = $db->prepare('SELECT COUNT(*) AS cnt FROM likes WHERE post_id=? AND member_id=?');
        $pushed->execute(array(
            $_REQUEST['like'],
            $_SESSION['id']
        ));
        $like_cnt = $pushed->fetch();
        // いいねの挿入と削除
        if ($like_cnt['cnt'] < 1) {
            $like_push = $db->prepare('INSERT INTO likes SET post_id=?, member_id=?, created=NOW()');
            $like_push->execute(array(
                $_REQUEST['like'],
                $_SESSION['id']
            ));
            header("Location: index.php?page={$page}");
            exit();
        } else {
            $like_delete = $db->prepare('DELETE FROM likes WHERE post_id=? AND member_id=?');
            $like_delete->execute(array(
                $_REQUEST['like'],
                $login_user
            ));
            header("Location: index.php?page={$page}");
            exit();
        }
    }
}
$like = $db->prepare('SELECT post_id FROM likes WHERE member_id=?');
$like->execute(array($_SESSION['id']));
while ($like_record = $like->fetch()) {
    $my_like[] = $like_record;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://kit.fontawesome.com/250c1a3838.js" crossorigin="anonymous"></script>
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
</head>

<body>
<div class="wrap">
    <div class="writing_title">
        <h1>ひとこと掲示板</h1>
    </div>
    <div class="content">
        <div class="content_wrapper">
            <div class="logout_button" style="text-align: right"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> ログアウト</a></div>
            <form class="content_form" action="" method="post">
                <dl>
                    <dt class="user_name"><i class="far fa-edit"></i>ログイン中： <?php echo h($member['name']); ?></dt>
                    <dd>
                    <textarea name="message" cols="80" rows="5"><?php echo h($message); ?></textarea>
                    <input type="hidden" name="reply_post_id" value="<?php echo h($_REQUEST['res']); ?>" />
                    </dd>
                </dl>
                <div class="write_button">
                    <input type="submit" class="button" value="書き込む" />
                </div>
            </form>
        </div>
    </div>
    <?php foreach ($posts as $post): ?>
    <div>
        <div class="msg">
            <section class="msg_wrapper">
                <div class="msg_container">
                    <p><img src="member_picture/<?php echo h($post['picture']); ?>" width="40" height="40" alt="<?php echo h($post['name']);?>" /></p>
                    <article class="user">
                    <span class="name"><?php echo h($post['name']) ?></span>
                    </article>
                    <article class="day">
                    <div class="created">
                        <?php echo h($post['created']); ?>
                    </div>
                    <!-- ライクボタン -->
                    <?php
                    $like_cnt = 0;
                    if (!empty($my_like)) {
                        foreach($my_like as $like_post) {
                            foreach ($like_post as $like_post_id) {
                                if ($like_post_id == $post['id']) {
                                    $like_cnt = 1;
                                }
                            }
                        }
                    }
                    ?>
                    <?php if ($like_cnt < 1): ?>
                        <div>
                        <p><a href="index.php?like=<?php echo h($post['id']); ?>&page=<?php echo h($page); ?>"><i class="far fa-heart"></i></a></p>
                        </div>
                    <?php else : ?>
                        <div class="likes_button">
                        <p><a href="index.php?like=<?php echo h($post['id']); ?>&page=<?php echo h($page); ?>"></a></p>
                        </div>
                    <?php endif; ?>
                        <div>
                        <span><?php echo h($post['like_cnt']); ?></span>
                        </div>
                    <div class="icon_reply">
                        <p class="meg_reply"><a href="index.php?res=<?php echo h($post['id']); ?>"><i class="fas fa-reply"></i> 返信</a></p>
                    </div>
                    <div class="icon_trash">
                        <?php if ($_SESSION['id'] == $post['member_id']): ?>
                        <p class="msg_delete"><a href="delete.php?id=<?php echo h($post['id']); ?>"><i class="far fa-trash-alt"></i> 削除</a></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($post['reply_post_id'] > 0): ?>
                        <a href="view.php?id=<?php echo h($post['reply_post_id']); ?>"><i class="fas fa-envelope-square"></i> 返信元を見る</a>
                        <?php endif; ?>
                    </div>
                    </article>
                </div>
                <article class="post">
                    <?php echo makeLink(h($post['message'])); ?>
                </article>
            </section>
            <div class="space"></div>
        </div>
    </div>
    <?php endforeach; ?>
    <ul class="paging">
        <?php if ($page > 1) { ?>
        <li><a href="index.php?page=<?php print($page - 1); ?>"><i class="far fa-caret-square-left"></i> Back</a></li>
        <?php } else { ?>
        <li class="link_off"><i class="far fa-caret-square-left"></i> Back</li>
        <?php } ?>
        <?php if ($page < $maxPage) { ?>
        <li><a href="index.php?page=<?php print($page + 1); ?>">Front <i class="far fa-caret-square-right"></i></a></li>
        <?php } else { ?>
        <li class="link_off">Front <i class="far fa-caret-square-right"></i></li>
        <?php } ?>
    </ul>
</div>
</body>
</html>
