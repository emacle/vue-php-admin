/*
MySQL Backup
Source Server Version: 8.0.12
Source Database: vueadminv2
Date: 2020/4/14 11:37:12
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
--  Table structure for `article`
-- ----------------------------
DROP TABLE IF EXISTS `article`;
CREATE TABLE `article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '文章id',
  `title` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `author` varchar(32) NOT NULL,
  `pageviews` varchar(512) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `display_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `keys`
-- ----------------------------
DROP TABLE IF EXISTS `keys`;
CREATE TABLE `keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `key` varchar(40) NOT NULL,
  `level` int(2) NOT NULL,
  `ignore_limits` tinyint(1) NOT NULL DEFAULT '0',
  `is_private_key` tinyint(1) NOT NULL DEFAULT '0',
  `ip_addresses` text,
  `date_created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `logs`
-- ----------------------------
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL,
  `method` varchar(6) NOT NULL,
  `params` text,
  `api_key` varchar(40) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `time` int(11) NOT NULL,
  `rtime` float DEFAULT NULL,
  `authorized` varchar(1) NOT NULL,
  `response_code` smallint(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_dept`
-- ----------------------------
DROP TABLE IF EXISTS `sys_dept`;
CREATE TABLE `sys_dept` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT '机构名称',
  `aliasname` varchar(255) DEFAULT NULL,
  `listorder` int(11) DEFAULT '99',
  `status` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_menu`
-- ----------------------------
DROP TABLE IF EXISTS `sys_menu`;
CREATE TABLE `sys_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL,
  `component` varchar(255) DEFAULT NULL,
  `type` tinyint(4) DEFAULT NULL COMMENT '0:目录，1:菜单, 3:功能/按钮/操作',
  `title` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `redirect` varchar(255) DEFAULT '' COMMENT 'redirect: noredirect           if `redirect:noredirect` will no redirect in the breadcrumb',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `condition` varchar(255) DEFAULT '' COMMENT '规则表达式，为空表示存在就验证，不为空表示按照条件验证',
  `listorder` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_perm`
-- ----------------------------
DROP TABLE IF EXISTS `sys_perm`;
CREATE TABLE `sys_perm` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `perm_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '权限类型：menu:菜单路由类,role:角色类,file:文件类',
  `r_id` int(11) NOT NULL COMMENT '实际基础表的关联id，如菜单表ID，角色表ID，文件表ID等',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='系统权限表\r\n\r\n基础表（菜单表，角色表，文件表及其他需要权限控制的表）每新增一个记录，此表同时插入一条对应记录，如\r\nsys_menu表加入一条记录，此处需要对应加入  类型 menu 的 r_id 为menu id的记录';

-- ----------------------------
--  Table structure for `sys_perm_type`
-- ----------------------------
DROP TABLE IF EXISTS `sys_perm_type`;
CREATE TABLE `sys_perm_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '权限类型',
  `r_table` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型对应的基础表，如sys_menu,sys_role,sys_file等',
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '类型标题',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT '类型注释说明',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='权限类型对照表';

-- ----------------------------
--  Table structure for `sys_role`
-- ----------------------------
DROP TABLE IF EXISTS `sys_role`;
CREATE TABLE `sys_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `pid` int(11) DEFAULT '0',
  `status` tinyint(4) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `scope` tinyint(4) DEFAULT '0' COMMENT '部门数据权限范围\r\n0.全部数据权限 \r\n1.部门数据权限\r\n2.部门及以下数据权限\r\n3.仅本人数据权限\r\n4.自定数据权限\r\n\r\n当为自定义数据权限4 时，角色权限会在sys_role_perm里写入对应的部门权限，这里部门也抽象成一种权限和角色一样，其他情况会在代码里sql直接进行处理\r\n\r\n数据权限\r\n_在实际开发中，需要设置用户只能查看哪些部门的数据，这种情况一般称为数据权限。_',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `listorder` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_role_perm`
-- ----------------------------
DROP TABLE IF EXISTS `sys_role_perm`;
CREATE TABLE `sys_role_perm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `perm_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `perm_id` (`perm_id`),
  CONSTRAINT `sys_role_perm_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `sys_role` (`id`),
  CONSTRAINT `sys_role_perm_ibfk_2` FOREIGN KEY (`perm_id`) REFERENCES `sys_perm` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user`;
CREATE TABLE `sys_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `tel` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `sex` smallint(1) DEFAULT NULL,
  `last_login_ip` varchar(16) DEFAULT NULL,
  `last_login_time` int(11) DEFAULT NULL,
  `create_time` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `listorder` int(11) DEFAULT '1000',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user_dept`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_dept`;
CREATE TABLE `sys_user_dept` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dept_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sys_user_dept_ibfk_1` (`user_id`),
  KEY `sys_user_dept_ibfk_2` (`dept_id`),
  CONSTRAINT `sys_user_dept_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `sys_user_dept_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `sys_dept` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user_role`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_role`;
CREATE TABLE `sys_user_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `role_id` (`role_id`),
  CONSTRAINT `sys_user_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `sys_user` (`id`),
  CONSTRAINT `sys_user_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `sys_role` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `sys_user_token`
-- ----------------------------
DROP TABLE IF EXISTS `sys_user_token`;
CREATE TABLE `sys_user_token` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `user_id` bigint(20) NOT NULL,
  `token` varchar(100) NOT NULL COMMENT 'token',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间',
  `create_by` varchar(50) DEFAULT NULL COMMENT '创建人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `last_update_by` varchar(50) DEFAULT NULL COMMENT '更新人',
  `last_update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户Token';

-- ----------------------------
--  Table structure for `upload_tbl`
-- ----------------------------
DROP TABLE IF EXISTS `upload_tbl`;
CREATE TABLE `upload_tbl` (
  `identify` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `idinfo` varchar(255) DEFAULT NULL,
  `bankinfo` varchar(255) DEFAULT NULL,
  `check` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
--  Procedure definition for `getChildLst`
-- ----------------------------
DROP FUNCTION IF EXISTS `getChildLst`;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `getChildLst`(`rootId` int) RETURNS varchar(1000) CHARSET utf8
BEGIN 
DECLARE sTemp VARCHAR(1000); 

DECLARE sTempChd VARCHAR(1000); 

 SET sTemp = '$'; 
 SET sTempChd =cast(rootId as CHAR); 
 
 WHILE sTempChd is not null DO 
   SET sTemp = concat(sTemp,',',sTempChd); 
    SELECT group_concat(Id) INTO sTempChd FROM sys_menu where FIND_IN_SET(pid,sTempChd)>0; 
 END WHILE; 
  RETURN sTemp; 
END
;;
DELIMITER ;

-- ----------------------------
--  Records 
-- ----------------------------
INSERT INTO `article` VALUES ('1','hello','po','288','2020-04-04 23:14:09'), ('2','wordl','qq','8000','2020-04-04 23:14:25');
INSERT INTO `keys` VALUES ('1','0','oocwo8cs88g4c8w8c08ow00ss844cc4osko0s0ks','10','1','0',NULL,'1551173554'), ('2','0','00kgsog84kooc44kgwkwccow48kggc48s4gcwwcg','0','1','0',NULL,'1551173554');
INSERT INTO `sys_dept` VALUES ('1','0','长城','','99','1'), ('2','0','黄河','','99','1'), ('3','1','敦煌','','99','1'), ('4','1','玉门关','','99','1');
INSERT INTO `sys_menu` VALUES ('1','0','Sys','/sys','Layout','0','系统管理','sysset2','/sys/menu','0','1','','99',NULL,NULL), ('2','1','SysMenu','/sys/menu','sys/menu/index','1','菜单管理','menu1','','0','1','','80',NULL,NULL), ('3','1','SysRole','/sys/role','sys/role/index','1','角色管理','role','','0','1','','99',NULL,NULL), ('4','1','SysUser','/sys/user','sys/user/index','1','用户管理','user','','0','1','','99',NULL,NULL), ('5','0','Sysx','/sysx','Layout','0','测试菜单','github','/sysx/xiangjun','0','1','','100',NULL,NULL), ('6','2','','/sys/menu/menus/post','','2','添加','','','0','1','','90',NULL,NULL), ('7','2','','/sys/menu/menus/put','','2','编辑','','','0','1','','95',NULL,NULL), ('8','2','','/sys/menu/menus/delete','','2','删除','','','0','1','','99',NULL,NULL), ('9','2','','/sys/menu/menus/get','','2','查看','','','0','1','','80',NULL,NULL), ('10','5','SysxXiangjun','/sysx/xiangjun','xiangjun/index','1','vue课堂测试','form','','0','1','','95',NULL,NULL), ('11','5','SysxUploadimg','/sysx/uploadimg','uploadimg/index','1','上传证件照','yidong','','0','1','','100',NULL,NULL), ('12','1','SysIcon','/sys/icon','svg-icons/index','1','图标管理','icon','','0','1','','100',NULL,NULL), ('13','3','','/sys/role/roles/get','','2','查看','','','0','1','','90',NULL,NULL), ('14','3','','/sys/role/roles/post','','2','添加','','','0','1','','91',NULL,NULL), ('15','3','','/sys/role/roles/put','','2','编辑','','','0','1','','92',NULL,NULL), ('16','3','','/sys/role/roles/delete','','2','删除','','','0','1','','101',NULL,NULL), ('17','4','','/sys/user/users/get','','2','查看','','','0','1','','96',NULL,NULL), ('18','4','','/sys/user/users/post','','2','添加','','','0','1','','97',NULL,NULL), ('19','4','','/sys/user/users/put','','2','编辑','','','0','1','','99',NULL,NULL), ('20','4','','/sys/user/users/delete','','2','删除','','','0','1','','100',NULL,NULL), ('21','3','','/sys/role/saveroleperm/post','','2','角色授权','','','0','1','','120',NULL,NULL), ('23','1','SysDept','/sys/dept','sys/dept/index','1','部门管理','dept','','0','1','','85',NULL,NULL), ('24','23','','/sys/dept/depts/get','','2','查看','','','0','1','','99',NULL,NULL), ('25','23','','/sys/dept/depts/post','','2','添加','','','0','1','','100',NULL,NULL), ('26','23','','/sys/dept/depts/put','','2','编辑','','','0','1','','102',NULL,NULL), ('27','23','','/sys/dept/depts/delete','','2','删除','','','0','1','','104',NULL,NULL), ('28','1','SysLog','/sys/log','sys/log/index','1','系统日志','log','','0','1','','101',NULL,NULL), ('29','28','','/sys/log/logs/get','','2','查看','','','0','1','','99',NULL,NULL);
INSERT INTO `sys_perm` VALUES ('1','role','1'), ('2','menu','1'), ('3','menu','2'), ('4','menu','3'), ('5','menu','4'), ('6','menu','5'), ('7','menu','6'), ('8','menu','7'), ('9','menu','8'), ('10','menu','9'), ('11','menu','10'), ('12','menu','11'), ('13','menu','12'), ('14','menu','13'), ('15','menu','14'), ('16','menu','15'), ('17','menu','16'), ('18','menu','17'), ('19','menu','18'), ('20','menu','19'), ('21','menu','20'), ('27','menu','21'), ('29','menu','23'), ('30','menu','24'), ('31','menu','25'), ('32','menu','26'), ('33','menu','27'), ('34','dept','1'), ('35','dept','2'), ('36','dept','3'), ('37','dept','4'), ('38','role','2'), ('39','menu','28'), ('40','menu','29');
INSERT INTO `sys_perm_type` VALUES ('1','role','sys_role','角色类',NULL), ('2','menu','sys_menu','菜单类',NULL), ('3','file','sys_file','文件类',NULL), ('4','dept','sys_dept','部门类',NULL);
INSERT INTO `sys_role` VALUES ('1','超级管理员','0','1','拥有网站最高管理员权限！','0','1329633709','1329633709','1'), ('2','test','0','1','','4','1584524771',NULL,'99');
INSERT INTO `sys_role_perm` VALUES ('1','1','1'), ('2','1','2'), ('3','1','3'), ('4','1','4'), ('5','1','5'), ('6','1','6'), ('7','1','7'), ('8','1','8'), ('9','1','9'), ('10','1','10'), ('11','1','11'), ('12','1','12'), ('13','1','13'), ('14','1','14'), ('15','1','15'), ('16','1','16'), ('17','1','17'), ('18','1','18'), ('19','1','19'), ('20','1','20'), ('21','1','21'), ('30','1','27'), ('38','1','29'), ('39','1','30'), ('40','1','31'), ('41','1','32'), ('42','1','33'), ('43','1','34'), ('44','1','35'), ('45','1','36'), ('46','1','37'), ('47','1','38'), ('48','2','2'), ('49','2','5'), ('50','2','18'), ('51','2','19'), ('52','2','20'), ('53','2','21'), ('57','2','35'), ('58','2','37'), ('59','2','36'), ('60','1','39'), ('61','1','40');
INSERT INTO `sys_user` VALUES ('1','admin','21232f297a57a5a743894a0e4a801fc3','admin','111@gmail.com','https://avatars0.githubusercontent.com/u/428884?s=460&v=4','0','127.0.0.1','1493103488','1487868050','1','1'), ('2','qq','026a4f42edc4e5016daa1f0a263242ee','','49727546@qq.com','https://portrait.gitee.com/uploads/avatars/user/1599/4797475_emacle_1583807883.png',NULL,NULL,NULL,'1554800129','1','1002'), ('3','editor','5aee9dbd2a188839105073571bee1b1f','','','',NULL,'',NULL,'1554803362','1','1003');
INSERT INTO `sys_user_role` VALUES ('1','1','1'), ('2','2','2');
