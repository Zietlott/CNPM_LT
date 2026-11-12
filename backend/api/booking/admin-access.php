<?php

require_once __DIR__ . '/common.php';

bookingRequireRole('admin');
$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'GET') {
    try {
        $controller = new BookingController();
        bookingRespond([
            'success' => true,
            'data' => $controller->getAdminBookingAccessData()
        ]);
    } catch (Throwable $error) {
        bookingHandleError($error);
    }
}

if ($method !== 'POST' && $method !== 'PUT') {
    header('Allow: GET, POST, PUT');
    bookingRespond([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ.'
    ], 405);
}

bookingRequireCsrf();
$input = bookingReadJsonInput();
$action = $input['action'] ?? null;

try {
    $controller = new BookingController();
    if ($method === 'POST' && $action === 'create_trainer') {
        $trainerId = filter_var(
            $input['trainer_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $email = $input['email'] ?? null;
        $phone = $input['phone'] ?? null;
        $password = $input['password'] ?? null;
        if (
            $trainerId === false
            || !is_string($email)
            || !is_string($phone)
            || !is_string($password)
        ) {
            throw new BookingException(
                'Thông tin tài khoản PT không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $memberId = $controller->createTrainerAccount(
            $trainerId,
            $email,
            $phone,
            $password
        );
        bookingRespond([
            'success' => true,
            'message' => 'Đã tạo tài khoản đăng nhập cho PT.',
            'data' => ['member_id' => $memberId]
        ], 201);
    }

    if ($method === 'POST' && $action === 'create_staff') {
        $fields = ['full_name', 'email', 'phone', 'password'];
        foreach ($fields as $field) {
            if (!isset($input[$field]) || !is_string($input[$field])) {
                throw new BookingException(
                    'Thông tin tài khoản Staff không hợp lệ.',
                    'INVALID_BOOKING_DATA',
                    422
                );
            }
        }

        $memberId = $controller->createStaffAccount(
            $input['full_name'],
            $input['email'],
            $input['phone'],
            $input['password'],
            ($input['can_confirm'] ?? false) === true,
            ($input['can_cancel'] ?? false) === true
        );
        bookingRespond([
            'success' => true,
            'message' => 'Đã tạo tài khoản Staff và lưu quyền booking.',
            'data' => ['member_id' => $memberId]
        ], 201);
    }

    if ($method === 'PUT' && $action === 'update_staff_permissions') {
        $staffId = filter_var(
            $input['staff_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if (
            $staffId === false
            || !is_bool($input['can_confirm'] ?? null)
            || !is_bool($input['can_cancel'] ?? null)
        ) {
            throw new BookingException(
                'Quyền Staff không hợp lệ.',
                'INVALID_BOOKING_DATA',
                422
            );
        }

        $controller->updateStaffPermissions(
            $staffId,
            $input['can_confirm'],
            $input['can_cancel']
        );
        bookingRespond([
            'success' => true,
            'message' => 'Đã cập nhật quyền booking của Staff.'
        ]);
    }

    throw new BookingException(
        'Thao tác quản lý tài khoản không hợp lệ.',
        'INVALID_BOOKING_DATA',
        422
    );
} catch (Throwable $error) {
    bookingHandleError($error);
}
