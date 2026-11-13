-- Nested Forum Lab: schema + fictional seed data
CREATE DATABASE IF NOT EXISTS nested_forum
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nested_forum;


SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS discussions;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;


SET time_zone = '+00:00';


CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(30)  NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NULL,          -- NULL = cannot log in (seed users)
  avatar        VARCHAR(255) NULL,          -- optional extension, unused
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;


CREATE TABLE discussions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  title      VARCHAR(200) NOT NULL,
  body       TEXT         NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_discussions_user FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB;


CREATE TABLE comments (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  discussion_id INT UNSIGNED NOT NULL,
  parent_id     INT UNSIGNED NULL,          -- NULL = top-level comment
  user_id       INT UNSIGNED NOT NULL,
  body          TEXT         NOT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,


  -- needed so (discussion_id, parent_id) can reference (discussion_id, id)
  UNIQUE KEY uq_comments_discussion_id (discussion_id, id),
  KEY idx_comments_parent (discussion_id, parent_id),


  CONSTRAINT fk_comments_discussion FOREIGN KEY (discussion_id) REFERENCES discussions (id),
  CONSTRAINT fk_comments_user       FOREIGN KEY (user_id)       REFERENCES users (id),
  -- composite FK: a parent must be in the SAME discussion
  CONSTRAINT fk_comments_parent_same_discussion
    FOREIGN KEY (discussion_id, parent_id) REFERENCES comments (discussion_id, id)
) ENGINE=InnoDB;


-- ---------- Fictional seed data ----------
-- Seed users have NULL password hashes, so nobody can log in as them.
INSERT INTO users (id, username, email, password_hash) VALUES
  (1, 'alice', 'alice@example.com', NULL),
  (2, 'ben',   'ben@example.com',   NULL),
  (3, 'cara',  'cara@example.com',  NULL),
  (4, 'dee',   'dee@example.com',   NULL),
  (5, 'eli',   'eli@example.com',   NULL);


INSERT INTO discussions (id, user_id, title, body, created_at) VALUES
  (1, 1, 'How do I learn PHP?', 'Share your learning tips.', '2026-11-01 08:00:00');


-- parent_id builds the tree; comment 7 reaches displayed depth 4
INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
  (1, 1, NULL, 1, 'Start with the PHP manual.',     '2026-11-01 09:00:00'),
  (2, 1, 1,    2, 'Try the examples as you read.',  '2026-11-01 09:10:00'),
  (3, 1, 2,    3, 'Build a small project too.',     '2026-11-01 09:20:00'),
  (4, 1, 1,    4, 'Which manual section first?',    '2026-11-01 09:30:00'),
  (5, 1, NULL, 5, 'Practice SQL as well.',          '2026-11-01 09:40:00'),
  (6, 1, 3,    2, 'Start with a forum.',            '2026-11-01 09:50:00'),
  (7, 1, 6,    5, 'Add replies next.',              '2026-11-01 10:00:00');


-- A second discussion, used to test the cross-discussion parent check
INSERT INTO discussions (id, user_id, title, body, created_at) VALUES
  (2, 4, 'Favourite MySQL tips?', 'Post your best query tricks.', '2026-11-02 08:00:00');
INSERT INTO comments (id, discussion_id, parent_id, user_id, body, created_at) VALUES
  (8, 2, NULL, 3, 'Always use prepared statements.', '2026-11-02 08:30:00');