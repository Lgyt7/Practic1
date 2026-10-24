<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

Auth::requireLogin();

if (!isPost()) {
    redirect('frontend/applications.php');
}

$applicationId = (int) post('application_id');
$text          = post('review');
$rating        = post('rating') === '' ? null : (int) post('rating');

setOld(['review' => $text, 'rating' => $rating === null ? '' : (string) $rating]);

$application = Application::findById($applicationId);

if ($application === null || (int) $application['user_id'] !== Auth::id()) {
    setErrors(['review' => 'Заявка не найдена.']);
    redirect('frontend/applications.php');
}

if (!Application::canReview($application['status'])) {
    setErrors(['review' => 'Отзыв можно оставить только после завершения приёма.']);
    redirect('frontend/applications.php');
}

if (Review::exists($applicationId)) {
    setErrors(['review' => 'Отзыв по этой заявке уже оставлен.']);
    redirect('frontend/applications.php');
}

$validator = Validator::make($_POST)->text('review', 'Отзыв', 3, 1000, true);

if ($validator->fails()) {
    setErrors($validator->errors());
    redirect('frontend/applications.php');
}

if ($rating !== null && ($rating < 1 || $rating > 5)) {
    setErrors(['rating' => 'Оценка должна быть от 1 до 5.']);
    redirect('frontend/applications.php');
}

Review::create($applicationId, (int) Auth::id(), $text, $rating);

setOld([]);
setErrors([]);
flash('success', 'Спасибо за отзыв!');
redirect('frontend/applications.php');
