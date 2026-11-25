-- Something About Pets – MySQL schema (GoDaddy cPanel / phpMyAdmin).
-- install.php runs this for you. You can also import it in phpMyAdmin.

CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role            VARCHAR(10)  NOT NULL DEFAULT 'client',   -- 'client' or 'admin'
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    phone           VARCHAR(40)  NOT NULL DEFAULT '',
    address         VARCHAR(255) NOT NULL DEFAULT '',
    password_hash   VARCHAR(255) NOT NULL,
    created_at      DATETIME     NOT NULL,
    last_login_at   DATETIME     NULL,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token_hash  CHAR(64)     NOT NULL,
    expires_at  DATETIME     NOT NULL,
    used_at     DATETIME     NULL,
    KEY idx_resets_token (token_hash),
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(190) NOT NULL,
    ip            VARCHAR(45)  NOT NULL,
    attempted_at  DATETIME     NOT NULL,
    KEY idx_attempts (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    name        VARCHAR(80)  NOT NULL,
    species     VARCHAR(20)  NOT NULL DEFAULT 'dog',   -- dog, cat, other
    breed       VARCHAR(80)  NOT NULL DEFAULT '',
    age         VARCHAR(40)  NOT NULL DEFAULT '',
    notes       TEXT         NULL,                       -- feeding, medication, vet, behaviour
    created_at  DATETIME     NOT NULL,
    KEY idx_pets_user (user_id),
    CONSTRAINT fk_pets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(80)  NOT NULL,
    description       TEXT         NULL,
    kind              VARCHAR(10)  NOT NULL DEFAULT 'slot',  -- 'slot' (time slot) or 'stay' (date range)
    duration_minutes  INT          NOT NULL DEFAULT 60,      -- slot services only
    price_cents       INT          NULL,                     -- NULL = "price on request", stays are per night
    active            TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order        INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS working_hours (
    weekday     TINYINT      NOT NULL PRIMARY KEY,   -- 0 = Sunday … 6 = Saturday
    is_open     TINYINT(1)   NOT NULL DEFAULT 1,
    open_time   CHAR(5)      NOT NULL DEFAULT '08:00',
    close_time  CHAR(5)      NOT NULL DEFAULT '18:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blocked_dates (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocked_date  DATE         NOT NULL,
    reason        VARCHAR(120) NOT NULL DEFAULT '',
    UNIQUE KEY uq_blocked_date (blocked_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    service_id   INT UNSIGNED NOT NULL,
    start_at     DATETIME     NOT NULL,
    end_at       DATETIME     NOT NULL,
    status       VARCHAR(12)  NOT NULL DEFAULT 'pending',  -- pending, confirmed, declined, cancelled, completed
    price_cents  INT          NULL,
    notes        TEXT         NULL,
    admin_note   TEXT         NULL,
    created_at   DATETIME     NOT NULL,
    updated_at   DATETIME     NOT NULL,
    KEY idx_bookings_time (status, start_at, end_at),
    KEY idx_bookings_user (user_id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_pets (
    booking_id  INT UNSIGNED NOT NULL,
    pet_id      INT UNSIGNED NOT NULL,
    PRIMARY KEY (booking_id, pet_id),
    CONSTRAINT fk_bp_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_bp_pet FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    subject     VARCHAR(190) NOT NULL DEFAULT '',
    message     TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
