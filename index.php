<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>攝影作品分享</title>
  <style>
    /* ========== 全域樣式 ========== */
    body { 
      font-family: Arial, sans-serif; 
      background: #f4f4f4; 
      color: #627899; 
      margin: 0; 
      padding: 0; 
    }

    /* 頁首樣式 */
    header { 
      background: #5a6b84; 
      color: white; 
      padding: 16px 24px; 
    }
    header h1 { 
      margin: 0; 
      font-size: 24px; 
      text-align: center; 
    }

    /* 主容器 */
    .container { 
      max-width: 1100px; 
      margin: 24px auto; 
      padding: 0 16px; 
    }

    /* 區塊盒子 */
    .box { 
      background: white; 
      border-radius: 12px; 
      box-shadow: 0 2px 10px rgba(0,0,0,0.08); 
      margin-bottom: 24px; 
      padding: 20px; 
    }

    h2 { 
      margin-top: 0; 
    }

    /* 表單元素 */
    label { 
      display: block; 
      margin: 12px 0 4px; 
    }
    input[type=text], input[type=password], textarea, select { 
      width: 100%; 
      padding: 10px; 
      border: 1px solid #ccc; 
      border-radius: 8px; 
      box-sizing: border-box; 
    }
    textarea { 
      min-height: 120px; 
      resize: vertical; 
    }

    /* 按鈕樣式 */
    button { 
      background: #457b9d; 
      color: white; 
      border: none; 
      padding: 10px 16px; 
      border-radius: 8px; 
      cursor: pointer; 
      margin-top: 12px; 
      margin-right: 8px;
    }
    button:hover {
      background: #2a5a8a;
    }
    button.secondary { 
      background: #a8dadc; 
      color: #1d3557; 
    }
    button.secondary:hover {
      background: #8bc5d4;
    }

    /* 格線佈局 */
    .grid { 
      display: grid; 
      gap: 20px; 
    }
    .grid-2 { 
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
    }

    /* 作品卡片 */
    .memo-card { 
      border: 1px solid #ddd; 
      border-radius: 12px; 
      overflow: hidden; 
      display: flex; 
      flex-direction: column; 
    }
    .memo-card img { 
      width: 100%; 
      aspect-ratio: 1 / 1; 
      object-fit: contain; 
      background-color: rgb(255, 255, 255); 
      object-position: center; 
    }
    .memo-body { 
      padding: 16px; 
      flex: 1; 
    }
    .memo-meta { 
      font-size: 0.9em; 
      color: #555; 
      margin-bottom: 8px; 
    }
    .memo-actions button { 
      margin-right: 8px; 
      margin-top: 12px; 
    }

    /* 標題與隱藏 */
    .section-title { 
      margin: 24px 0 12px; 
      font-size: 20px; 
    }
    .hidden { 
      display: none !important; 
    }

    /* 表格樣式 */
    .log-table, .log-table th, .log-table td { 
      border: 1px solid #ccc; 
      border-collapse: collapse; 
    }
    .log-table th, .log-table td { 
      padding: 8px; 
    }
    .log-table th {
      background: #f0f0f0;
    }

    /* 狀態提示 */
    .status-success { 
      color: green; 
    }
    .status-fail { 
      color: red; 
    }

    /* 消息提示 */
    .alert-box {
      padding: 12px 16px;
      margin-bottom: 16px;
      border-radius: 8px;
      display: none;
    }
    .alert-box.show {
      display: block;
    }
    .alert-box.success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert-box.error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    /* 載入中 */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #457b9d;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <!-- 頁首 -->
  <header>
    <h1>攝影作品分享</h1>
  </header>

  <div class="container">
    <!-- 主要內容 -->
    <div id="auth-content">
      <!-- 帳號管理區塊 -->
      <div class="box" id="account-box">
        <!-- 消息提示 -->
        <div id="alert-box" class="alert-box"></div>

        <!-- 註冊區 -->
        <div id="register-section">
          <h2>註冊帳號</h2>
          <form id="register-form" onsubmit="handleRegister(event)">
            <label>帳號</label>
            <input type="text" id="register-account" placeholder="請輸入帳號" required />
            <label>暱稱</label>
            <input type="text" id="register-nickname" placeholder="請輸入暱稱" required />
            <label>密碼</label>
            <input type="password" id="register-password" placeholder="請輸入密碼" required />
            <label>性別</label>
            <select id="register-gender">
              <option value="male">男</option>
              <option value="female">女</option>
              <option value="other">其他</option>
            </select>
            <label>興趣</label>
            <input type="text" id="register-interests" placeholder="攝影、旅遊、風景..." />
            <button type="submit">註冊</button>
            <button type="button" class="secondary" onclick="switchToLogin()">已有帳號？登入</button>
          </form>
        </div>

        <!-- 登入區（預設隱藏） -->
        <div id="login-section" class="hidden">
          <h2>登入</h2>
          <form id="login-form" onsubmit="handleLogin(event)">
            <label>帳號</label>
            <input type="text" id="login-account" placeholder="請輸入帳號" required />
            <label>密碼</label>
            <input type="password" id="login-password" placeholder="請輸入密碼" required />
            <button type="submit">登入</button>
            <button type="button" class="secondary" onclick="switchToRegister()">還沒有帳號？註冊</button>
          </form>
        </div>

        <!-- 使用者面板（登入後顯示） -->
        <div id="user-panel" class="hidden">
          <h2>歡迎回來，<span id="user-display"></span></h2>
          <p>帳號：<span id="user-account"></span></p>
          <p>性別：<span id="user-gender"></span></p>
          <p>興趣：<span id="user-interests"></span></p>
          <button onclick="handleLogout()">登出</button>
        </div>
      </div>
    </div>

    <!-- 登入後內容（預設隱藏） -->
    <div id="main-content" class="hidden">
      <!-- 作品編輯區 -->
      <div class="box" id="memo-box">
        <h2>上傳作品 / 圖文備忘</h2>
        <form id="memo-form" onsubmit="handleSaveMemo(event)" enctype="multipart/form-data">
          <label>標題</label>
          <input type="text" id="memo-title" placeholder="作品標題" required />
          <label>分類</label>
          <select id="memo-category" required>
            <option value="風景">風景</option>
            <option value="人物">人物</option>
            <option value="動物">動物</option>
            <option value="植物">植物</option>
            <option value="其他">其他</option>
          </select>
          <label>內容</label>
          <textarea id="memo-content" placeholder="分享攝影作品內容與靈感來源..." required></textarea>
          <label>靈感來源</label>
          <textarea id="memo-inspiration" placeholder="輸入作品靈感來源或拍攝概念..."></textarea>
          <label>上傳圖片</label>
          <input type="file" id="memo-image" accept="image/*" />
          <button type="submit">儲存作品</button>
          <button type="button" class="secondary" onclick="resetMemoForm()">清除</button>
          <input type="hidden" id="memo-id" />
        </form>
      </div>

      <!-- 作品展示區 -->
      <div class="box" id="gallery-box">
        <h2>作品區</h2>
        <h3 class="section-title">自己的作品欄</h3>
        <div id="own-memo-list" class="grid grid-2"></div>
        <h3 class="section-title">作品交流區</h3>
        <div id="public-memo-list" class="grid grid-2"></div>
      </div>

      <!-- 登入紀錄區 -->
      <div class="box" id="logs-box">
        <h2>登入紀錄</h2>
        <table class="log-table" style="width:100%;">
          <thead>
            <tr>
              <th>帳號</th>
              <th>日期時間</th>
              <th>是否成功</th>
              <th>事件</th>
            </tr>
          </thead>
          <tbody id="log-body"></tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    // ========== 基礎功能 ==========

    /**
     * 顯示提示訊息
     */
    function showAlert(message, type = 'success') {
      const alert = document.getElementById('alert-box');
      alert.textContent = message;
      alert.className = `alert-box show ${type}`;
      setTimeout(() => alert.classList.remove('show'), 5000);
    }

    /**
     * 切換到登入區
     */
    function switchToLogin() {
      document.getElementById('register-section').classList.add('hidden');
      document.getElementById('login-section').classList.remove('hidden');
    }

    /**
     * 切換到註冊區
     */
    function switchToRegister() {
      document.getElementById('login-section').classList.add('hidden');
      document.getElementById('register-section').classList.remove('hidden');
    }

    // ========== 認證相關 ==========

    /**
     * 處理使用者註冊
     */
    async function handleRegister(event) {
      event.preventDefault();

      const formData = new FormData(document.getElementById('register-form'));
      formData.set('account', document.getElementById('register-account').value);
      formData.set('nickname', document.getElementById('register-nickname').value);
      formData.set('password', document.getElementById('register-password').value);
      formData.set('gender', document.getElementById('register-gender').value);
      formData.set('interests', document.getElementById('register-interests').value);

      try {
        const response = await fetch('api/auth.php?action=register', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          showAlert('註冊完成，請登入。', 'success');
          document.getElementById('register-form').reset();
          setTimeout(switchToLogin, 2000);
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        showAlert('網路錯誤，請稍後重試。', 'error');
      }
    }

    /**
     * 處理使用者登入
     */
    async function handleLogin(event) {
      event.preventDefault();

      const formData = new FormData(document.getElementById('login-form'));
      formData.set('account', document.getElementById('login-account').value);
      formData.set('password', document.getElementById('login-password').value);

      try {
        const response = await fetch('api/auth.php?action=login', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          showAlert('登入成功，歡迎！', 'success');
          document.getElementById('login-form').reset();
          setTimeout(renderPage, 1000);
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        showAlert('網路錯誤，請稍後重試。', 'error');
      }
    }

    /**
     * 處理使用者登出
     */
    async function handleLogout() {
      if (!confirm('確認要登出嗎？')) return;

      try {
        const response = await fetch('api/auth.php?action=logout', {
          method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
          showAlert('已登出。', 'success');
          setTimeout(renderPage, 1000);
        }
      } catch (error) {
        showAlert('網路錯誤，請稍後重試。', 'error');
      }
    }

    /**
     * 檢查目前登入狀態
     */
    async function getCurrentUser() {
      try {
        const response = await fetch('api/auth.php?action=current');
        const data = await response.json();
        return data.success ? data.user : null;
      } catch (error) {
        console.error('檢查登入狀態失敗:', error);
        return null;
      }
    }

    // ========== 作品相關 ==========

    /**
     * 儲存或編輯作品
     */
    async function handleSaveMemo(event) {
      event.preventDefault();

      const memoId = document.getElementById('memo-id').value || null;
      const title = document.getElementById('memo-title').value.trim();
      const category = document.getElementById('memo-category').value;
      const content = document.getElementById('memo-content').value.trim();
      const inspiration = document.getElementById('memo-inspiration').value.trim();
      const imageFile = document.getElementById('memo-image').files[0];

      if (!title || !content) {
        showAlert('標題與內容為必填項目。', 'error');
        return;
      }

      const formData = new FormData();
      formData.append('id', memoId);
      formData.append('title', title);
      formData.append('category', category);
      formData.append('content', content);
      formData.append('inspiration', inspiration);
      if (imageFile) {
        formData.append('image', imageFile);
      }

      try {
        const response = await fetch('api/memo.php?action=save', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          showAlert(data.message, 'success');
          resetMemoForm();
          renderMemos();
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        showAlert('網路錯誤，請稍後重試。', 'error');
      }
    }

    /**
     * 編輯作品：載入資料到表單
     */
    function editMemo(id) {
      const allMemos = document.querySelectorAll('[data-memo-id]');
      let found = false;

      allMemos.forEach(card => {
        if (parseInt(card.dataset.memoId) === id) {
          const title = card.querySelector('.memo-title').textContent;
          const category = card.querySelector('.memo-category').textContent.replace('分類：', '').trim();
          const content = card.querySelector('.memo-content').textContent;
          const inspiration = card.querySelector('.memo-inspiration').textContent.replace('靈感：', '').trim();

          document.getElementById('memo-id').value = id;
          document.getElementById('memo-title').value = title;
          document.getElementById('memo-category').value = category;
          document.getElementById('memo-content').value = content;
          document.getElementById('memo-inspiration').value = inspiration;

          window.scrollTo({ top: 0, behavior: 'smooth' });
          found = true;
        }
      });
    }

    /**
     * 刪除作品
     */
    async function deleteMemo(id) {
      if (!confirm('確認要刪除此作品嗎？')) return;

      try {
        const response = await fetch('api/memo.php?action=delete', {
          method: 'DELETE',
          body: new URLSearchParams({ id })
        });

        const data = await response.json();

        if (data.success) {
          showAlert('作品已刪除。', 'success');
          renderMemos();
        } else {
          showAlert(data.message, 'error');
        }
      } catch (error) {
        showAlert('網路錯誤，請稍後重試。', 'error');
      }
    }

    /**
     * 重設作品表單
     */
    function resetMemoForm() {
      document.getElementById('memo-id').value = '';
      document.getElementById('memo-title').value = '';
      document.getElementById('memo-category').value = '風景';
      document.getElementById('memo-content').value = '';
      document.getElementById('memo-inspiration').value = '';
      document.getElementById('memo-image').value = '';
    }

    /**
     * 建立單張作品卡片
     */
    function createMemoCard(memo, allowActions = false) {
      const card = document.createElement('div');
      card.className = 'memo-card';
      card.dataset.memoId = memo.id;

      card.innerHTML = `
        ${memo.thumbnail_path ? `<img src="${memo.thumbnail_path}" alt="作品縮圖" />` : ''}
        <div class="memo-body">
          <div class="memo-meta">作者：${memo.nickname || '未知'} | 分類：<span class="memo-category">${memo.category}</span> | 上傳：${memo.created_at}</div>
          <h3 class="memo-title">${escapeHtml(memo.title)}</h3>
          <p class="memo-content">${escapeHtml(memo.content).replace(/\n/g, '<br/>')}</p>
          <p><strong>靈感：</strong><span class="memo-inspiration">${escapeHtml(memo.inspiration || '').replace(/\n/g, '<br/')}</span></p>
          <div class="memo-actions">
            ${allowActions ? `<button onclick="editMemo(${memo.id})">編輯</button><button class="secondary" onclick="deleteMemo(${memo.id})">刪除</button>` : ''}
          </div>
        </div>
      `;
      return card;
    }

    /**
     * 防止 XSS 攻擊
     */
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    /**
     * 渲染所有作品
     */
    async function renderMemos() {
      const user = await getCurrentUser();
      const ownMemoList = document.getElementById('own-memo-list');
      const publicMemoList = document.getElementById('public-memo-list');

      ownMemoList.innerHTML = '';
      publicMemoList.innerHTML = '';

      if (user) {
        // 載入自己的作品
        try {
          const response = await fetch('api/memo.php?action=own');
          const data = await response.json();

          if (data.success && data.memos.length > 0) {
            data.memos.forEach(memo => {
              ownMemoList.appendChild(createMemoCard(memo, true));
            });
          } else {
            ownMemoList.innerHTML = '<p>還沒有自己的作品。</p>';
          }
        } catch (error) {
          ownMemoList.innerHTML = '<p>無法載入作品。</p>';
        }
      } else {
        ownMemoList.innerHTML = '<p>登入後可查看自己的作品欄。</p>';
      }

      // 載入所有公開作品
      try {
        const response = await fetch('api/memo.php?action=list');
        const data = await response.json();

        if (data.success && data.memos.length > 0) {
          data.memos.forEach(memo => {
            publicMemoList.appendChild(createMemoCard(memo, false));
          });
        } else {
          publicMemoList.innerHTML = '<p>目前尚無其他人的作品。</p>';
        }
      } catch (error) {
        publicMemoList.innerHTML = '<p>無法載入作品。</p>';
      }
    }

    // ========== 紀錄相關 ==========

    /**
     * 渲染登入紀錄
     */
    async function renderLogs() {
      const body = document.getElementById('log-body');
      body.innerHTML = '';

      try {
        const response = await fetch('api/log.php?action=list');
        const data = await response.json();

        if (data.success && data.logs.length > 0) {
          data.logs.forEach(log => {
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${escapeHtml(log.account)}</td>
              <td>${log.occurred_at}</td>
              <td class="${log.success ? 'status-success' : 'status-fail'}">${log.success ? '成功' : '失敗'}</td>
              <td>${escapeHtml(log.event)}${log.notes ? ' - ' + escapeHtml(log.notes) : ''}</td>
            `;
            body.appendChild(row);
          });
        } else {
          body.innerHTML = '<tr><td colspan="4">無紀錄。</td></tr>';
        }
      } catch (error) {
        body.innerHTML = '<tr><td colspan="4">無法載入紀錄。</td></tr>';
      }
    }

    // ========== 頁面渲染 ==========

    /**
     * 依登入狀態渲染整個頁面
     */
    async function renderPage() {
      const user = await getCurrentUser();
      const authContent = document.getElementById('auth-content');
      const mainContent = document.getElementById('main-content');
      const userPanel = document.getElementById('user-panel');
      const registerSection = document.getElementById('register-section');
      const loginSection = document.getElementById('login-section');

      if (user) {
        // 已登入：顯示主內容
        authContent.classList.add('hidden');
        mainContent.classList.remove('hidden');
        userPanel.classList.remove('hidden');
        registerSection.classList.add('hidden');
        loginSection.classList.add('hidden');

        document.getElementById('user-display').textContent = user.nickname;
        document.getElementById('user-account').textContent = user.account;
        document.getElementById('user-gender').textContent = user.gender;
        document.getElementById('user-interests').textContent = user.interests;

        renderMemos();
        renderLogs();
      } else {
        // 未登入：顯示認證區
        authContent.classList.remove('hidden');
        mainContent.classList.add('hidden');
        userPanel.classList.add('hidden');
        registerSection.classList.remove('hidden');
        loginSection.classList.add('hidden');

        ownMemoList.innerHTML = '<p>登入後可查看自己的作品欄。</p>';
        document.getElementById('public-memo-list').innerHTML = '<p>目前尚無作品。</p>';
      }
    }

    // ========== 初始化 ==========

    /**
     * 頁面載入完成後初始化
     */
    document.addEventListener('DOMContentLoaded', () => {
      renderPage();
    });
  </script>
</body>
</html>
