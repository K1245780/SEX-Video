<?php
// --- ৬ষ্ঠ ধাপ: PHP ও MySQL ডাটাবেজ কানেকশন ইঞ্জিন (config.php) ---

// ডাটাবেজের কনফিগারেশন তথ্য (আপনার হোস্টিং সার্ভার অনুযায়ী পরিবর্তন করবেন)
$db_host = "localhost";         // সাধারণত localhost-ই থাকে
$db_name = "your_database_name"; // আপনার তৈরি করা ডাটাবেজের নাম
$db_user = "your_database_user"; // ডাটাবেজের ইউজারনেম
$db_pass = "your_database_password"; // ডাটাবেজের পাসওয়ার্ড

try {
    // PDO কানেকশন তৈরি এবং সিকিউরিটি সেটিংস সেট করা
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    
    // এরর হ্যান্ডেলিং মোড অন করা (কোনো সমস্যা হলে স্ক্রিনে স্পষ্ট এরর দেখাবে)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ডাটাবেজ থেকে ডেটা তুলে আনার সময় যেন ডিফল্ট অ্যাসোসিয়েティブ অ্যারে হিসেবে আসে
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // ইমুলেট প্রিপেয়ার্ড স্টেটমেন্ট বন্ধ করা (হ্যাকিং প্রতিরোধের জন্য অত্যন্ত জরুরি)
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // কানেকশন ব্যর্থ হলে সাইট বন্ধ করে স্পষ্ট এরর মেসেজ দেখাবে
    die("❌ ডাটাবেজ কানেকশন ব্যর্থ হয়েছে: " . $e->getMessage());
}
?>
