<?php

require_once __DIR__ . '/../services/NotificationService.php';

class NotificationController
{
    private NotificationService $service;

    public function __construct(PDO $db)
    {
        $this->service = new NotificationService($db);
    }

    public function list(int $userId): array
    {
        return [
            'success' => true,
            'data' => $this->service->getNotifications($userId)
        ];
    }

    public function markRead(
        int $userId,
        ?int $notificationId,
        bool $markAll = false
    ): array {
        if ($markAll) {
            $this->service->markAllAsRead($userId);
        } elseif ($notificationId !== null && $notificationId > 0) {
            $this->service->markAsRead($userId, $notificationId);
        } else {
            throw new InvalidArgumentException(
                'Dữ liệu không hợp lệ.'
            );
        }

        return [
            'success' => true,
            'message' => 'Đã đánh dấu thông báo đã đọc.'
        ];
    }
}
