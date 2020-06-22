CREATE TABLE IF NOT EXISTS alignment_tags (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  alignment_id int(10) UNSIGNED NOT NULL,
  tag_id int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alignments (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  individual_id int(10) UNSIGNED NOT NULL,
  organisation_id int(10) UNSIGNED NOT NULL,
  type varchar(191) DEFAULT 'member',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS authkeys (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  authkey varchar(40) CHARSET ascii COLLATE ascii_general_ci,
  created int(10) UNSIGNED NOT NULL,
  valid_until int(10) UNSIGNED NOT NULL,
  user_id int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX (authkey),
  INDEX (created),
  INDEX (valid_until)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS broods (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid varchar(40) CHARSET ascii COLLATE ascii_general_ci DEFAULT NULL,
  name varchar(191) NOT NULL,
  url varchar(191) NOT NULL,
  description text,
  organisation_id int(10) UNSIGNED NOT NULL,
  alignment_id int(10) UNSIGNED NOT NULL,
  trusted tinyint(1),
  pull tinyint(1),
  authkey varchar(40) CHARSET ascii COLLATE ascii_general_ci,
  PRIMARY KEY (id),
  INDEX (uuid),
  INDEX (name),
  INDEX (url),
  INDEX (authkey)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS individuals (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid varchar(40) CHARSET ascii COLLATE ascii_general_ci DEFAULT NULL,
  email varchar(191) NOT NULL,
  first_name varchar(191) NOT NULL,
  last_name varchar(191) NOT NULL,
  position text,
  PRIMARY KEY (id),
  INDEX (uuid),
  INDEX (email),
  INDEX (first_name),
  INDEX (last_name)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS individual_encryption_keys (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  individual_id int(10) UNSIGNED NOT NULL,
  encryption_key_id int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS encryption_keys (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid varchar(40) CHARSET ascii COLLATE ascii_general_ci DEFAULT NULL,
  type varchar(191) NOT NULL,
  encryption_key text,
  revoked tinyint(1),
  expires int(10) UNSIGNED,
  PRIMARY KEY (id),
  INDEX (uuid),
  INDEX (type),
  INDEX (expires)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS organisation_encryption_keys (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  organisation_id int(10) UNSIGNED NOT NULL,
  encryption_key_id int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS organisations (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid varchar(40) CHARSET ascii COLLATE ascii_general_ci DEFAULT NULL,
  name varchar(191) NOT NULL,
  url varchar(191),
  nationality varchar(191),
  sector varchar(191),
  type varchar(191),
  contacts text,
  PRIMARY KEY (id),
  INDEX (uuid),
  INDEX (name),
  INDEX (url),
  INDEX (nationality),
  INDEX (sector),
  INDEX (type)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid varchar(40) CHARSET ascii COLLATE ascii_general_ci DEFAULT NULL,
  name varchar(191) NOT NULL,
  is_default tinyint(1),
  perm_admin tinyint(1),
  PRIMARY KEY (id),
  INDEX (name),
  INDEX (uuid)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(191) NOT NULL,
  description text,
  colour varchar(6) CHARSET ascii COLLATE ascii_general_ci NOT NULL,
  PRIMARY KEY (id),
  INDEX (name)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_keys (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id int(10) UNSIGNED NOT NULL,
  authkey_id int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid varchar(40) CHARSET ascii COLLATE ascii_general_ci DEFAULT NULL,
  email varchar(191) NOT NULL,
  password varchar(191),
  role_id int(11) UNSIGNED NOT NULL,
  individual_id int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX (uuid),
  INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE alignment_tags
  ADD FOREIGN KEY (alignment_id) REFERENCES alignments(id),
  ADD FOREIGN KEY (tag_id) REFERENCES tags(id);

ALTER TABLE alignments
  ADD FOREIGN KEY (individual_id) REFERENCES individuals(id),
  ADD FOREIGN KEY (organisation_id) REFERENCES organisations(id);

ALTER TABLE authkeys
  ADD FOREIGN KEY (user_id) REFERENCES users(id);

ALTER TABLE broods
  ADD FOREIGN KEY (alignment_id) REFERENCES alignments(id);

ALTER TABLE individual_encryption_keys
  ADD FOREIGN KEY (individual_id) REFERENCES individuals(id),
  ADD FOREIGN KEY (encryption_key_id) REFERENCES encryption_keys(id);

ALTER TABLE organisation_encryption_keys
  ADD FOREIGN KEY (organisation_id) REFERENCES organisations(id),
  ADD FOREIGN KEY (encryption_key_id) REFERENCES encryption_keys(id);

ALTER TABLE user_keys
  ADD FOREIGN KEY (user_id) REFERENCES users(id),
  ADD FOREIGN KEY (authkey_id) REFERENCES authkeys(id);

ALTER TABLE users
  ADD FOREIGN KEY (role_id) REFERENCES roles(id),
  ADD FOREIGN KEY (individual_id) REFERENCES individuals(id);

