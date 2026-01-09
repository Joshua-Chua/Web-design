<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['officer', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id || $quiz_id <= 0) {
    header("Location: officer_quiz.php");
    exit();
}
$role = $_SESSION['role'];
$profile_link = ($role == 'admin') ? '../admin/admin_profile.php' : 'officer_profile.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $question_ids = $_POST['question_id'] ?? [];
    $questions = $_POST['question'] ?? [];
    $correct_answers = $_POST['correct_answer'] ?? [];
    $options_list = $_POST['options'] ?? [];

    $deletedIds = [];
    if (!empty($_POST['deleted_questions'])) {
        $deletedIds = array_map('intval', explode(',', $_POST['deleted_questions']));
    }

    // Delete questions first
    if (!empty($deletedIds)) {
        // Delete all at once
        $in = implode(',', array_fill(0, count($deletedIds), '?'));
        $types = str_repeat('i', count($deletedIds));
        
        $stmt = $conn->prepare("DELETE FROM question WHERE question_id IN ($in) AND quiz_id = ?");
        $params = array_merge($deletedIds, [$quiz_id]);
        $stmt->bind_param($types . "i", ...$params);
        $stmt->execute();
        $stmt->close();
    }

    // Process remaining form data
    // Get all remaining questions in the database
    $remainingQuestions = [];
    $stmt = $conn->prepare("SELECT question_id, question_number FROM question WHERE quiz_id = ? ORDER BY question_number ASC");
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $remainingQuestions[$row['question_id']] = $row['question_number'];
    }
    $stmt->close();

    // Update existing questions
    foreach ($questions as $i => $q_text) {
        $qid = $question_ids[$i] ?? null;
        
        // Skip if no question ID OR if it was deleted
        if (!$qid || in_array($qid, $deletedIds)) {
            continue;
        }
        
        // Existing question
        $answer = $correct_answers[$i] ?? '';
        $optA = ($options_list[$i][0] ?? '');
        $optB = ($options_list[$i][1] ?? '');
        $optC = ($options_list[$i][2] ?? null);
        $optD = ($options_list[$i][3] ?? null);
    
        if ($optA === '' || $optB === '' || $q_text === '') {
            continue;
        }
    
        // Save image to file
        $image_name = null;
    
        if (!empty($_FILES['question_image']['name'][$i])) {
            $dir = "../../uploads/questions/";
        
            if (!is_dir($dir)) mkdir($dir, 0777, true);
        
            $tmp = $_FILES['question_image']['tmp_name'][$i];
            $name = $_FILES['question_image']['name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $image_name = uniqid("q_"). "." . $ext;
                move_uploaded_file($tmp, $dir . $image_name);
            }
        }
        
        // Update the question
        $stmt = $conn->prepare("SELECT picture FROM question WHERE question_id = ?");
        $stmt->bind_param("i", $qid);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $final_image = $image_name ?? $old['picture'];

        $stmt = $conn->prepare("
            UPDATE question SET
            question = ?, answer = ?, picture = ?,
            option_a = ?, option_b = ?, option_c = ?, option_d = ?
            WHERE question_id = ?
        ");

        $stmt->bind_param(
            "sssssssi",
            $q_text, $answer, $final_image,
            $optA, $optB, $optC, $optD,
            $qid
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert only truly new questions
    // Identify which form entries are new
    $newQuestionIndexes = [];
    foreach ($questions as $i => $q_text) {
        $qid = $question_ids[$i] ?? null;
        
        // If no question_id and not empty, it's a new question
        if (!$qid && $q_text !== '') {
            $newQuestionIndexes[] = $i;
        }
    }
    
    // Process new questions
    foreach ($newQuestionIndexes as $i) {
        $q_text = $questions[$i];
        $answer = $correct_answers[$i] ?? '';
        $optA = ($options_list[$i][0] ?? '');
        $optB = ($options_list[$i][1] ?? '');
        $optC = ($options_list[$i][2] ?? null);
        $optD = ($options_list[$i][3] ?? null);
    
        if ($optA === '' || $optB === '') {
            continue;
        }
    
        // Save image to file
        $image_name = null;
    
        if (!empty($_FILES['question_image']['name'][$i])) {
            $dir = "../../uploads/questions/";
        
            if (!is_dir($dir)) mkdir($dir, 0777, true);
        
            $tmp = $_FILES['question_image']['tmp_name'][$i];
            $name = $_FILES['question_image']['name'][$i];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $image_name = uniqid("q_"). "." . $ext;
                move_uploaded_file($tmp, $dir . $image_name);
            }
        }
        
        // Get next question number
        $stmt = $conn->prepare("SELECT COALESCE(MAX(question_number), 0) + 1 as next_number FROM question WHERE quiz_id = ?");
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $stmt->bind_result($next_number);
        $stmt->fetch();
        $stmt->close();
        
        // Insert the new question
        $stmt = $conn->prepare("
        INSERT INTO question
        (quiz_id, question_number, question, answer, picture, option_a, option_b, option_c, option_d)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisssssss",
            $quiz_id, $next_number, $q_text, $answer, $image_name,
            $optA, $optB, $optC, $optD
        );
        $stmt->execute();
        $stmt->close();
    }

    // Final renumbering
    $stmt = $conn->prepare(
        "SELECT question_id FROM question WHERE quiz_id = ? ORDER BY question_number ASC, question_id ASC"
    );
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $num = 1;
    while ($r = $res->fetch_assoc()) {
        $u = $conn->prepare(
            "UPDATE question SET question_number = ? WHERE question_id = ?"
        );
        $u->bind_param("ii", $num, $r['question_id']);
        $u->execute();
        $u->close();
        $num++;
    }

    header("Location: officer_quiz_summary.php?quiz_id=$quiz_id");
    exit();
}

$questions_data = [];

$stmt = $conn->prepare("SELECT * FROM question WHERE quiz_id = ? ORDER BY question_number ASC");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $questions_data[] = $row;
}

if (empty($questions_data)) {
    $questions_data[] = [
        'question_id' => null,
        'question' => '',
        'answer' => '',
        'picture' => null,
        'option_a' => '',
        'option_b' => '',
        'option_c' => null,
        'option_d' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_quiz.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
</head>
<body>
    
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Quiz /Create Quiz</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-seperator">/</span>
            <a href = "officer_quiz.php" class = "breadcrumb-link">Quiz</a>
            <span class = "breadcrumb-seperator">/</span>
            <a href = "officer_create_quiz.php" class = "breadcrumb-link">Create Quiz</a>
        </span>
    </div>

    <div class = "topbar-right">
        <a href = "<?php echo $profile_link; ?>" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "officer_profile.php">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">

    <div class = "sidebar">
        <a href = "officer_main.php">Main Menu</a>
        <a href = "officer_monthly_report.php">Monthly Report</a>
        <a href = "#">Events</a>
        <a href = "../student/browse_tips.php">Smart Tips</a>
        
        <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('quizMenu', this)">
            Quiz <span class="arrow">&#9662;</span> <!-- Pre-opened arrow style if active -->
        </a>
        
        <div id="quizMenu" class="dropdown-container" style="display: flex; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="officer_quiz.php" class="active" style="font-size: 0.9em;">View Quiz</a>
            <a href="officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
        </div>

        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content quiz-page quiz-question-page">

        <div class = "question-form-box">

            <a href = "officer_quiz.php" class = "close-btn">
                <img src = "../../assets/images/close-icon.png" alt = "Close">
            </a>

            <form method = "POST" action = "officer_create_questions.php?quiz_id=<?= $quiz_id ?>" enctype = "multipart/form-data">
                <input type = "hidden" name = "deleted_questions" id = "deleted_questions">
                <div class = "question-container">
                    <?php foreach ($questions_data as $i => $q): ?>
                        <div class = "question-box" data-question = "<?= $i + 1 ?>" data-question-id = "<?= $q['question_id'] ?? '' ?>">
                            
                            <div class = "question-header">
                            
                                <h3>Question <?= $i + 1 ?></h3>

                                <?php if ($i > 0): ?>
                                    <img src = "../../assets/images/close-icon.png" class = "icon delete-question" alt = "Delete question">
                                <?php endif; ?>
                            </div>

                            <input type = "hidden" name = "question_id[<?= $i ?>]" value = "<?= $q['question_id'] ?? '' ?>">


                            <div class = "form-row">
                                <label>Question</label>
                                <textarea name = "question[<?= $i ?>]" placeholder = "Question" required><?= htmlspecialchars($q['question']) ?></textarea>
                            </div>

                            <div class = "form-row">
                                <label>Image (optional)</label>
                                <input type = "file" name = "question_image[<?= $i ?>]" accept = "image/*">
                                <?php if (!empty($q['picture'])): ?>
                                    <div class = "current-image">
                                        Current: <img src = "../../uploads/questions/<?= htmlspecialchars($q['picture']) ?>" style = "max-width: 150px; display: block; margin-top: 5px;">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class = "form-row">
                                <label>Correct Answer</label>
                                <input type = "text" name = "correct_answer[<?= $i ?>]" value = "<?= htmlspecialchars($q['answer']) ?>" placeholder = "Correct Answer" required>
                            </div>

                            <div class = "option-box">
                                <label>Options</label>
                                <?php $options = [
                                    $q['option_a'] ?? '',
                                    $q['option_b'] ?? '',
                                    $q['option_c'] ?? '',
                                    $q['option_d'] ?? '',
                                    ];

                                    $numOptions = max(2, count(array_filter($options)));

                                    for ($j = 0; $j < $numOptions; $j++):
                                ?>
                                        <div class = "option-row">
                                            <input type = "text" name = "options[<?= $i ?>][]" value = "<?= htmlspecialchars($options[$j]) ?>" placeholder = "Option <?= $j + 1 ?>" <?= $j < 2 ? 'required' : '' ?>>
                                            <?php if ($j == 1 && $numOptions < 4): ?>
                                                <img src = "../../assets/images/plus-icon.png" class = "icon add-option" alt = "Add option">
                                            <?php elseif ($j > 1): ?>
                                                <img src = "../../assets/images/close-icon.png" class = "icon remove-option" alt = "Remove option">
                                            <?php endif; ?>
                                        </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class = "add-question-wrapper">
                    <img src = "../../assets/images/plus-icon.png" class = "icon add-question" alt = "Add question">
                </div>

                <div class = "form-footer">
                    <a href = "officer_create_quiz.php?quiz_id=<?= $quiz_id ?>" class = "back-btn">
                        <img src = "../../assets/images/back-icon.png" alt = "Back">
                    </a>

                    <button type = "submit" class = "next-btn">
                        <img src = "../../assets/images/next-icon.png" alt = "Next">
                    </button>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
let questionCount = document.querySelectorAll(".question-box").length;
let deletedQuestionIds = [];

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.option-box').forEach(optionBox => {
        updateOptionButtons(optionBox);
    });
});

document.addEventListener("click", function(e) {
    
    // Add option
    if (e.target.classList.contains("add-option")) {
        const optionBox = e.target.closest(".option-box");
        if (!optionBox) return;

        const questionBox = optionBox.closest(".question-box");
        const questionIndex = parseInt(questionBox.dataset.question) - 1;

        const optionRows = optionBox.querySelectorAll(".option-row");

        // Limit maximum 4 options only
        if (optionRows.length >= 4) {
            alert("Maximum 4 options allowed.");
            return;
        }

        const newOptionNumber = optionRows.length + 1;

        const row = document.createElement("div");
        row.className = "option-row";

        row.innerHTML = `<input type = "text" name="options[${questionIndex}][]" placeholder = "Option ${newOptionNumber}" required> <img src = "../../assets/images/close-icon.png" class = "icon remove-option" alt = "Remove option">`;

        optionBox.appendChild(row);

        updateOptionButtons(optionBox);
    }

    // Delete option
    if (e.target.classList.contains("remove-option")) {
        const optionBox = e.target.closest(".option-box");
        e.target.closest(".option-row")?.remove();
        updateOptionButtons(optionBox);
    }

    // Add question
    if (e.target.classList.contains("add-question")) {

        const container = document.querySelector(".question-container");
        const existingQuestions = document.querySelectorAll(".question-box");

        let newQuestion;
        
        if (existingQuestions.length > 0) {
            newQuestion = existingQuestions[0].cloneNode(true);
        } else {
            // Create a basic template if no questions exist
            newQuestion = document.createElement("div");
            newQuestion.className = "question-box";
            newQuestion.innerHTML = `
                <div class="question-header">
                    <h3>Question 1</h3>
                </div>
                <input type="hidden" name="question_id[0]" value="">
                <div class="form-row">
                    <label>Question</label>
                    <textarea name="question[0]" placeholder="Question" required></textarea>
                </div>
                <div class="form-row">
                    <label>Image (optional)</label>
                    <input type="file" name="question_image[0]" accept="image/*">
                </div>
                <div class="form-row">
                    <label>Correct Answer</label>
                    <input type="text" name="correct_answer[0]" value="" placeholder="Correct Answer" required>
                </div>
                <div class="option-box">
                    <label>Options</label>
                    <div class="option-row">
                        <input type="text" name="options[0][]" placeholder="Option 1" required>
                    </div>
                    <div class="option-row">
                        <input type="text" name="options[0][]" placeholder="Option 2" required>
                        <img src="../../assets/images/plus-icon.png" class="icon add-option" alt="Add option">
                    </div>
                </div>
            `;
        }

        questionCount = existingQuestions.length + 1;

        newQuestion.setAttribute("data-question", questionCount);
        newQuestion.setAttribute("data-question-id", "");

        const qIndex = questionCount - 1;

        newQuestion.querySelector("h3").innerText = "Question " + questionCount;

        // Reset input values
        const textarea = newQuestion.querySelector("textarea");
        textarea.value = "";
        textarea.name = `question[${qIndex}]`;

        const correctInput = newQuestion.querySelector('input[name^="correct_answer"]');
        correctInput.value = "";
        correctInput.name = `correct_answer[${qIndex}]`;
    
        const fileInput = newQuestion.querySelector('input[type="file"]');
        fileInput.value = "";
        fileInput.name = `question_image[${qIndex}]`;

        newQuestion.querySelectorAll(".current-image").forEach(imgBlock => {
            imgBlock.remove();
        });

        // Update hidden question_id input
        const questionIdInput = newQuestion.querySelector('input[name^="question_id"]');
        if (questionIdInput) {
            questionIdInput.value = "";
            questionIdInput.name = `question_id[${qIndex}]`;
        }

        // Reset options
        const optionBox = newQuestion.querySelector(".option-box");
        const rows = optionBox.querySelectorAll(".option-row");
        rows.forEach((row, idx) => {
            if (idx > 1) row.remove();
            row.querySelector('input').value = "";
            row.querySelector('input').name = `options[${qIndex}][]`;
        });

        updateOptionButtons(optionBox);

        // Add delete button to new questions
        let questionHeader = newQuestion.querySelector(".question-header");
        const title = newQuestion.querySelector("h3");

        if (!questionHeader) {
            questionHeader = document.createElement("div");
            questionHeader.className = "question-header";

            // Insert the header at the top of newQuestion
            newQuestion.prepend(questionHeader);
            questionHeader.appendChild(title);
        }

        if (!questionHeader.querySelector(".delete-question")) {
            const deleteBtn = document.createElement("img");
            deleteBtn.src = "../../assets/images/close-icon.png";
            deleteBtn.className = "icon delete-question";
            deleteBtn.alt = "Delete question";
            questionHeader.appendChild(deleteBtn);
        }

        container.appendChild(newQuestion);
    }

    // Delete question
    if (e.target.classList.contains("delete-question")) {
    const questionBox = e.target.closest(".question-box");

    const questionId = questionBox.dataset.questionId;

    // Only saved questions have question_id
    if (questionId) {
        deletedQuestionIds.push(questionId);
        document.querySelectorAll('input[name = "deleted_questions"]').forEach(input => {
            input.value = deletedQuestionIds.join(",");
        });
    }
    
    questionBox.remove();

    // Re-number remaining questions
    const container = document.querySelector(".question-container");
    const allQuestions = container.querySelectorAll(".question-box");

    questionCount = allQuestions.length;

    allQuestions.forEach((q, idx) => {
        q.dataset.question = idx + 1;
        q.querySelector("h3").innerText = "Question " + (idx + 1);

        q.querySelector("textarea").name = `question[${idx}]`;
        q.querySelector('input[name^="correct_answer"]').name = `correct_answer[${idx}]`;
        q.querySelector('input[type="file"]').name = `question_image[${idx}]`;

        const questionIdInput = q.querySelector('input[name^="question_id"]');
        if (questionIdInput) {
            questionIdInput.name = `question_id[${idx}]`;
        }

        q.querySelectorAll(".option-row input").forEach(input => {
            input.name = `options[${idx}][]`;
        });

        updateOptionButtons(q.querySelector(".option-box"));
    });
}
});

function updateOptionButtons(optionBox) {
    const rows = optionBox.querySelectorAll(".option-row");

    rows.forEach((row, idx) => {
        const input = row.querySelector("input");

        input.placeholder = `Option ${idx + 1}`;

        row.querySelectorAll("img").forEach(img => img.remove());

        if (idx === 1) {

            if (rows.length < 4) {
                const addBtn = document.createElement("img");
                addBtn.src = "../../assets/images/plus-icon.png";
                addBtn.className = "icon add-option";
                addBtn.alt = "Add option";
                row.appendChild(addBtn);
            }
        } else if (idx > 1) {
            const removeBtn = document.createElement("img");
            removeBtn.src = "../../assets/images/close-icon.png";
            removeBtn.className = "icon remove-option";
            removeBtn.alt = "Remove option";
            row.appendChild(removeBtn);
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {

    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.querySelector(".sidebar");

    const moreBtn = document.getElementById("moreBtn");
    const moreMenu = document.getElementById("moreMenu");

    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });
    }

    /* Sidebar Dropdown Toggle */
    window.toggleDropdown = function(id, el) {
        var dropdown = document.getElementById(id);
        if (dropdown.style.display === "none" || dropdown.style.display === "") {
            dropdown.style.display = "flex";
            if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9652;'; // Up arrow
        } else {
            dropdown.style.display = "none";
                if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9662;'; // Down arrow
        }
    }

    if (moreBtn && moreMenu) {
        moreBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            moreMenu.classList.toggle("active");
        });
    }

    /* Auto-close when clicking outside */
    document.addEventListener("click", function(e) {
        
        /*  Close sidebar */
        if (
            sidebar &&
            sidebar.classList.contains("active") &&
            !sidebar.contains(e.target) &&
            e.target !== menuBtn
        ){
            sidebar.classList.remove("active");
        }

        /* Close more menu */
        if (
            moreMenu &&
            moreMenu.classList.contains("active") &&
            !moreMenu.contains(e.target) &&
            e.target !== moreBtn
        ){
            moreMenu.classList.remove("active")
        }
    });
});

document.addEventListener('change', function(e) {
    if (e.target.type === 'file') {
        const file = e.target.files[0];
        if (file && !file.type.startsWith('image/')) {
            alert("Only image files are allowed!");
            e.target.value = '';
        }
    }
});
</script>

</body>
</html>