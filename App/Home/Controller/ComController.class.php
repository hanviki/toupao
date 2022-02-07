<?php

/**
 * 版    本：1.0.0
 * 功能说明：后台公用控制器。
 *
 * */

namespace Home\Controller;
use Common\Controller\BaseController;
use Think\Auth;

header('Content-Type:text/html;charset=utf-8');

class ComController extends BaseController {

    public $USER;

    public function _initialize() {
        if (!C("COOKIE_SALT")) {
            $this->error('请配置COOKIE_SALT信息');
        }
        /**
         * 不需要登录控制器
         */
        if (in_array(CONTROLLER_NAME, array("Login"))) {
            return true;
        }
        //检测是否登录
        $flag = $this->check_login();
        $url = U("login/index");
        if (!$flag) {
            header("Location: {$url}");
            exit(0);
        }

        $UID = $this->USER['user_id'];
        $user = member(intval($UID));
        $this->assign('user', $user);
    }

    public function check_login() {
        session_start();
        $flag = false;
        $salt = C("COOKIE_SALT");
        $ip = get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $auth = cookie('auth');
        $user_id = session('user_id');
        if ($user_id) {
            $user = M('judges')->where(array('user_id' => $user_id))->find();

            if ($user) {
                if ($auth == password($user_id . $user['user_name'] . $ip . $ua . $salt)) {
                    $flag = true;
                    $this->USER = $user;
                }
            }
        }
        return $flag;
    }
    
    public function now_round_applicant_list($round_id, $judgetype, $quota_type) {
        $prefix = C('DB_PREFIX');
        $wherelist[$prefix.'rounddetail.round_id'] = $round_id ;
        $wherelist[$prefix.'applicant.quota_log']=$quota_type;
        if ($judgetype == '1') {
            $wherelist[$prefix.'rounddetail.applicant_status'] = 0;//学科组未通过
        } else {
            $wherelist[$prefix.'rounddetail.applicant_status'] = 3;//评审委员会未通过
        }
        $vote_roundapplicanlist = M('rounddetail')->field("{$prefix}rounddetail.rounddetail_id,{$prefix}rounddetail.round_id,{$prefix}rounddetail.employee_id,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log")->where($wherelist)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_rounddetail.select_total desc,qw_applicant.ordernumber asc')->select();
        return $vote_roundapplicanlist;
    }
    
    public function already_passed_list($vote_id, $judgetype, $quota_type) {
        $prefix = C('DB_PREFIX');
        if ($judgetype == '1') {//返回学科组已经通过的人数
            $yijingtongguowhere['qw_rounddetail.applicant_status'] = '2';//学科组通过
        } else {//返回评审委员会已经通过的人数
            $yijingtongguowhere['qw_rounddetail.applicant_status'] = '5';//评审委员会通过
        }
        $yijingtongguowhere['qw_applicant.vote_id'] = $vote_id;
        $yijingtongguowhere['qw_applicant.quota_log'] = $quota_type;
        $applicantlistAll = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.quota_log,{$prefix}applicant.vote_id")->where($yijingtongguowhere)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->group('qw_rounddetail.employee_id')->select();
        $result = array();
        foreach ($applicantlistAll as $value) {
            $result['' . $value['applicant_id']] = $value['employee_id'];
        }
        return $result;
    }
    public function selected_in_current_round_list($vote_id, $round_id, $judgetype, $quota_type, $selectedList) {
        $prefix = C('DB_PREFIX');
        $wherelist[$prefix.'rounddetail.round_id'] = $round_id ;
        $wherelist[$prefix.'applicant.quota_log']=$quota_type;
        if ($judgetype == '1') {
            $wherelist[$prefix.'rounddetail.applicant_status'] = 0;//学科组未通过
        } else {
            $wherelist[$prefix.'rounddetail.applicant_status'] = 3;//评审委员会未通过
        }
        $wherelist[$prefix.'rounddetail.rounddetail_id'] = array('in', $selectedList);
        $vote_roundapplicanlist = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log")->where($wherelist)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_rounddetail.select_total desc,qw_applicant.ordernumber asc')->select();
        //echo ('current_round_list result:'.count($vote_roundapplicanlist)."of list:".$selectedList."\n");
        $result = array();
        foreach ($vote_roundapplicanlist as $value) {
            $result['' . $value['applicant_id']] = $value['employee_id'];
        }
        return $result;
    }
    
}
