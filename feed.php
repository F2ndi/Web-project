<?php
session_start();

// Database connection
$host = 'localhost';
$db = 'registerdb';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>You must be logged in to post, comment, or like.</p>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle file upload for posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['content'])) {
    $content_type = $_POST['content_type']; // 'photo' or 'video'
    $allowed_types = ($content_type === 'photo') ? ['image/jpeg', 'image/png'] : ['video/mp4'];

    if (in_array($_FILES['content']['type'], $allowed_types)) {
        $target_dir = ($content_type === 'photo') ? 'uploads/photos/' : 'uploads/videos/';
        $target_file = $target_dir . basename($_FILES['content']['name']);

        if (move_uploaded_file($_FILES['content']['tmp_name'], $target_file)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO posts (user_id, content_type, content_path, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iss", $user_id, $content_type, $target_file);
            $stmt->execute();
            echo "<p>Post uploaded successfully!</p>";
        } else {
            echo "<p>Error uploading file.</p>";
        }
    } else {
        echo "<p>Invalid file type.</p>";
    }
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $post_id = $_POST['post_id'];
    $comment = htmlspecialchars($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    $stmt->execute();
    echo "<p>Comment added!</p>";
}

// Handle likes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like'])) {
    $post_id = $_POST['post_id'];

    $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    echo "<p>Post liked!</p>";
}

// Fetch and display posts
$stmt = $conn->prepare("SELECT posts.id, posts.content_type, posts.content_path, posts.created_at, users.username FROM posts JOIN users ON posts.user_id = users.id ORDER BY posts.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

while ($post = $result->fetch_assoc()) {
    echo "<div class='post'>";
    echo "<p>Posted by: " . htmlspecialchars($post['username']) . " at " . $post['created_at'] . "</p>";
    if ($post['content_type'] === 'photo') {
        echo "<img src='" . htmlspecialchars($post['content_path']) . "' alt='Photo' style='max-width:100%;'>";
    } else {
        echo "<video controls style='max-width:100%;'><source src='" . htmlspecialchars($post['content_path']) . "' type='video/mp4'></video>";
    }

    // Like button
    echo "<form method='POST'>
            <input type='hidden' name='post_id' value='" . $post['id'] . "'>
            <button type='submit' name='like'>Like</button>
          </form>";

    // Comment form
    echo "<form method='POST'>
            <input type='hidden' name='post_id' value='" . $post['id'] . "'>
            <textarea name='comment' placeholder='Add a comment'></textarea>
            <button type='submit'>Comment</button>
          </form>";

    // Display comments
    $comment_stmt = $conn->prepare("SELECT comments.comment, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at ASC");
    $comment_stmt->bind_param("i", $post['id']);
    $comment_stmt->execute();
    $comment_result = $comment_stmt->get_result();
    while ($comment = $comment_result->fetch_assoc()) {
        echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . htmlspecialchars($comment['comment']) . "</p>";
    }

    echo "</div><hr>";
}

$conn->close();
?>
