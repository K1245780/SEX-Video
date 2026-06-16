<?php
// --- ৭ম ধাপ: কোর ইউজার ইন্টারফেস ও ডাইনামিক ভিডিও ফিড ইঞ্জিন (index.php) ---

// ডাটাবেজ কানেকশন ফাইলটি যুক্ত করা
require_once 'config.php';

// ডাটাবেজ থেকে সব ভিডিও তুলে আনা (নতুন ভিডিওগুলো সবার উপরে থাকবে)
try {
    $stmt = $pdo->query("SELECT * FROM video_feed ORDER BY id DESC");
    $videos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("❌ ভিডিও ফিড লোড করতে সমস্যা হয়েছে: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamHub - আসল ভিডিও সোশ্যাল প্ল্যাটফর্ম</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #f0f2f5;
            --bg-secondary: #ffffff;
            --text-primary: #1c1e21;
            --text-secondary: #65676b;
            --accent-color: #1877f2;
            --border-color: #e4e6eb;
        }

        [data-theme="dark"] {
            --bg-primary: #18191a;
            --bg-secondary: #242526;
            --text-primary: #e4e6eb;
            --text-secondary: #b0b3b8;
            --border-color: #3e4042;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: var(--bg-primary); color: var(--text-primary); transition: all 0.3s ease; }
        
        /* ন্যাপবার */
        .navbar { background-color: var(--bg-secondary); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .logo { font-size: 22px; font-weight: bold; color: var(--accent-color); display: flex; align-items: center; gap: 10px; }
        .search-container { flex: 0 1 500px; position: relative; }
        .search-input { width: 100%; padding: 10px 20px 10px 40px; border-radius: 50px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-size: 16px; outline: none; }
        .search-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); }
        .nav-actions { display: flex; gap: 15px; align-items: center; }
        .icon-btn { background: none; border: none; color: var(--text-primary); font-size: 20px; cursor: pointer; width: 40px; height: 40px; border-radius: 50px; display: flex; align-items: center; justify-content: center; background-color: var(--bg-primary); }

        /* লেআউট ও কার্ড */
        .main-layout { max-width: 650px; margin: 20px auto; padding: 0 10px; padding-bottom: 50px; }
        .feed-card { background-color: var(--bg-secondary); border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 25px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .card-header { padding: 15px; display: flex; align-items: center; gap: 10px; }
        .admin-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--accent-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .meta-info h4 { font-size: 15px; font-weight: 600; }
        .meta-info span { font-size: 12px; color: var(--text-secondary); }
        .card-caption { padding: 0 15px 12px 15px; font-size: 16px; line-height: 1.5; word-wrap: break-word; }
        
        /* ভিডিও কন্টেইনার */
        .video-container { background: #000; width: 100%; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; }
        video { width: 100%; height: 100%; object-fit: contain; }
        
        /* কাউন্টার ও অ্যাকশনস */
        .stats-counter { padding: 10px 15px; display: flex; justify-content: space-between; font-size: 13px; color: var(--text-secondary); border-bottom: 1px solid var(--border-color); }
        .interaction-bar { display: flex; padding: 5px 0; border-bottom: 1px solid var(--border-color); }
        .action-button { flex: 1; background: none; border: none; padding: 10px; color: var(--text-secondary); font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; border-radius: 4px; }
        .action-button:hover { background-color: var(--bg-primary); }

        /* কমেন্ট সেকশন */
        .comment-box-wrapper { padding: 15px; background-color: var(--bg-secondary); }
        .comment-form { display: flex; gap: 10px; margin-bottom: 15px; }
        .user-input-field { flex: 1; padding: 10px 15px; border-radius: 20px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); outline: none; font-size: 14px; }
        .submit-comment-btn { background-color: var(--accent-color); color: white; border: none; padding: 0 15px; border-radius: 20px; cursor: pointer; font-weight: bold; }
        .comments-display-list { max-height: 180px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .single-comment { display: flex; gap: 10px; align-items: flex-start; }
        .comment-avatar { width: 32px; height: 32px; border-radius: 50%; background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #fff; }
        .comment-text-bg { background-color: var(--bg-primary); padding: 8px 15px; border-radius: 18px; max-width: 85%; }
        .comment-text-bg h5 { font-size: 13px; margin-bottom: 2px; font-weight: 600; }
        .comment-text-bg p { font-size: 14px; word-break: break-word; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo"><i class="fa-solid fa-circle-play"></i> StreamHub</div>
        <div class="search-container">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="masterSearch" class="search-input" placeholder="ক্যাপশন দিয়ে ভিডিও খুঁজুন...">
        </div>
        <div class="nav-actions">
            <button class="icon-btn" id="themeToggle"><i class="fa-solid fa-moon"></i></button>
            <a href="admin.php" class="icon-btn" title="অ্যাডমিন প্যানেল"><i class="fa-solid fa-user-gear"></i></a>
        </div>
    </nav>

    <main class="main-layout" id="videoFeedContainer">
        
        <?php if (empty($videos)): ?>
            <div style="text-align:center; padding: 50px 20px; color: var(--text-secondary);">
                <i class="fa-solid fa-photo-film" style="font-size: 45px; margin-bottom: 15px;"></i>
                <h3 style="font-size: 18px; font-weight: 600;">ফিড একদম খালি!</h3>
                <p style="font-size: 14px; margin-top: 5px;">অ্যাডমিন প্যানেল থেকে লিংক বা মেমোরি ফাইল আপলোড করলে তা এখানে লাইভ দেখা যাবে।</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($videos as $video): 
                // এই নির্দিষ্ট ভিডিওর জন্য ডাটাবেজ থেকে আসল কমেন্টগুলো তুলে আনা
                $stmtComm = $pdo->prepare("SELECT * FROM video_comments WHERE video_id = ? ORDER BY comment_id DESC");
                $stmtComm->execute([$video['video_id']]);
                $comments = $stmtComm->fetchAll();
            ?>
                <div class="feed-card" data-caption="<?php echo htmlspecialchars(strtolower($video['caption'])); ?>">
                    <div class="card-header">
                        <div class="admin-avatar">A</div>
                        <div class="meta-info">
                            <h4>অ্যাডমিন প্রোফাইল</h4>
                            <span><?php echo date('d M Y, h:i A', strtotime($video['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="card-caption"><?php echo htmlspecialchars($video['caption']); ?></div>
                    
                    <div class="video-container">
                        <video controls preload="metadata" playsinline id="player-<?php echo $video['video_id']; ?>">
                            <source src="<?php echo htmlspecialchars($video['video_source']); ?>" type="video/mp4">
                            আপনার ব্রাউজারটি ভিডিও সমর্থন করে না।
                        </video>
                    </div>

                    <div class="stats-counter">
                        <span><i class="fa-solid fa-thumbs-up" style="color: var(--accent-color);"></i> <span id="like-count-<?php echo $video['video_id']; ?>"><?php echo $video['likes_count']; ?></span> লাইক</span>
                        <span><?php echo count($comments); ?>টি মন্তব্য</span>
                    </div>

                    <div class="interaction-bar">
                        <button class="action-button" onclick="likeVideo('<?php echo $video['video_id']; ?>')">
                            <i class="fa-regular fa-thumbs-up"></i> লাইক
                        </button>
                        <button class="action-button" onclick="document.getElementById('input-<?php echo $video['video_id']; ?>').focus()">
                            <i class="fa-regular fa-comment"></i> মন্তব্য করুন
                        </button>
                    </div>

                    <div class="comment-box-wrapper">
                        <form action="add_comment.php" method="POST" class="comment-form">
                            <input type="hidden" name="video_id" value="<?php echo $video['video_id']; ?>">
                            <input type="text" name="comment_text" id="input-<?php echo $video['video_id']; ?>" class="user-input-field" placeholder="একটি মন্তব্য লিখুন..." required>
                            <button type="submit" class="submit-comment-btn">পোস্ট</button>
                        </form>
                        
                        <div class="comments-display-list">
                            <?php if(empty($comments)): ?>
                                <span style="color:var(--text-secondary); font-size:12px; padding:5px; display:block;">প্রথম মন্তব্যটি আপনার হোক!</span>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="single-comment">
                                        <div class="comment-avatar"><i class="fa-solid fa-user"></i></div>
                                        <div class="comment-text-bg">
                                            <h5>ভিজিটর</h5>
                                            <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <script>
        // ১. রিয়েল-টাইম ক্লায়েন্ট সাইড সার্চ ফিল্টার
        const masterSearch = document.getElementById('masterSearch');
        masterSearch.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.feed-card');
            cards.forEach(card => {
                const caption = card.getAttribute('data-caption');
                if(caption.includes(query)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // ২. ডার্ক/লাইট মোড মেমোরি থিম চেঞ্জার
        const themeToggle = document.getElementById('themeToggle');
        if(localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
        }
        themeToggle.addEventListener('click', () => {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                themeToggle.innerHTML = '<i class="fa-solid fa-moon"></i>';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                themeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
            }
        });

        // ৩. পেজ রিফ্রেশ ছাড়া লাইক কাউন্ট বাড়ানোর AJAX ইঞ্জিন
        function likeVideo(videoId) {
            fetch('like_video.php?video_id=' + videoId)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    document.getElementById('like-count-' + videoId).innerText = data.new_likes;
                }
            })
            .catch(err => console.error('Error tracking like:', err));
        }
    </script>
</body>
</html>
