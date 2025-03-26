<?php
// API URL
$url = "https://opentdb.com/api.php?amount=10&encode=base64";

// cURL을 사용하여 API 데이터 가져오기
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
// Check if cURL request was successful
if ($response === false) {
    die('Error fetching data from API: ' . curl_error($ch));
}
curl_close($ch);

// JSON 데이터 디코딩
$data = json_decode($response, true);

// Check if JSON decoding was successful
if ($data === null) {
    die('Error decoding JSON data: ' . json_last_error_msg());
}

// Check if results array exists
if (!isset($data['results']) || !is_array($data['results'])) {
    die('Invalid API response format. 퀴즈 데이터를 불러오는데 실패했습니다. 다시 시도해주세요.');
}

// Base64 디코딩
foreach ($data['results'] as &$question) {
    $question['question'] = base64_decode($question['question']);
    $question['correct_answer'] = base64_decode($question['correct_answer']);
    foreach ($question['incorrect_answers'] as &$incorrect) {
        $incorrect = base64_decode($incorrect);
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
	include_once('../../common.php');
	$g5['title'] = "나는 퀴즈왕!";
	include_once(G5_PATH.'/_head.php');
	?>    
    <style>
    .quiz-container {
        font-family: Arial, sans-serif;
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background-color: #f9f9f9;
    }
    .quiz-item {
        margin-bottom: 20px;
        padding: 15px;
        border-bottom: 1px solid #eee;
    }
    .quiz-item:last-child {
        border-bottom: none;
    }
    .quiz-item h3 {
        margin-top: 0;
    }
    label {
        display: block;
        margin: 5px 0;
    }
    input[type="radio"] {
        margin-right: 10px;
    }
    </style>
</head>
<body>   
    <div class="quiz-container">
     <audio id="ticktock_sound" src="ticktock.mp3"></audio>
        <div id="timer"><font color="red">남은 시간: <span id="time">180</span>초</font></div>
        <?php foreach ($data['results'] as $index => $quiz): ?>
        <div class="quiz-item" id="quiz-<?php echo $index; ?>">
            <h3><?php echo ($index + 1) . ". " . $quiz['question']; ?></h3>
            <?php
            $answers = array_merge([$quiz['correct_answer']], $quiz['incorrect_answers']);
            shuffle($answers);
            foreach ($answers as $answer): ?>
            <label>
                <input type="radio" name="answer_<?php echo $index; ?>" value="<?php echo $answer; ?>"
                       onclick="checkAnswer(<?php echo $index; ?>, '<?php echo addslashes($answer); ?>', '<?php echo addslashes($quiz['correct_answer']); ?>')">
                <?php echo $answer; ?>
            </label><br>
            <?php endforeach; ?>
            <p id="result-<?php echo $index; ?>" style="display: none;"></p>
        </div>
        <?php endforeach; ?>
        <button onclick="calculateScore()">점수 계산</button>
        <div id="final-score" style="display: none;"></div>
        <button onclick="loadMoreQuizzes()">다음 퀴즈 로드</button>
    </div>

    <script>
    let score = 0;
    let timeLeft = 180;
    let timerInterval;
    let timerStarted = false; // Add flag to track if timer has ever been started

    // 타이머 시작
    function startTimer() {
        if (timerStarted) return; // Don't start if timer has already been started
        timerStarted = true;
        
        const ticktock_sound = document.getElementById('ticktock_sound');
        timerInterval = setInterval(() => {
            timeLeft--;
            document.getElementById('time').innerText = timeLeft;
            
            ticktock_sound.currentTime = 0;
            ticktock_sound.play();            
           
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert("시간이 종료되었습니다!");
                calculateScore();
            }
        }, 1000);
    }

    // 정답 확인
    function checkAnswer(quizIndex, userAnswer, correctAnswer) {
        const resultElement = document.getElementById(`result-${quizIndex}`);
        if (userAnswer === correctAnswer) {
            resultElement.innerText = "정답입니다! 🎉";
            resultElement.style.color = "green";
            score++;
        } else {
            resultElement.innerText = `틀렸습니다. 정답은 "${correctAnswer}" 입니다. 😅`;
            resultElement.style.color = "red";
        }
        resultElement.style.display = "block";
    }

    // 점수 계산
    function calculateScore() {
        clearInterval(timerInterval);
        const finalScoreElement = document.getElementById('final-score');
        //finalScoreElement.innerText = `최종 점수: ${score} / ${<?php echo count($data['results']); ?>}`;
		finalScoreElement.innerText = `최종 점수: ${score}`;
        finalScoreElement.style.display = "block";
    }

    // 다음 퀴즈 로드 (AJAX)
    function loadMoreQuizzes() {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "load_quizzes.php", true); // load_quizzes.php는 새로운 퀴즈를 로드하는 서버 스크립트
        xhr.onload = function () {
            if (xhr.status === 200) {
                document.querySelector('.quiz-container').innerHTML = xhr.responseText;
                //startTimer(); // 새로운 퀴즈 로드 후 타이머 재시작
            }
        };
        xhr.send();
    }

    // 페이지 로드 시 타이머 시작
    //window.onload = startTimer;
	window.addEventListener('load', startTimer);//window.onload 의 중복사용 에러를 피하기위해 위 문장 형식 변경
    </script>
</body>
</html>
<?php
include_once(G5_PATH.'/_tail.php');
?>