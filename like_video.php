<?php
// --- ৮ম ধাপ (পার্ট-২): রিয়েল-টাইম লাইক প্রসেসর (like_video.php) ---

// আউটপুট ফরম্যাট JSON হিসেবে সেট করা
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['video_id'])) {
    $video_id = trim($_GET['video_id']);

    if (!empty($video_id)) {
        try {
            // ১. ডাটাবেজে ওই নির্দিষ্ট ভিডিওর বর্তমান লাইক সংখ্যা ঠিক ১ বাড়িয়ে দেওয়া
            $stmtUpdate = $pdo->prepare("UPDATE video_feed SET likes_count = likes_count + 1 WHERE video_id = ?");
            $stmtUpdate->execute([$video_id]);

            // ২. আপডেট হওয়ার পর নতুন আসল লাইক সংখ্যাটি ডাটাবেজ থেকে তুলে আনা
            $stmtGet = $pdo->prepare("SELECT likes_count FROM video_feed WHERE video_id = ?");
            $stmtGet->execute([$video_id]);
            $result = $stmtGet->fetch();

            if ($result) {
                // জাভাস্ক্রিপ্টের কাছে নতুন লাইক কাউন্টটি JSON ফরম্যাটে পাঠানো
                echo json_encode([
                    'success' => true,
                    'new_likes' => (int)$result['likes_count'] // আসল কাউন্ট পাঠানো হচ্ছে
                ]);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }
}

// ইনভ্যালিড রিকোয়েস্ট হলে এরর মেসেজ পাঠানো
echo json_encode(['success' => false, 'error' => 'Invalid Request']);
exit();
?>
