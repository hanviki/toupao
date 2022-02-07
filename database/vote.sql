/*
Navicat MySQL Data Transfer

Source Server         : PHP
Source Server Version : 50617
Source Host           : localhost:3306
Source Database       : vote

Target Server Type    : MYSQL
Target Server Version : 50617
File Encoding         : 65001

Date: 2018-01-15 15:46:39
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `qw_applicant`
-- ----------------------------
DROP TABLE IF EXISTS `qw_applicant`;
CREATE TABLE `qw_applicant` (
  `applicant_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '申请人ID',
  `vote_id` int(11) NOT NULL DEFAULT '0' COMMENT '对应的投票ID',
  `round_id` int(11) NOT NULL COMMENT '关联轮次ID，对应查询出当前是什么状态以及轮次以及评审阶段',
  `ordernumber` int(11) DEFAULT NULL COMMENT '导入序号',
  `employee_id` varchar(255) NOT NULL DEFAULT '0' COMMENT '职工编码',
  `office_name` varchar(255) NOT NULL COMMENT '科室',
  `applicant_name` varchar(255) NOT NULL COMMENT '姓名',
  `apply_title` varchar(255) NOT NULL COMMENT '申报职称',
  `apply_total` int(11) NOT NULL COMMENT '总人数',
  `subject_limit` int(11) NOT NULL COMMENT '学科组选出人数',
  `committee_limit` int(11) NOT NULL COMMENT '评审委员选择票数',
  `is_quota` varchar(255) DEFAULT '' COMMENT '是否占指标（0表示占指标1表示不占指标2表示援疆指标3表示援非指标）',
  `quota_log` varchar(255) DEFAULT '' COMMENT '是否占指标说明',
  `applicant_style` varchar(255) DEFAULT NULL COMMENT '申请类型',
  PRIMARY KEY (`applicant_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='投票申请人信息';

-- ----------------------------
-- Records of qw_applicant
-- ----------------------------

-- ----------------------------
-- Table structure for `qw_category`
-- ----------------------------
DROP TABLE IF EXISTS `qw_category`;
CREATE TABLE `qw_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL COMMENT '组别名称',
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COMMENT='组别表';

-- ----------------------------
-- Records of qw_category
-- ----------------------------
INSERT INTO `qw_category` VALUES ('1', '手术组(高级)');
INSERT INTO `qw_category` VALUES ('2', '非手术组(高级)');
INSERT INTO `qw_category` VALUES ('3', '护技药及其他组(高级)');
INSERT INTO `qw_category` VALUES ('4', '医师组(中初级)');
INSERT INTO `qw_category` VALUES ('5', '护理组(中初级)');
INSERT INTO `qw_category` VALUES ('6', '药技及其他组(中初级)');

-- ----------------------------
-- Table structure for `qw_judges`
-- ----------------------------
DROP TABLE IF EXISTS `qw_judges`;
CREATE TABLE `qw_judges` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(225) NOT NULL COMMENT '用户名',
  `password` varchar(32) NOT NULL COMMENT '密码',
  `user_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0表示评委1表示管理员',
  `category_id` int(11) NOT NULL DEFAULT '1' COMMENT '组别ID',
  `judgetype_id` int(11) NOT NULL COMMENT '评审类型Id',
  `user_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否激活0表示未激活1表示激活2表示失效',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user` (`user_name`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='管理员和评委表';

-- ----------------------------
-- Records of qw_judges
-- ----------------------------
INSERT INTO `qw_judges` VALUES ('1', 'admin', '123', '1', '0', '0', '1');

-- ----------------------------
-- Table structure for `qw_judgetype`
-- ----------------------------
DROP TABLE IF EXISTS `qw_judgetype`;
CREATE TABLE `qw_judgetype` (
  `judgetype_id` int(11) NOT NULL AUTO_INCREMENT,
  `judge_type` varchar(255) NOT NULL COMMENT '类型名称',
  PRIMARY KEY (`judgetype_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='评委类型表';

-- ----------------------------
-- Records of qw_judgetype
-- ----------------------------
INSERT INTO `qw_judgetype` VALUES ('1', '学科组评审');
INSERT INTO `qw_judgetype` VALUES ('2', '医院评审委员会评审');

-- ----------------------------
-- Table structure for `qw_log`
-- ----------------------------
DROP TABLE IF EXISTS `qw_log`;
CREATE TABLE `qw_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `operator_name` varchar(100) NOT NULL COMMENT '管理员名称',
  `operation_time` int(10) NOT NULL COMMENT '操作时间',
  `ip` varchar(16) NOT NULL COMMENT 'ip',
  `log` varchar(255) NOT NULL COMMENT '日志记录',
  `round_id` int(11) NOT NULL DEFAULT '0' COMMENT '轮次ID',
  `applicant_id` int(11) NOT NULL DEFAULT '0' COMMENT '申请人ID',
  PRIMARY KEY (`log_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='设置评审通过日志表';

-- ----------------------------
-- Records of qw_log
-- ----------------------------

-- ----------------------------
-- Table structure for `qw_professional`
-- ----------------------------
DROP TABLE IF EXISTS `qw_professional`;
CREATE TABLE `qw_professional` (
  `professional_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL COMMENT '对应组别ID',
  `professional_name` varchar(100) NOT NULL COMMENT '职称名称',
  PRIMARY KEY (`professional_id`),
  KEY `fsid` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of qw_professional
-- ----------------------------
INSERT INTO `qw_professional` VALUES ('1', '1', '正高');
INSERT INTO `qw_professional` VALUES ('2', '1', '副高');
INSERT INTO `qw_professional` VALUES ('3', '2', '正高');
INSERT INTO `qw_professional` VALUES ('4', '2', '副高');
INSERT INTO `qw_professional` VALUES ('5', '3', '正高');
INSERT INTO `qw_professional` VALUES ('6', '3', '副高');
INSERT INTO `qw_professional` VALUES ('7', '4', '在编及培训选留');
INSERT INTO `qw_professional` VALUES ('8', '4', '合同制');
INSERT INTO `qw_professional` VALUES ('9', '5', '在编及培训选留');
INSERT INTO `qw_professional` VALUES ('10', '5', '合同制');
INSERT INTO `qw_professional` VALUES ('11', '6', '在编及培训选留');
INSERT INTO `qw_professional` VALUES ('12', '6', '合同制');

-- ----------------------------
-- Table structure for `qw_rounddetail`
-- ----------------------------
DROP TABLE IF EXISTS `qw_rounddetail`;
CREATE TABLE `qw_rounddetail` (
  `rounddetail_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '轮次详情ID',
  `round_id` int(11) NOT NULL COMMENT '轮次ID（对应votenum的nid）',
  `applicant_id` int(11) NOT NULL COMMENT '申请人ID',
  `employee_id` varchar(255) DEFAULT NULL COMMENT '职工编号',
  `applicant_name` varchar(255) NOT NULL COMMENT '申请人姓名',
  `apply_title` varchar(255) NOT NULL COMMENT '被选择的职位',
  `select_total` int(11) NOT NULL DEFAULT '0' COMMENT '投票总数',
  `applicant_status` int(11) NOT NULL DEFAULT '0' COMMENT '0表示未通过1表示进入下一轮学科组2表示学科组通过进入评审委员会3表示院评委未通过4表示进入院评审下一轮5表示院评审通过',
  `previous_round_id` int(11) NOT NULL DEFAULT '0' COMMENT '上一轮的轮次ID',
  `previous_select_total` int(11) NOT NULL DEFAULT '0' COMMENT '上一轮投票总数',
  PRIMARY KEY (`rounddetail_id`),
  KEY `sid` (`applicant_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='轮次详情记录';

-- ----------------------------
-- Records of qw_rounddetail
-- ----------------------------

-- ----------------------------
-- Table structure for `qw_vote`
-- ----------------------------
DROP TABLE IF EXISTS `qw_vote`;
CREATE TABLE `qw_vote` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '投票ID',
  `category_id` int(11) NOT NULL COMMENT '组别id',
  `category_name` varchar(255) NOT NULL COMMENT '组别',
  `professional_id` int(11) NOT NULL COMMENT '职称ID',
  `professional_name` varchar(255) NOT NULL COMMENT '职称名称',
  `vote_name` varchar(255) NOT NULL COMMENT '投票名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0表示开启1表示关闭',
  `start_t` int(10) unsigned NOT NULL COMMENT '开始时间',
  `end_t` int(10) unsigned NOT NULL COMMENT '结束时间',
  `add_t` int(10) unsigned NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`vote_id`),
  UNIQUE KEY `title` (`vote_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='投票列表';

-- ----------------------------
-- Records of qw_vote
-- ----------------------------

-- ----------------------------
-- Table structure for `qw_votedetail`
-- ----------------------------
DROP TABLE IF EXISTS `qw_votedetail`;
CREATE TABLE `qw_votedetail` (
  `votedetail_id` int(11) NOT NULL AUTO_INCREMENT,
  `round_id` int(11) DEFAULT NULL COMMENT '轮次ID',
  `rounddetail_id` int(11) DEFAULT NULL COMMENT '某人某轮次详情ID',
  `judge_id` int(11) NOT NULL COMMENT '评委ID',
  `judge_name` varchar(255) NOT NULL COMMENT '评委用户名',
  `is_toup` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0表示投票成功2表示保存中',
  PRIMARY KEY (`votedetail_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='投票结果';

-- ----------------------------
-- Records of qw_votedetail
-- ----------------------------

-- ----------------------------
-- Table structure for `qw_voteround`
-- ----------------------------
DROP TABLE IF EXISTS `qw_voteround`;
CREATE TABLE `qw_voteround` (
  `round_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '轮次ID',
  `vote_id` int(11) NOT NULL COMMENT '投票id',
  `addtime` int(10) NOT NULL COMMENT '添加时间',
  `round` int(10) NOT NULL DEFAULT '1' COMMENT '第几轮',
  `judgetype_id` int(11) NOT NULL COMMENT '当前评审类型',
  `round_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0表示投票中1表示未启动2已结束3表示管理员评审中',
  `vote_sort_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '该轮投票评委界面的排序，0表示导入顺序，1表示按票数',
  PRIMARY KEY (`round_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='投票轮流次数记录';

-- ----------------------------
-- Records of qw_voteround
-- ----------------------------
