# TwelveT API数据库

# twelvet_version

DROP TABLE IF EXISTS `twelvet_demo`;
CREATE TABLE `twelvet_demo`(
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(20) NOT NULL COMMENT '版本号名称',
    `file_name` varchar(20) NOT NULL COMMENT '版本zip包名称',
    `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
    `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    UNIQUE KEY `file_name` (`file_name`)
)ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO `twelvet_demo`(`name`, `file_name`) values('2.0', '2.0.zip');