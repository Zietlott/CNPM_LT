<?php

class NotificationService
{
    public function __construct(
        private PDO $db
    ) {}

    public function getNotifications(int $userId, int $limit = 30): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID');
        }

        $limit = max(1, min($limit, 100));

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*)
             FROM notifications
             WHERE user_id = :user_id AND is_read = 0'
        );
        $countStmt->execute(['user_id' => $userId]);

        $stmt = $this->db->prepare(
            "SELECT id, user_id, type, title, content,
                    reference_id, is_read, created_at
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT $limit"
        );
        $stmt->execute(['user_id' => $userId]);

        return [
            'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'unread_count' => (int) $countStmt->fetchColumn()
        ];
    }

    public function markAsRead(int $userId, int $notificationId): int
    {
        if ($userId <= 0 || $notificationId <= 0) {
            throw new InvalidArgumentException('Invalid notification ID');
        }

        $stmt = $this->db->prepare(
            'UPDATE notifications
             SET is_read = 1
             WHERE id = :id
               AND user_id = :user_id
               AND is_read = 0'
        );

        $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId
        ]);

        return $stmt->rowCount();
    }

    public function markAllAsRead(int $userId): int
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID');
        }

        $stmt = $this->db->prepare(
            'UPDATE notifications
             SET is_read = 1
             WHERE user_id = :user_id
               AND is_read = 0'
        );

        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();
    }
}
