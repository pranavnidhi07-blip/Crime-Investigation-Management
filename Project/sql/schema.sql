-- Crime Investigation Management System
-- 3NF schema with PKs, FKs, CHECKs, UNIQUE, and documented delete rules.

CREATE DATABASE IF NOT EXISTS crime_investigation
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE crime_investigation;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS case_officer;
DROP TABLE IF EXISTS case_suspect;
DROP TABLE IF EXISTS evidence;
DROP TABLE IF EXISTS `case`;
DROP TABLE IF EXISTS suspect;
DROP TABLE IF EXISTS victim;
DROP TABLE IF EXISTS officer;
DROP TABLE IF EXISTS investigation_team;
DROP TABLE IF EXISTS location;
DROP TABLE IF EXISTS tmp_demo;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE location (
  location_id   INT AUTO_INCREMENT PRIMARY KEY,
  address       VARCHAR(200) NOT NULL,
  city          VARCHAR(80)  NOT NULL,
  state         VARCHAR(80)  NOT NULL,
  description   VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE investigation_team (
  team_id     INT AUTO_INCREMENT PRIMARY KEY,
  team_name   VARCHAR(120) NOT NULL,
  department  VARCHAR(80)  NOT NULL,
  contact_no  VARCHAR(20)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE officer (
  officer_id     INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(120) NOT NULL,
  `rank`         VARCHAR(80)  NOT NULL,
  department     VARCHAR(80)  NOT NULL,
  email          VARCHAR(120) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE victim (
  victim_id   INT AUTO_INCREMENT PRIMARY KEY,
  case_id     INT NULL,
  name        VARCHAR(120) NOT NULL,
  gender      ENUM('Male', 'Female', 'Other') NOT NULL,
  contact_no  VARCHAR(20)  NOT NULL
) ENGINE=InnoDB;

CREATE TABLE suspect (
  suspect_id       INT AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(120) NOT NULL,
  age              INT NOT NULL,
  gender           ENUM('Male', 'Female', 'Other') NOT NULL,
  criminal_record  VARCHAR(255) NULL,
  victim_id        INT NULL,
  CONSTRAINT chk_suspect_age CHECK (age BETWEEN 10 AND 100),
  CONSTRAINT fk_suspect_victim
    FOREIGN KEY (victim_id) REFERENCES victim(victim_id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `case` (
  case_id      INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(180) NOT NULL,
  description  TEXT         NOT NULL,
  status       ENUM('Open', 'Under Investigation', 'Closed') NOT NULL DEFAULT 'Open',
  date_opened  DATE         NOT NULL,
  officer_id   INT NULL,
  team_id      INT NULL,
  location_id  INT NULL,
  CONSTRAINT fk_case_officer
    FOREIGN KEY (officer_id) REFERENCES officer(officer_id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_case_team
    FOREIGN KEY (team_id) REFERENCES investigation_team(team_id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_case_location
    FOREIGN KEY (location_id) REFERENCES location(location_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE victim
  ADD CONSTRAINT fk_victim_case
    FOREIGN KEY (case_id) REFERENCES `case`(case_id)
    ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE evidence (
  evidence_id     INT AUTO_INCREMENT PRIMARY KEY,
  `type`          VARCHAR(80)  NOT NULL,
  description     VARCHAR(255) NOT NULL,
  status          ENUM('Collected', 'In Analysis', 'Archived') NOT NULL DEFAULT 'Collected',
  collected_date  DATE         NOT NULL,
  case_id         INT          NOT NULL,
  location_id     INT          NOT NULL,
  CONSTRAINT fk_evidence_case
    FOREIGN KEY (case_id) REFERENCES `case`(case_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_evidence_location
    FOREIGN KEY (location_id) REFERENCES location(location_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE case_suspect (
  case_id     INT NOT NULL,
  suspect_id  INT NOT NULL,
  PRIMARY KEY (case_id, suspect_id),
  CONSTRAINT fk_cs_case
    FOREIGN KEY (case_id) REFERENCES `case`(case_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_cs_suspect
    FOREIGN KEY (suspect_id) REFERENCES suspect(suspect_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE case_officer (
  case_id     INT NOT NULL,
  officer_id  INT NOT NULL,
  PRIMARY KEY (case_id, officer_id),
  CONSTRAINT fk_co_case
    FOREIGN KEY (case_id) REFERENCES `case`(case_id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_co_officer
    FOREIGN KEY (officer_id) REFERENCES officer(officer_id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
