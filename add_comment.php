<?php
// --- ৮ম ধাপ (পার্ট-১): কমেন্ট সেভিং ব্যাকএন্ড ইঞ্জিন (add_comment.php) ---

// ডাটাবেজ কানেকশন যুক্ত করা
require_once 'config.php';

// ফর্ম থেকে ডেটা POST মেথডে পাঠানো হয়েছে কিনা তা চেক করা
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $video_id = trim($_POST['video_id']);
    $comment_text = trim($_POST['comment_text']);

    // ইনপুট খালি কিনা তা যাচাই করা
    if (!empty($video_id) && !empty($comment_text)) {
        try {
            // সিকিউর প্রিপেয়ার্ড স্টেটমেন্ট তৈরি (SQL Injection প্রতিরোধের জন্য)
            $stmt = $pdo->prepare("INSERT INTO video_comments (video_id, comment_text) VALUES (?, ?)");
            $stmt->execute([$video_id, $comment_text]);
            
            // কমেন্ট সফলভাবে সেভ হওয়ার পর আবার মেইন পেজে ফেরত পাঠানো
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            die("❌ কমেন্ট পোস্ট করতে সমস্যা হয়েছে: " . $e->getMessage());
        }
    }
}

// যদি কেউ ভুলবশত বা সরাসরি এই ফাইলে ঢোকার চেষ্টা করে তবে মেইন পেজে পাঠিয়ে দেবে
header("Location: index.php");
exit();
?>
