CREATE SCHEMA `mysql_framework_php_testing` ;

CREATE TABLE `mysql_framework_php_testing`.`people` (
  `person_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  `title` VARCHAR(45) NULL,
  PRIMARY KEY (`person_id`));

CREATE TABLE `mysql_framework_php_testing`.`authors` (
  `author_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  PRIMARY KEY (`author_id`));

CREATE TABLE `mysql_framework_php_testing`.`books` (
  `book_id` INT NOT NULL AUTO_INCREMENT,
  `author_id` INT NOT NULL,
  `title` VARCHAR(45) NULL,
  PRIMARY KEY (`book_id`),
  INDEX `book_author_fk_idx` (`author_id` ASC),
  CONSTRAINT `book_author_fk`
  FOREIGN KEY (`author_id`)
  REFERENCES `mysql_framework_php_testing`.`authors` (`author_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
