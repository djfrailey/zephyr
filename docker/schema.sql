CREATE TABLE `users` (
    `email_address` VARCHAR(255) NOT NULL,
    `username` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`email_address`)
) ENGINE=InnoDB DEFAULT charset=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `metrics` (
    `email_address` VARCHAR(255) NOT NULL,
    `metric_type` VARCHAR(255) NOT NULL,
    `value` DECIMAL(12, 3) NOT NULL,
    `date` DATE NOT NULL
) ENGINE=InnoDB DEFAULT charset=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_lookup` (
    `user_email_address` VARCHAR(255) NOT NULL,
    `account_identifier` int(11) NOT NULL,
    `account_type` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT charset=utf8mb4 COLLATE=utf8mb4_unicode_ci;