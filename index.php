<?php
require_once 'config.php';
require_once 'functions.php';
session_start();

$conn = getDb();
$user = getCurrentUser();
$page = $_GET['page'] ?? 'home';
$error = '';
$success = '';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if ($user) {
        recordLog($user['id'], $user['account'], 'logout', true, '登出成功');
    }
    session_destroy();
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'register') {
        $account = trim($_POST['account'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $gender = $_POST['gender'] ?? 'other';
        $interests = trim($_POST['interests'] ?? '');

        if (!$account || !$nickname || !$password) {
            $error = '帳號、暱稱與密碼為必填。';
        } elseif ($password !== $password_confirm) {
            $error = '兩次密碼不一致。';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $account)) {
            $error = '帳號格式錯誤（3-20，僅字母數字底線）。';
        } else {
            $stmt = $conn->prepare('SELECT id FROM dbusers WHERE account = ?');
            $stmt->bind_param('s', $account);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($exists) {
                $error = '帳號已存在。';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('INSERT INTO dbusers (account, nickname, password, gender, interests) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssss', $account, $nickname, $hash, $gender, $interests);
                if ($stmt->execute()) {
                    recordLog((int)$conn->insert_id, $account, 'register', true, '成功註冊');
                    $success = '註冊成功，請登入。';
                    $page = 'login';
                } else {
                    $error = '註冊失敗，請稍後重試。';
                }
                $stmt->close();
            }
        }
    }

    if ($page === 'login') {
        $account = trim($_POST['account'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$account || !$password) {
            $error = '請輸入帳號與密碼。';
        } else {
            $stmt = $conn->prepare('SELECT id, account, nickname, password, gender, interests FROM dbusers WHERE account = ?');
            $stmt->bind_param('s', $account);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                recordLog(null, $account, 'login', false, '帳號不存在');
                $error = '帳號或密碼錯誤。';
            } else {
                $u = $result->fetch_assoc();
                if (!password_verify($password, $u['password'])) {
                    recordLog((int)$u['id'], $account, 'login', false, '密碼錯誤');
                    $error = '帳號或密碼錯誤。';
                } else {
                    $_SESSION['user_id'] = (int)$u['id'];
                    $_SESSION['account'] = $u['account'];
                    $_SESSION['nickname'] = $u['nickname'];
                    $_SESSION['gender'] = $u['gender'];
                    $_SESSION['interests'] = $u['interests'];
                    recordLog((int)$u['id'], $u['account'], 'login', true, '登入成功');
                    redirect('index.php');
                }
            }
            $stmt->close();
        }
    }

    if ($page === 'upload' && $user) {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $inspiration = trim($_POST['inspiration'] ?? '');

        if (!$title || !$content) {
            $error = '標題與內容為必填。';
        } elseif (!in_array($category, ['風景', '人物', '動物', '植物', '其他'], true)) {
            $error = '分類無效。';
        } else {
            $image_url = null;
            $thumbnail_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    $error = '圖片只支援 JPG/PNG/GIF。';
                } else {
                    $uploadDir = __DIR__ . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $filename = 'memo_' . $user['id'] . '_' . time() . '.' . $ext;
                    $fullPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                        $image_url = 'uploads/' . $filename;
                        $thumb = generateThumbnail($fullPath, 480, 320);
                        if ($thumb) {
                            $thumbName = 'thumb_' . $filename;
                            file_put_contents($uploadDir . $thumbName, $thumb);
                            $thumbnail_url = 'uploads/' . $thumbName;
                        } else {
                            $thumbnail_url = $image_url;
                        }
                    } else {
                        $error = '圖片上傳失敗。';
                    }
                }
            }

            if (!$error) {
                $stmt = $conn->prepare('INSERT INTO dbmemo (user_id, title, category, content, inspiration, image_path, thumbnail_path, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
                $stmt->bind_param('issssss', $user['id'], $title, $category, $content, $inspiration, $image_url, $thumbnail_url);
                if ($stmt->execute()) {
                    $success = '作品新增成功。';
                    $page = 'home';
                } else {
                    $error = '作品新增失敗。';
                }
                $stmt->close();
            }
        }
    }

    if ($page === 'edit' && $user) {
        $memoId = (int)($_GET['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $inspiration = trim($_POST['inspiration'] ?? '');

        $stmt = $conn->prepare('SELECT image_path, thumbnail_path FROM dbmemo WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $memoId, $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $error = '找不到作品或無權限。';
        } elseif (!$title || !$content) {
            $error = '標題與內容為必填。';
        } elseif (!in_array($category, ['風景', '人物', '動物', '植物', '其他'], true)) {
            $error = '分類無效。';
        } else {
            $image_url = $row['image_path'];
            $thumbnail_url = $row['thumbnail_path'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    $error = '圖片只支援 JPG/PNG/GIF。';
                } else {
                    $uploadDir = __DIR__ . '/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $filename = 'memo_' . $user['id'] . '_' . time() . '.' . $ext;
                    $fullPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                        removeFileIfExists($image_url);
                        removeFileIfExists($thumbnail_url);
                        $image_url = 'uploads/' . $filename;
                        $thumb = generateThumbnail($fullPath, 480, 320);
                        if ($thumb) {
                            $thumbName = 'thumb_' . $filename;
                            file_put_contents($uploadDir . $thumbName, $thumb);
                            $thumbnail_url = 'uploads/' . $thumbName;
                        } else {
                            $thumbnail_url = $image_url;
                        }
                    } else {
                        $error = '圖片上傳失敗。';
                    }
                }
            }

            if (!$error) {
                $stmt = $conn->prepare('UPDATE dbmemo SET title = ?, category = ?, content = ?, inspiration = ?, image_path = ?, thumbnail_path = ? WHERE id = ? AND user_id = ?');
                $stmt->bind_param('ssssssii', $title, $category, $content, $inspiration, $image_url, $thumbnail_url, $memoId, $user['id']);
                if ($stmt->execute()) {
                    $success = '作品更新成功。';
                    $page = 'home';
                } else {
                    $error = '更新失敗。';
                }
                $stmt->close();
            }
        }
    }
}

if ($page === 'delete' && $user) {
    $memoId = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare('SELECT image_path, thumbnail_path FROM dbmemo WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $memoId, $user['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $stmt = $conn->prepare('DELETE FROM dbmemo WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $memoId, $user['id']);
        $stmt->execute();
        $stmt->close();
        removeFileIfExists($row['image_path']);
        removeFileIfExists($row['thumbnail_path']);
    }
    redirect('index.php');
}

$user = getCurrentUser();
$myMemos = [];
$publicMemos = [];
$logs = [];
if ($user) {
    $stmt = $conn->prepare('SELECT id, title, category, content, inspiration, thumbnail_path, created_at FROM dbmemo WHERE user_id = ? ORDER BY created_at DESC');
if ($stmt) {
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
    $myMemos[] = $r;
    }
    $stmt->close();
} else {
    $error = '載入個人作品失敗：' . $conn->error;
    }
}
if ($user) {
    $stmt = $conn->prepare("
        SELECT event, success, ip_address, notes, created_at 
        FROM dblog 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $logs[] = $row;
        }

        $stmt->close();
    }
}
$stmt = $conn->prepare('SELECT m.id, m.title, m.category, m.content, m.inspiration, m.thumbnail_path, m.created_at, u.nickname FROM dbmemo m JOIN dbusers u ON u.id = m.user_id WHERE m.is_public = 1 ORDER BY m.created_at DESC');
$publicMemos = [];
if ($stmt) {
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $publicMemos[] = $r;
  }
  $stmt->close();
} else {
  $error = ($error ? $error . '；' : '') . '載入公開作品失敗：' . $conn->error;
}

$editMemo = null;
if ($page === 'edit' && $user) {
    $memoId = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare('SELECT * FROM dbmemo WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $memoId, $user['id']);
    $stmt->execute();
    $editMemo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$editMemo) {
        $page = 'home';
        $error = '找不到可編輯作品。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>攝影作品分享</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; color: #2d3d50; margin: 0; }
    header { background: #4f5f76; color: #fff; padding: 16px; }
    .nav a { color: #fff; margin-right: 12px; text-decoration: none; }
    .container { max-width: 1100px; margin: 20px auto; padding: 0 14px; }
    .box { background: #fff; border-radius: 10px; padding: 18px; margin-bottom: 18px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
    label { display: block; margin: 10px 0 6px; }
    input[type=text], input[type=password], textarea, select, input[type=file] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
    textarea { min-height: 110px; }
    button, .btn { background: #457b9d; color: #fff; border: 0; border-radius: 8px; padding: 9px 14px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 10px; }
    .btn.secondary { background: #8bbcc6; color: #18334a; }
    .btn.danger { background: #c53c3c; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
    .card { border: 1px solid #ddd; border-radius: 10px; overflow: hidden; background: #fff; }
    .card img { width: 100%; height: 220px; object-fit: cover; background: #f0f0f0; }
    .card-body { padding: 12px; }
    .meta { font-size: 12px; color: #666; margin-bottom: 6px; }
    .alert { padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
    .alert.error { background: #ffdfe2; color: #7b1f28; }
    .alert.success { background: #dff4df; color: #1b5e1b; }
  </style>
</head>
<body>
  <header>
    <h1>攝影作品分享</h1>
    <div class="nav">
      <a href="index.php">首頁</a>
      <?php if ($user): ?>
        <a href="index.php?page=upload">上傳作品</a>
        <a href="index.php?action=logout">登出</a>
      <?php else: ?>
        <a href="index.php?page=register">註冊</a>
        <a href="index.php?page=login">登入</a>
      <?php endif; ?>
    </div>
  </header>

  <div class="container">
    <?php if ($error): ?><div class="alert error"><?php echo h($error); ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?php echo h($success); ?></div><?php endif; ?>

    <?php if ($page === 'register' && !$user): ?>
      <div class="box">
        <h2>註冊帳號</h2>
        <form method="POST" action="index.php?page=register">
          <label>帳號 *</label>
          <input type="text" name="account" required value="<?php echo h($_POST['account'] ?? ''); ?>" />
          <label>暱稱 *</label>
          <input type="text" name="nickname" required value="<?php echo h($_POST['nickname'] ?? ''); ?>" />
          <label>密碼 *</label>
          <input type="password" name="password" required />
          <label>確認密碼 *</label>
          <input type="password" name="password_confirm" required />
          <label>性別</label>
          <select name="gender">
            <option value="other">其他</option>
            <option value="male">男</option>
            <option value="female">女</option>
          </select>
          <label>興趣</label>
          <input type="text" name="interests" value="<?php echo h($_POST['interests'] ?? ''); ?>" />
          <button type="submit">註冊</button>
          <a class="btn secondary" href="index.php?page=login">改去登入</a>
        </form>
      </div>

    <?php elseif ($page === 'login' && !$user): ?>
      <div class="box">
        <h2>登入</h2>
        <form method="POST" action="index.php?page=login">
          <label>帳號 *</label>
          <input type="text" name="account" required value="<?php echo h($_POST['account'] ?? ''); ?>" />
          <label>密碼 *</label>
          <input type="password" name="password" required />
          <button type="submit">登入</button>
          <a class="btn secondary" href="index.php?page=register">改去註冊</a>
        </form>
      </div>

    <?php elseif ($page === 'upload' && $user): ?>
      <div class="box">
        <h2>上傳作品</h2>
        <form method="POST" action="index.php?page=upload" enctype="multipart/form-data">
          <label>標題 *</label>
          <input type="text" name="title" required value="<?php echo h($_POST['title'] ?? ''); ?>" />
          <label>分類 *</label>
          <select name="category" required>
            <option value="風景">風景</option><option value="人物">人物</option><option value="動物">動物</option><option value="植物">植物</option><option value="其他">其他</option>
          </select>
          <label>內容 *</label>
          <textarea name="content" required><?php echo h($_POST['content'] ?? ''); ?></textarea>
          <label>靈感</label>
          <textarea name="inspiration"><?php echo h($_POST['inspiration'] ?? ''); ?></textarea>
          <label>圖片</label>
          <input type="file" name="image" accept="image/*" />
          <button type="submit">送出</button>
          <a class="btn secondary" href="index.php">返回首頁</a>
        </form>
      </div>

    <?php elseif ($page === 'edit' && $user && $editMemo): ?>
      <div class="box">
        <h2>編輯作品</h2>
        <form method="POST" action="index.php?page=edit&id=<?php echo (int)$editMemo['id']; ?>" enctype="multipart/form-data">
          <label>標題 *</label>
          <input type="text" name="title" required value="<?php echo h($_POST['title'] ?? $editMemo['title']); ?>" />
          <label>分類 *</label>
          <select name="category" required>
            <?php $selectedCat = $_POST['category'] ?? $editMemo['category']; ?>
            <option value="風景" <?php echo $selectedCat === '風景' ? 'selected' : ''; ?>>風景</option>
            <option value="人物" <?php echo $selectedCat === '人物' ? 'selected' : ''; ?>>人物</option>
            <option value="動物" <?php echo $selectedCat === '動物' ? 'selected' : ''; ?>>動物</option>
            <option value="植物" <?php echo $selectedCat === '植物' ? 'selected' : ''; ?>>植物</option>
            <option value="其他" <?php echo $selectedCat === '其他' ? 'selected' : ''; ?>>其他</option>
          </select>
          <label>內容 *</label>
          <textarea name="content" required><?php echo h($_POST['content'] ?? $editMemo['content']); ?></textarea>
          <label>靈感</label>
          <textarea name="inspiration"><?php echo h($_POST['inspiration'] ?? $editMemo['inspiration']); ?></textarea>
          <?php if (!empty($editMemo['thumbnail_path'])): ?>
            <img src="<?php echo h($editMemo['thumbnail_path']); ?>" alt="現有圖片" style="max-width:260px; border-radius:8px; margin-top:8px;" />
          <?php endif; ?>
          <label>更換圖片</label>
          <input type="file" name="image" accept="image/*" />
          <button type="submit">儲存</button>
          <a class="btn secondary" href="index.php">返回首頁</a>
        </form>
      </div>

    <?php else: ?>
      <?php if ($user): ?>
        <div class="box">
          <h2>歡迎，<?php echo h($user['nickname']); ?></h2>
          <p>帳號：<?php echo h($user['account']); ?> | 性別：<?php echo h($user['gender']); ?> | 興趣：<?php echo h($user['interests']); ?></p>
          <a class="btn" href="index.php?page=upload">新增作品</a>
        </div>

        <div class="box">
          <h2>我的作品</h2>
          <?php if (!$myMemos): ?>
            <p>尚無作品。</p>
          <?php else: ?>
            <div class="grid">
              <?php foreach ($myMemos as $m): ?>
                <div class="card">
                  <?php if ($m['thumbnail_path']): ?><img src="<?php echo h($m['thumbnail_path']); ?>" alt="縮圖" /><?php endif; ?>
                  <div class="card-body">
                    <div class="meta"><?php echo h($m['created_at']); ?> | <?php echo h($m['category']); ?></div>
                    <h3><?php echo h($m['title']); ?></h3>
                    <p><?php echo nl2br(h($m['content'])); ?></p>
                    <p><strong>靈感：</strong><?php echo nl2br(h($m['inspiration'])); ?></p>
                    <a class="btn" href="index.php?page=edit&id=<?php echo (int)$m['id']; ?>">編輯</a>
                    <a class="btn danger" href="index.php?page=delete&id=<?php echo (int)$m['id']; ?>" onclick="return confirm('確認刪除？')">刪除</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="box">
  <h2>我的操作紀錄</h2>

  <?php if (!$logs): ?>
    <p>尚無紀錄。</p>
  <?php else: ?>
    <table border="1" cellpadding="8" style="width:100%; border-collapse: collapse;">
      <tr>
        <th>時間</th>
        <th>事件</th>
        <th>結果</th>
        <th>IP</th>
        <th>備註</th>
      </tr>

      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?php echo h($log['created_at']); ?></td>
          <td><?php echo h($log['event']); ?></td>
          <td>
            <?php echo $log['success'] ? '成功' : '失敗'; ?>
          </td>
          <td><?php echo h($log['ip_address']); ?></td>
          <td><?php echo h($log['notes']); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
      <?php else: ?>
        <div class="box">
          <h2>歡迎訪問攝影作品分享平台</h2>
          <p>請先註冊或登入後再上傳作品。</p>
          <a class="btn" href="index.php?page=register">註冊</a>
          <a class="btn secondary" href="index.php?page=login">登入</a>
        </div>
    <?php endif; ?>

    <div class="box">
        <h2>作品交流區</h2>
        <?php if (!$publicMemos): ?>
        <p>目前尚無作品。</p>
        <?php else: ?>
        <div class="grid">
            <?php foreach ($publicMemos as $m): ?>
            <div class="card">
                <?php if ($m['thumbnail_path']): ?><img src="<?php echo h($m['thumbnail_path']); ?>" alt="縮圖" /><?php endif; ?>
                <div class="card-body">
                <div class="meta">作者：<?php echo h($m['nickname']); ?> | <?php echo h($m['created_at']); ?> | <?php echo h($m['category']); ?></div>
                <h3><?php echo h($m['title']); ?></h3>
                <p><?php echo nl2br(h($m['content'])); ?></p>
                <p><strong>靈感：</strong><?php echo nl2br(h($m['inspiration'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
