<?php
declare(strict_types=1);

/**
 * backend/actions/review_create.php
 * Обработка формы отзыва о работе клиники.
 *
 * Отзыв может быть оставлен только владельцем заявки
 * и только если приём уже завершён.
 */

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

// Заявка должна существовать и принадлежать текущему пользователю.
if ($application === null || (int) $application['user_id'] !== Auth::id()) {
    setErrors(['review' => 'Заявка не найдена.']);
    redirect('frontend/applications.php');
}

// Проверка статуса выполняется на сервере.
if (!Application::canReview($application['status'])) {
    setErrors(['review' => 'Отзыв можно оставить только после завершения приёма.']);
    redirect('frontend/applications.php');
}

// Один отзыв на одну заявку.
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