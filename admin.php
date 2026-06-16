<?php
// --- ৯ম ধাপ: ডুয়াল আপলোড পিএইচপি অ্যাডমিন প্যানেল ইঞ্জিন (admin.php) ---

// সেশন চালু করা
session_start();

// ডাটাবেজ কানেকশন যুক্ত করা
require_once 'config.php';

// গোপন মাস্টার পাসওয়ার্ড
$MASTER_PASSWORD = "kalyan1357"; 

// ১. লগইন প্রসেস হ্যান্ডেল করা
if (isset($_POST['login'])) {
    $password_input = trim($_POST['password']);
    if ($password_input === $MASTER_PASSWORD) {
        $_SESSION['is_admin_logged_in'] = true;
    } else {
        $login_error = "❌ ভুল পাসওয়ার্ড! আবার চেষ্টা করুন।";
    }
}

// ২. লগআউট প্রসেс
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// ৩. নতুন ভিডিও পাবলিশ করার স্মার্ট ডুয়াল পিএইচপি ইঞ্জিন
if (isset($_POST['publish_video']) && isset($_SESSION['is_admin_logged_in'])) {
    $video_url = trim($_POST['video_url']);
    $caption = trim($_POST['caption']);
    
    $video_source = "";
    $upload_type = "";
    $is_valid = true;

    // ক) ইউজার যদি সরাসরি মেমোরি/স্টোরেজ থেকে ফাইল আপলোড করেন
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
        
        // আপলোড করা ফাইল রাখার জন্য 'uploads' নামে একটি ফোল্ডার তৈরি করা (যদি না থাকে)
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = time() . '_' . basename($_FILES['video_file']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // শুধুমাত্র MP4 ফাইল অ্যালাউ করা
        if ($file_type === 'mp4') {
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_file)) {
                $video_source = $target_file; // লোকাল স্টোরেজ পাথ
                $upload_type = "file";
            } else {
                $upload_error = "❌ সার্ভারে ফাইল আপলোড করতে সমস্যা হয়েছে।";
                $is_valid = false;
            }
        } else {
            $upload_error = "⚠️ দুঃখিত, শুধুমাত্র .mp4 ফরম্যাটের ভিডিও আপলোড করা সম্ভব।";
            $is_valid = false;
        }

    // খ) ফাইল আপলোড না করে যদি যেকোনো সাইটের ভিডিও লিঙ্ক বা ইউআরএল দেওয়া হয়
    } elseif (!empty($video_url)) {
        $video_source = $video_url;
        $upload_type = "url";
    } else {
        $upload_error = "⚠️ যেকোনো একটি অপশন ব্যবহার করুন! হয় লিঙ্ক দিন, নয়তো ফাইল আপলোড করুন।";
        $is_valid = false;
    }

    // গ) সবকিছু ঠিক থাকলে ডাটাবেজে ডাটা পাঠানো
    if ($is_valid && !empty($caption) && !empty($video_source)) {
        try {
            $video_id = 'stream_' . date("YmdHis") . '_' . uniqid();

            // প্রিপেয়ার্ড স্টেটমেন্ট কুয়েরি
            $stmt = $pdo->prepare("INSERT INTO video_feed (video_id, video_source, upload_type, caption, likes_count) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$video_id, $video_source, $upload_type, $caption]);

            $upload_success = "🎉 ভিডিওটি সফলভাবে ডাটাবেজে সংরক্ষিত এবং লাইভ হয়েছে!";
        } catch (PDOException $e) {
            $upload_error = "❌ ডাটাবেজ এরর: " . $e->getMessage();
        }
    } elseif (empty($caption) && $is_valid) {
        $upload_error = "⚠️ ভিডিওর সাথে একটি ক্যাপশন দেওয়া বাধ্যতামূলক!";
    }
}

// ৪. ডাটাবেজ ও স্টোরেজ থেকে ভিডিও ডিলিট করার প্রসেস
if (isset($_GET['delete_id']) && isset($_SESSION['is_admin_logged_in'])) {
    $delete_id = trim($_GET['delete_id']);
    try {
        // ডিলিট করার আগে চেক করা ফাইলটি কি লোকাল মেমোরির নাকি লিঙ্ক
        $stmtCheck = $pdo->prepare("SELECT video_source, upload_type FROM video_feed WHERE video_id = ?");
        $stmtCheck->execute([$delete_id]);
        $video_info = $stmtCheck->fetch();

        if ($video_info) {
            // যদি এটি সরাসরি মেমোরি থেকে আপলোড করা ফাইল হয়, তবে ফাইলটিকেও সার্ভার স্টোরেজ থেকে ডিলিট (unlink) করে দেবে
            if ($video_info['upload_type'] === 'file' && file_exists($video_info['video_source'])) {
                unlink($video_info['video_source']);
            }

            // ডাটাবেজ থেকে মুছে ফেলা
            $stmtDel = $pdo->prepare("DELETE FROM video_feed WHERE video_id = ?");
            $stmtDel->execute([$delete_id]);
        }
        
        header("Location: admin.php");
        exit();
    } catch (PDOException $e) {
        $upload_error = "❌ ডিলিট করতে সমস্যা হয়েছে: " . $e->getMessage();
    }
}

// ৫. ড্যাশবোর্ডের জন্য ভিডিওর তালিকা নিয়ে আসা
$all_videos = [];
if (isset($_SESSION['is_admin_logged_in'])) {
    $stmtList = $pdo->query("SELECT video_id, caption, upload_type FROM video_feed ORDER BY id DESC");
    $all_videos = $stmtList->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ক্রিয়েটর স্টুডিও - স্মার্ট পিএইচপি কন্ট্রোল সেন্টার</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #f4f6f9;
            --bg-card: #ffffff;
            --text-main: #333333;
            --text-sub: #6c757d;
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --border: #e3e6f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .admin-container { width: 100%; max-width: 580px; }
        .admin-card { background-color: var(--bg-card); border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border: 1px solid var(--border); padding: 30px; margin-bottom: 25px; }
        .card-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 18px; position: relative; }
        .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: var(--text-sub); }
        .form-control { width: 100%; padding: 12px 15px 12px 40px; border-radius: 8px; border: 1px solid var(--border); font-size: 15px; outline: none; background-color: #fafafa; }
        .form-control:focus { border-color: var(--primary); background-color: #fff; }
        .input-icon { position: absolute; left: 15px; top: 38px; color: var(--text-sub); }
        textarea.form-control { padding-left: 15px; resize: vertical; }
        
        /* কাস্টম ফাইল আপলোডার স্টাইল */
        .file-box { border: 2px dashed var(--border); padding: 20px; text-align: center; border-radius: 8px; background: #fafafa; cursor: pointer; }
        .file-box:hover { border-color: var(--primary); background: #f1f7ff; }

        .divider-or { display: flex; align-items: center; text-align: center; margin: 20px 0; color: var(--text-sub); font-weight: bold; font-size: 14px; }
        .divider-or::before, .divider-or::after { content: ''; flex: 1; border-bottom: 1px solid var(--border); }
        .divider-or:not(:empty)::before { margin-right: .5em; }
        .divider-or:not(:empty)::after { margin-left: .5em; }

        .admin-btn { width: 100%; padding: 12px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-success { background-color: var(--success); color: white; }
        .btn-danger { background-color: var(--danger); color: white; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 14px; font-weight: 500; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
        
        .video-manager-list { max-height: 200px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; background: #fafafa; margin-bottom: 20px; }
        .manager-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border-bottom: 1px solid var(--border); background-color: var(--bg-card); }
        .manager-item p { font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 60%; }
        .badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; font-weight: bold; color: white; }
        .badge-file { background-color: var(--success); }
        .badge-url { background-color: var(--primary); }
        .small-del-btn { background-color: var(--danger); color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: var(--primary); text-decoration: none; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

<div class="admin-container">

    <?php if (!isset($_SESSION['is_admin_logged_in'])): ?>
        <div class="admin-card">
            <div class="card-title"><i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> অ্যাডমিন ভেরিফিকেশন</div>
            <?php if(isset($login_error)) echo "<div class='alert alert-danger'>$login_error</div>"; ?>
            <form action="admin.php" method="POST">
                <div class="form-group">
                    <label>মাস্টার পাসওয়ার্ড দিন</label>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" class="form-control" placeholder="গোপন কোডটি লিখুন..." required>
                </div>
                <button type="submit" name="login" class="admin-btn btn-primary">লগইন করুন</button>
            </form>
            <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> ইউজার ফিডে ফিরে যান</a>
        </div>

    <?php else: ?>
        <div class="admin-card">
            <div class="card-title"><i class="fa-solid fa-cloud-arrow-up" style="color: var(--success);"></i> নতুন ভিডিও আপলোড / লিঙ্ক করুন</div>
            
            <?php 
                if(isset($upload_error)) echo "<div class='alert alert-danger'>$upload_error</div>";
                if(isset($upload_success)) echo "<div class='alert alert-success'>$upload_success</div>";
            ?>

            <form action="admin.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>১. যেকোনো ভিডিও ইউআরএল (URL Link)</label>
                    <i class="fa-solid fa-globe input-icon"></i>
                    <input type="url" name="video_url" id="v_url" class="form-control" placeholder="যেকোনো ওয়েবসাইটের ভিডিওর লিঙ্ক দিন...">
                </div>

                <div class="divider-or">অথবা সরাসরি ফাইল পছন্দ করুন</div>

                <div class="form-group">
                    <label>২. মেমোরি/স্টোরেজ থেকে ভিডিও ফাইল আপলোড করুন</label>
                    <div class="file-box" onclick="document.getElementById('v_file').click()">
                        <i class="fa-solid fa-file-video" style="font-size:30px; color:var(--text-sub); margin-bottom:5px;"></i>
                        <p id="file-name-text">এখানে ক্লিক করে .mp4 ভিডিও সিলেক্ট করুন</p>
                        <input type="file" name="video_file" id="v_file" accept="video/mp4" style="display:none;">
                    </div>
                </div>

                <hr style="border:0; border-top:1px solid var(--border); margin:20px 0;">

                <div class="form-group">
                    <label>ভিডিওর ক্যাপশন</label>
                    <textarea name="caption" class="form-control" rows="3" placeholder="ক্যাপশন বা হ্যাশট্যাগ লিখুন..." required></textarea>
                </div>
                
                <button type="submit" name="publish_video" class="admin-btn btn-success">পাবলিশ করুন</button>
            </form>
        </div>

        <div class="admin-card">
            <div class="card-title"><i class="fa-solid fa-list-check" style="color: var(--text-sub);"></i> আপলোড করা ভিডিওর তালিকা (<?php echo count($all_videos); ?>)</div>
            
            <div class="video-manager-list">
                <?php if(empty($all_videos)): ?>
                    <p style="color:var(--text-sub); text-align:center; padding: 20px; font-size:14px;">কোনো ভিডিও আপলোড করা নেই।</p>
                <?php else: ?>
                    <?php foreach($all_videos as $vid): ?>
                        <div class="manager-item">
                            <p><?php echo htmlspecialchars($vid['caption']); ?></p>
                            <div>
                                <span class="badge <?php echo ($vid['upload_type'] === 'file') ? 'badge-file' : 'badge-url'; ?>">
                                    <?php echo ($vid['upload_type'] === 'file') ? 'ফাইল' : 'লিঙ্ক'; ?>
                                </span>
                                <a href="admin.php?delete_id=<?php echo $vid['video_id']; ?>" class="small-del-btn" onclick="return confirm('আপনি কি নিশ্চিত যে ভিডিওটি চিরতরে মুছে ফেলবেন? (ফাইল হলে স্টোরেজ থেকেও ডিলিট হয়ে যাবে)')"><i class="fa-solid fa-trash"></i> ডিলিট</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <a href="admin.php?action=logout" class="admin-btn btn-danger" style="text-decoration:none;"><i class="fa-solid fa-power-off"></i> ড্যাশবোর্ড লক (Logout) করুন</a>
            <a href="index.php" class="back-link" target="_blank"><i class="fa-solid fa-eye"></i> লাইভ ওয়েবসাইট দেখুন</a>
        </div>
    <?php endif; ?>

</div>

<script>
    // ফাইল সিলেক্ট করলে সুন্দরভাবে ফাইলের নাম স্ক্রিনে দেখানোর ট্র্যাকার
    const fileInput = document.getElementById('v_file');
    if(fileInput) {
        fileInput.addEventListener('change', function() {
            if(this.files && this.files.length > 0) {
                document.getElementById('file-name-text').innerHTML = "🟢 সিলেক্টেড ফাইল: <strong>" + this.files[0].name + "</strong>";
            }
        });
    }
</script>
</body>
</html>
