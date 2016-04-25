CREATE TABLE acl_user (
    `id` INT (6) NOT NULL AUTO_INCREMENT,
    `login` VARCHAR (50) NOT NULL,
    `password` VARCHAR (32) NOT NULL,
    `name` VARCHAR (128) CHARACTER SET cp1251 NOT NULL COMMENT 'имя и фамилия',
    PRIMARY KEY (id),
    UNIQUE INDEX `login` USING BTREE (`login`)
) DEFAULT CHARSET = cp1251;

CREATE TABLE acl_group (
    `id` INT (4) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR (128) NOT NULL,
    `descr` VARCHAR (255) NOT NULL COMMENT 'описание группы',
    PRIMARY KEY (id),
    UNIQUE INDEX `name` USING BTREE (`name`)
) DEFAULT CHARSET = cp1251;

INSERT INTO acl_group VALUES(1, 'superusers', 'System group');
INSERT INTO acl_group VALUES(2, 'Not authenticated', 'System group');
INSERT INTO acl_group VALUES(3, 'Authenticated', 'System group');


CREATE TABLE acl_user_group_rel (
    `id` INT (11) NOT NULL AUTO_INCREMENT,
    `user_id` INT (6) NOT NULL,
    `group_id` INT (4) NOT NULL,
    PRIMARY KEY (id)
);


CREATE TABLE acl_resource (
    `id` INT (11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR (128) NOT NULL,
    `path` VARCHAR (255) NOT NULL,
    `descr` VARCHAR (255) NOT NULL,
    `parent` INT (11) DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE INDEX `path` USING BTREE (`path`)
) DEFAULT CHARSET = cp1251;

INSERT INTO acl_resource VALUES(1, 'Root of the project', '/', '', 0);

CREATE TABLE acl_resource_user_rel (
    `id` INT (11) NOT NULL AUTO_INCREMENT,
    `resource_id` INT (6) NOT NULL,
    `user_id` INT (6) NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE acl_resource_group_rel (
    `id` INT (11) NOT NULL AUTO_INCREMENT,
    `resource_id` INT (6) NOT NULL,
    `group_id` INT (4) NOT NULL,
    PRIMARY KEY (id)
);

