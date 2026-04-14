[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/VOLNfwbe)
# 作業 5 資料庫基礎存取

## 繳交說明
1. 分組名稱請依照分組表上進行更名，甲班為 A01, A02, .. A12 乙班則為 B01, B02, .. B11。更名若有問題，請找老師協助！
2. 分組作業，不開 PR, 所以也不開branch。
3. 繳交期限  4/20
4. 繳交後可以找時間整組找老師進行demo，請組員理解你們的程式碼，老師會個別問問題。
   
## 作業說明
自行設計題目，但須具備下列功能: 
1. 三個資料表，一個用來存放註冊資料，一個用來存放 log 資料，一個用來存放圖文備忘資料。
   資料表命名分別為 dbusers, dblog 及 dememo 。完成後請將資料表匯出，填入到資料夾中。
2. 具備註冊功能，註冊資料包含
   a. 帳號
   b. 暱稱
   c. 密碼
   d. 性別
   e. 興趣
   ...
3. 具備登入功能，需註冊後才能登入
4. 任何人登入時，紀錄登入者帳號，日期時間以及是否登入成功
5. 登入後可以新增圖文備忘，至少包含
   a. 新增者(使用者id)
   b. 多行文字
   c. 上傳一張圖片，進行縮圖後存放
   d. ...
6. 圖文備忘功能具備 新增、刪除、修改、列出
7. 登入資料可以被瀏覽

## 自行設計的內容說明(同學自填)
主題:攝影作品分享
1.可以上傳圖片檔,並說明圖片內容以及靈感來源
2.上傳的作品可供大家欣賞
3.上傳的作品要分類(風景、人物、動物、植物、其他)
4.首頁設計:註冊、登入、登出、上傳作品、自己的作品欄、作品交流區

## 實作架構說明

### 技術堆疊
- **後端**: PHP 7.4+ 搭配 MySQLi 逐句查詢
- **資料庫**: MySQL 5.7+（Laragon 預設環境）
- **前端**: HTML5 + CSS3 + Vanilla JavaScript（無框架依賴）
- **存儲**: 圖片以檔案系統存放，使用 GD Library 產生縮圖

### 檔案結構
```
db-a03/
├── config.php              # 資料庫連線設定
├── index.php               # 主頁面（前端 HTML/CSS/JS）
├── api/
│   ├── auth.php            # 認證 API（登入、註冊、登出、狀態檢查）
│   ├── memo.php            # 作品管理 API（新增、編輯、刪除、查詢）
│   └── log.php             # 紀錄查詢 API
├── uploads/                # 圖片存放目錄（自動建立）
├── dbusers.sql             # 使用者表結構
├── dbmemo.sql              # 作品表結構
├── dblog.sql               # 紀錄表結構
└── README.md               # 本檔案
```

### API 端點說明

#### 認證相關 (`api/auth.php`)
- **POST** `?action=register` - 使用者註冊
- **POST** `?action=login` - 使用者登入
- **POST** `?action=logout` - 使用者登出
- **GET** `?action=current` - 檢查目前登入使用者資訊

#### 作品相關 (`api/memo.php`)
- **POST** `?action=save` - 新增或編輯作品（支援圖片上傳）
- **DELETE** `?action=delete` - 刪除作品
- **GET** `?action=own` - 查詢使用者自己的作品
- **GET** `?action=list` - 查詢所有公開作品

#### 紀錄相關 (`api/log.php`)
- **GET** `?action=list` - 查詢登入紀錄（最新 100 筆）

### 安全特性
- 密碼使用 bcrypt 加密儲存（PHP 內建 `password_hash()`）
- SQL 注入防護：所有查詢使用準備陳述式 (Prepared Statement)
- XSS 防護：前端使用 `escapeHtml()` 對輸出進行編碼
- 作品刪除檢查：驗證使用者是否為作者
- 檔案上傳驗證：限制副檔名為 JPG、PNG、GIF
- Session 機制：使用 PHP 內建 session 管理登入狀態

### 快速開始

1. **建立資料庫與表**
   ```sql
   -- 在 MySQL 中執行
   CREATE DATABASE db_a03;
   USE db_a03;
   
   -- 匯入三個 SQL 檔案
   SOURCE dbusers.sql;
   SOURCE dbmemo.sql;
   SOURCE dblog.sql;
   ```

2. **檢查資料庫連線設定** (`config.php`)
   - `DB_HOST`: localhost（Laragon 預設）
   - `DB_USER`: root（Laragon 預設）
   - `DB_PASS`: 留空（Laragon 預設無密碼）
   - `DB_NAME`: db_a03（須與上面建立的資料庫同名）

3. **在瀏覽器中開啟**
   ```
   http://localhost/db-a03/index.php
   ```

4. **目錄權限**
   - `uploads/` 目錄須可寫入（Laragon 通常預設有寫入權限）

### 核心功能流程

#### 使用者生命週期
1. 新使用者在「註冊」區填表並提交
2. 系統驗證帳號唯一性、必填欄位
3. 密碼經 bcrypt 加密後存入 `dbusers`
4. 在 `dblog` 記錄「註冊」事件（成功）
5. 使用者切到「登入」區並輸入帳密
6. 系統驗證帳密、建立 PHP session
7. 在 `dblog` 記錄「登入」事件（成功/失敗）
8. 登入成功後顯示使用者面板與作品編輯區

#### 作品管理流程
1. 登入使用者在「上傳作品」區填入標題、分類、內容、靈感
2. 若選擇圖片，先上傳到 `uploads/` 目錄
3. 使用 GD Library 產生縮圖（保持原圖比例）
4. 原圖與縮圖路徑存入 `dbmemo`
5. 自己的作品出現在「自己的作品欄」
6. 其他使用者的作品出現在「作品交流區」（只讀）
7. 使用者可編輯或刪除自己的作品

### 技術筆記
- 圖片以 `uploads/memo_{user_id}_{timestamp}.{ext}` 命名，確保唯一性
- 縮圖統一產生為 JPEG 格式，品質設為 80%
- 編輯作品時，若不重新上傳圖片，原圖資料保留
- 刪除作品時同時刪除磁碟上的原圖與縮圖檔案
- 所有日期時間使用 MySQL 的 `CURRENT_TIMESTAMP`
- 登入紀錄限制最多 100 筆（防止表無限成長）