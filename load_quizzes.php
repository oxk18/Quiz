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

// 퀴즈 HTML 생성
ob_start();
?>
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
<?php endforeach;?>
<button onclick="calculateScore()">점수 계산</button>
<div id="final-score" style="display: none;"></div>
<button onclick="loadMoreQuizzes()">다음 퀴즈 로드</button>
<?php echo ob_get_clean();
?>