<?php

/**
 * 版    本：1.0.0
 * 功能说明：后台首页控制器。
 *
 * */

namespace Home\Controller;

header('Content-Type:text/html;charset=utf-8');

class IndexController extends ComController
{
    /* 从excel表格第几行读入 */
    const ROW_START = 2;
    
    //按照导入顺序导出2018617
    public function orderdao() {
        $vote_id = intval($_REQUEST['vote_id']);
        $xlsName = '按照导入顺序导出学科组评审结果'; //导出数据名称
        prepare_final_export_orderdao_result($xlsName, $vote_id, '1');
        die('1');
    }
    
    //按轮次票数导出2018617
    public function ballotdao() {
        $vote_id = intval($_REQUEST['vote_id']);
        $xlsName = '按票数导出学科组评审结果'; //导出数据名称
        prepare_final_export_ballotdao_result($xlsName, $vote_id, '1');
        die('1');
    }

    public function keshiballotdao() {
        $vote_id = intval($_REQUEST['vote_id']);
        $xlsName = '按科室和票数导出学科组评审结果'; //导出数据名称
        prepare_final_export_keshi_ballotdao_result($xlsName, $vote_id, '1');
        die('1');
    }

    //导出学科组评审最终结果
    public function xuekezu() {
        $vote_id = intval($_REQUEST['vote_id']);
        $xlsName = '学科组评审结果'; //导出数据名称
        prepare_final_export_final_result($xlsName, $vote_id, '1');
        die('1');
    }

    public function weiyuanhui() {
        $vote_id = intval($_REQUEST['vote_id']);
        $xlsName = '医院评审委员会评审结果'; //导出数据名称
        prepare_final_export_final_result($xlsName, $vote_id, '2');
        die('1');
    }

    //导出医院评审最终结果
    public function pingshenjieguo() {
        $vote_id = intval($_REQUEST['vote_id']);
        $xlsName = '最终评审结果'; //导出数据名称
        prepare_final_export_final_result($xlsName, $vote_id, '2', true);
        die('1');
    }

    public function exportRoundResult() {
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id
        prepare_and_export_round_data($round_id, $vote_id, '1');
        die();
    }

    //导出本轮所有评委投票信息
    public function exportVoteDetail() {
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id
        prepare_and_export_round_data($round_id, $vote_id, '2');
        die();
    }

    //投票列表
    public function index($sid = 0) {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }

        $article = M('vote');
        $prefix = C('DB_PREFIX');
        $vote_name = isset($_GET['vote_name']) ? htmlentities($_GET['vote_name']) : '';
        $voteStatus = isset($_GET['vote_status']) ? htmlentities($_GET['vote_status']) : '';
        $where = '1 = 1 ';
        if ($vote_name) {
            $where .= "and {$prefix}vote.vote_name like '%{$vote_name}%' ";
        }
        if ($voteStatus) {
            if($voteStatus == '0'){//表示开启
                $where .= "and {$prefix}vote.end_t = 0 ";
            }else 
            if($voteStatus == '1'){//表示结束
                $where .= "and {$prefix}vote.end_t <> 0 ";
            }
            $voteStatusass = $voteStatus;
        }else{
            $voteStatusass = '0';
            $where .= "and {$prefix}vote.end_t = 0 ";
        }
        //默认按照id降序
        $orderby = "vote_id desc";

        //获取组别分类
        $category = M('category')->field('category_id,category_name')->select();
        $this->assign('category', $category);
        $list = $article->field("{$prefix}vote.*,{$prefix}category.category_name")->where($where)->order($orderby)->join("{$prefix}category ON {$prefix}category.category_id = {$prefix}vote.category_id")->select();

        $this->assign('list', $list);
        $this->assign('voteStatusass', $voteStatusass);
        $this->display();
    }

    //投票轮次信息查询
    public function view($vote_id = 0, $round_id = 0, $rounddetail_id = 0) {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $prefix = C('DB_PREFIX');
        //查询当前投票信息轮次信息
        $nowvoteinfowhere['qw_voteround.vote_id'] = $vote_id;
        $nowvoteinfowhere['qw_voteround.round_id'] = $round_id;
        $votelunciinfo = M('voteround')->field("{$prefix}voteround.*,{$prefix}vote.*")->join("{$prefix}vote ON {$prefix}voteround.vote_id = {$prefix}vote.vote_id")->where($nowvoteinfowhere)->find();
        $this->assign('votelunciinfo', $votelunciinfo);
        $judgetype = $votelunciinfo['judgetype_id'];
        $this->assign('judgetype', $judgetype);

        $this->assign('getaid', $vote_id);
        $this->assign('getnid', $round_id);

        //如果当前轮次是学院评审第一次且未启动的状态
        if(($judgetype == '2') && ($votelunciinfo['round'] == '1') && ($votelunciinfo['round_status'] == '1')){
            $ispsfirst = '1';
        }else{
            $ispsfirst = '0';
        }
        $this->assign('ispsfirst',$ispsfirst);
        //查询当前投票信息
        $vote_id_where['vote_id'] = $vote_id;
        $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();

        // 准备指标分组的列表信息；
        $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,' . ($judgetype == 1 ? "apply_total as apply_total" : "subject_limit as apply_total") . ',' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->order('ordernumber asc')->where($vote_id_where)->select();

        // 准备员工的职称数据
        $applicant_where['vote_id'] = $vote_id;
        $applicantArr = M('applicant')->where($applicant_where)->order('applicant_id asc')->select();
        $app = [];
        foreach ($applicantArr as $key => $value) {
            if (isset($app[$value['employee_id']])){
                $app[$value['employee_id']][] = $value;
            }else {
                $app[$value['employee_id']] = array($value);
            }
        }

        foreach ($voteApplySetTable as $quota_type_idx => $quota_type_value) {
            $vote_roundapplicanlist = array();
            $wherelist = '1 = 1 ';
            $wherelist .= "and {$prefix}rounddetail.round_id = {$round_id} ";
            $wherelist .= "and {$prefix}applicant.quota_log = '{$quota_type_value['quota_log']}' ";
            //根据票数排名查询        
            $list = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log")->where($wherelist)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_applicant.ordernumber asc')->select();

            //去除双职称重复的名字
            $vote_roundapplicanlist = $this->remove_duplicate($list);

            foreach ($vote_roundapplicanlist as $roundapplicankey => $roundapplicanvalue) {
                //循环查找同一个人不同职称

                $dobule_applicant_where['vote_id'] = $vote_id;
                $dobule_applicant_where['employee_id'] = $roundapplicanvalue['employee_id'];
                $dobule_applicantArr = $app[$roundapplicanvalue['employee_id']];
                if (count($dobule_applicantArr) > 1) {
                    foreach ($dobule_applicantArr as $m => $n) {
                        //查询当前职位该轮次是否存在及返回信息
                        $first_round_details = _applicant_have_in($round_id, $n['applicant_id']);

                        $idarr['rounddetail_id'] = $first_round_details['rounddetail_id'];
                        $idarr['applicant_id'] = $first_round_details['applicant_id'];
                        $idarr['apply_title'] = $first_round_details['apply_title'];
                        $idarr['select_total'] = $first_round_details['select_total'];
                        $idarr['applicant_status'] = $first_round_details['applicant_status'];

                        //判断双职称是否已经通过评审
                        if ($first_round_details['is_notnowround'] == '1') {
                            $idarr['is_belong_to'] = '2';//表示不是该轮次的人员
                            $is_pass_pingsheng = _applicant_pass_info($first_round_details['applicant_status'], $judgetype);
                            if ($is_pass_pingsheng['passed'] == '10') {
                                $idarr['is_passed'] = '10';//表示已经通过的职称  
                            } else {
                                $idarr['is_passed'] = '12';//表示未通过的职称 
                            }
                        } else {
                            $idarr['is_passed'] = '11';//表示是该轮次可操作的人员
                            $idarr['is_belong_to'] = '1';//表示是该轮次的人员
                        }
                        $vote_roundapplicanlist[$roundapplicankey]['myson'][] = $idarr;
                        //判断哪个子类投票数最多,将票数多的排在最前面
                        if ($m == 1) {
                            if ($vote_roundapplicanlist[$roundapplicankey]['myson'][0]['select_total'] < $idarr['select_total']) {
                                $vote_roundapplicanlist[$roundapplicankey]['myson'][1] = $vote_roundapplicanlist[$roundapplicankey]['myson'][0];
                                $vote_roundapplicanlist[$roundapplicankey]['myson'][0] = $idarr;
                            }
                        }
                    }
                }
            }
            //排序
            $rating = array();
            foreach ($vote_roundapplicanlist as $tmpIdx => $person) {
                $rating[$tmpIdx] = $this->_calc_round_detail_bubble_weight($person);
            }
            array_multisort($rating, SORT_DESC, $vote_roundapplicanlist);
            $voteApplySetTable[$quota_type_idx]['list'] = $vote_roundapplicanlist;
        }
        $this->assign('voteApplySetTable', $voteApplySetTable);

        if ($rounddetail_id) { //查询某个人的投票信息
            $whereapplist['rounddetail_id'] = $rounddetail_id;
            $shengqingren = M('rounddetail')->where($whereapplist)->find(); //查找申请人信息
            $this->assign('shengqingren', $shengqingren);
            $whereshen['qw_votedetail.rounddetail_id'] = $rounddetail_id;
            $whereshen['qw_votedetail.is_toup'] = '0';
            $shenlist = M('votedetail')->field("{$prefix}votedetail.*,{$prefix}rounddetail.rounddetail_id")->where($whereshen)->join("{$prefix}rounddetail ON {$prefix}votedetail.rounddetail_id = {$prefix}rounddetail.rounddetail_id")->select();
            $this->assign('shenlist', $shenlist);
        }
        $this->display();
    }

    //投票轮次筛选
    public function lunci($vote_id = 0, $round_id = 0) {
        $prefix = C('DB_PREFIX');
        //判断是否是管理员登录
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $this->assign('getaid', $vote_id);
        $this->assign('getnid', $round_id);
        //查询当前投票信息轮次信息
        $nowvoteinfowhere['qw_voteround.vote_id'] = $vote_id;
        $nowvoteinfowhere['qw_voteround.round_id'] = $round_id;
        $votelunciinfo = M('voteround')->field("{$prefix}voteround.*,{$prefix}vote.*")->join("{$prefix}vote ON {$prefix}voteround.vote_id = {$prefix}vote.vote_id")->where($nowvoteinfowhere)->find();
        $this->assign('votelunciinfo', $votelunciinfo);

        //开始查询
        //查询当前投票信息
        $vote_id_where['vote_id'] = $vote_id;
        $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();

        //查询出当前轮次信息
        $round_info_where['round_id'] = $round_id;
        $round_now_info = M('voteround')->where($round_info_where)->find();
        $judgetype = $round_now_info['judgetype_id'];//当前轮次的评审类型
        $this->assign('judgetype', $judgetype);

        //查询当前已激活的评委数量
        $jihuopingweiwhere['user_status'] = '1';
        $jihuopingweiwhere['judgetype_id'] = $judgetype;
        if ('1' == $judgetype) {
            $jihuopingweiwhere['category_id'] = $vote_info['category_id'];
        }
        $jihuopwsltotal = M('judges')->where($jihuopingweiwhere)->select();
        $this->assign('jihuopwsltotal', count($jihuopwsltotal));

        //查询当前已经投票的评委数量
        $have_yitou = 0;
        foreach ($jihuopwsltotal as $jihuopingweikey => $jihuopingweivalue) {
            $whereistjtp['round_id'] = $round_id;
            $whereistjtp['judge_id'] = $jihuopingweivalue['user_id'];
            $whereistjtp['is_toup'] = '0';
            $istijiaotoupiao = M('votedetail')->where($whereistjtp)->find();
            if (!empty($istijiaotoupiao)) {
                $have_yitou = $have_yitou + 1;
            }
        }
        $this->assign('have_yitou', $have_yitou);


        //获取提示信息
        $jieguoinfofirstinfo = $this->get_applicant_backinfo($vote_id, $round_id, "", '3');
        $this->assign('tishijieguo', $jieguoinfofirstinfo);

        // 准备指标分组的列表信息；
        $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,' . ($judgetype == 1 ? "apply_total as apply_total" : "subject_limit as apply_total") . ',' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->order('ordernumber asc')->where($vote_id_where)->select();

        // 准备员工的职称数据
        $applicant_where['vote_id'] = $vote_id;
        $applicantArr = M('applicant')->where($applicant_where)->order('applicant_id asc')->select();
        $app = [];
        foreach ($applicantArr as $key => $value) {
            if (isset($app[$value['employee_id']])){
                $app[$value['employee_id']][] = $value;
            }else {
                $app[$value['employee_id']] = array($value);
            }
        }

        //准备本轮职称数据
        $roundWhere['vote_id'] = $vote_id;
        $roundDetailTbl = M('rounddetail')->field('round_id, select_total, applicant_status, applicant_id')->where($roundWhere)->select();
        $rounds = [];
        foreach ($roundDetailTbl as $key => $value) {
            if (isset($rounds[$value['applicant_id']])){
                $rounds[$value['applicant_id']][] = $value;
            }else {
                $rounds[$value['applicant_id']] = array($value);
            }
        }

        foreach ($voteApplySetTable as $quota_type_idx => $quota_type_value) {
            $vote_roundapplicanlist = array();
            $wherelist = '1 = 1 ';
            $wherelist .= "and {$prefix}rounddetail.round_id = {$round_id} ";
            $wherelist .= "and {$prefix}applicant.quota_log = '{$quota_type_value['quota_log']}' ";
            if ($judgetype == '1') {
                $wherelist .= "and {$prefix}rounddetail.applicant_status = 0 ";//学科组未通过
            } else {
                $wherelist .= "and {$prefix}rounddetail.applicant_status = 3 ";//评审委员会未通过
            }
            if (IS_POST) {//筛选查询 
                $pxtype = $_POST['pxtype'];
                $this->assign('pxtype', $pxtype);
                //筛选类型
                if ($pxtype == '1') {//票数筛选
                    $piaoshu = $_POST['piaoshu'];
                    if (empty($piaoshu)) {
                        echo "<script>alert('票数未填写!');window.history.back(-1);</script>";
                        exit();
                    }
                    $this->assign('piaoshu', $piaoshu);
                }
                if ($pxtype == '2') {//名次筛选
                    $pmnum = $_POST['pmnum'];
                    if (empty($pmnum)) {
                        echo "<script>alert('前几名数量未填写!');window.history.back(-1);</script>";
                        exit();
                    }
                    $this->assign('pmnum', $pmnum);
                }
                if ($pxtype == '3') {
                    $vote_roundapplicanlist = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log")->where($wherelist)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_applicant.ordernumber asc')->select();
                }
            }
            //如果没有筛选结果
            if (empty($vote_roundapplicanlist)) {
                $vote_roundapplicanlist = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log")->where($wherelist)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_rounddetail.select_total desc,qw_applicant.ordernumber asc')->select();
            }

            //去除双职称重复的名字
            $vote_roundapplicanlist = $this->remove_duplicate($vote_roundapplicanlist);

            //获取当前轮次已投票评委总数        
            $wherevotedetail['round_id'] = $round_id;
            $wherevotedetail['is_toup'] = '0';
            $toupiaototal = M('votedetail')->where($wherevotedetail)->group('judge_id')->select();

            $havetongguoapplicant = 0;
            foreach ($vote_roundapplicanlist as $roundapplicanlistkey => $roundapplicanlistvalue) {
                $is_dobule_applicant_pass = is_dobule_pass_new($app, $rounds, $roundapplicanlistvalue['employee_id'], $judgetype);
                if (!$is_dobule_applicant_pass) {
                    if ($roundapplicanlistvalue['quota_log'] == $quota_type_value['quota_log']) {
                        $havetongguoapplicant = intval($havetongguoapplicant) + 1;
                    }
                }
            }

            //获取当前类型已经通过的人数 修改总人数和还需要选择的人数
            $yitongguorenshu = $this->have_tongguo($vote_id, $judgetype, $quota_type_value['quota_log']);
            if (intval($yitongguorenshu) > 0) { //减去通过的人数
                $voteApplySetTable[$quota_type_idx]['apply_total'] = intval($quota_type_value['apply_total']) - intval($yitongguorenshu);
                $voteApplySetTable[$quota_type_idx]['limit_num'] = intval($quota_type_value['limit_num']) - intval($yitongguorenshu);
            }
            if (intval($voteApplySetTable[$quota_type_idx]['limit_num']) < 0) {
                $voteApplySetTable[$quota_type_idx]['renshutixing'] = count($vote_roundapplicanlist) . '人中已经超出' . abs(intval($voteApplySetTable[$quota_type_idx]['limit_num'])) . '人';
            } else {
                $voteApplySetTable[$quota_type_idx]['renshutixing'] = count($vote_roundapplicanlist) . '人中选出' . min($voteApplySetTable[$quota_type_idx]['limit_num'], intval($havetongguoapplicant)) . '人';
            }

            foreach ($vote_roundapplicanlist as $roundapplicankey => $roundapplicanvalue) {
                //获取当前投票数是否超过三分之二
                if (intval($roundapplicanvalue['select_total']) / count($toupiaototal) < 2 / 3) {
                    $vote_roundapplicanlist[$roundapplicankey]['issanfener'] = '1'; //表示未超过2/3
                } else {
                    $vote_roundapplicanlist[$roundapplicankey]['issanfener'] = '2'; //表示超过2/3
                }
                $vote_roundapplicanlist[$roundapplicankey]['myson'] = array();

                //循环查找同一个人不同职称
                $dobule_applicant_where['employee_id'] = $roundapplicanvalue['employee_id'];
                $dobule_applicant_where['vote_id'] = $vote_id;
                $dobule_applicantArr = $app[$roundapplicanvalue['employee_id']];
                if (count($dobule_applicantArr) > 1) {
                    foreach ($dobule_applicantArr as $m => $n) {
                        //查询当前职位该轮次是否存在及返回信息
                        $first_round_details = _applicant_have_in($round_id, $n['applicant_id']);

                        $idarr['rounddetail_id'] = $first_round_details['rounddetail_id'];
                        $idarr['applicant_id'] = $first_round_details['applicant_id'];
                        $idarr['apply_title'] = $first_round_details['apply_title'];
                        $idarr['select_total'] = $first_round_details['select_total'];
                        $idarr['applicant_status'] = $first_round_details['applicant_status'];

                        //判断双职称是否已经通过评审
                        if ($first_round_details['is_notnowround'] == '1') {
                            $idarr['is_belong_to'] = '2';//表示不是该轮次的人员
                            $idarr['issanfener'] = '1'; //表示未超过2/3
                            $is_pass_pingsheng = _applicant_pass_info($first_round_details['applicant_status'], $judgetype);
                            if ($is_pass_pingsheng['passed'] == '10') {
                                $idarr['is_passed'] = '10';//表示已经通过的职称  
                            } else {
                                $idarr['is_passed'] = '12';//表示未通过的职称 
                            }
                        } else {
                            $idarr['is_belong_to'] = '1';//表示是该轮次的人员
                            if ($idarr['applicant_status'] == '2' || $idarr['applicant_status'] == '5') {
                                $idarr['is_passed'] = '10';//表示该轮次已通过的人员
                            } else {
                                $idarr['is_passed'] = '11';//表示是该轮次的人员
                            }
                            //获取当前投票数是否超过三分之二
                            if (intval($first_round_details['select_total']) / count($toupiaototal) < 2 / 3) {
                                $idarr['issanfener'] = '1'; //表示未超过2/3
                            } else {
                                $idarr['issanfener'] = '2'; //表示超过2/3
                            }
                        }

                        $vote_roundapplicanlist[$roundapplicankey]['myson'][] = $idarr;
                        //判断哪个子类投票数最多,将票数多的排在最前面
                        if ($m == 1) {
                            if ($vote_roundapplicanlist[$roundapplicankey]['myson'][0]['select_total'] < $idarr['select_total']) {
                                $vote_roundapplicanlist[$roundapplicankey]['myson'][1] = $vote_roundapplicanlist[$roundapplicankey]['myson'][0];
                                $vote_roundapplicanlist[$roundapplicankey]['myson'][0] = $idarr;
                            }
                        }
                    }
                }
            }
            //排序
            $rating = array();
            foreach ($vote_roundapplicanlist as $tmpIdx => $person) {
                $rating[$tmpIdx] = $this->_calc_round_query_bubble_weight($person);
            }
            array_multisort($rating, SORT_DESC, $vote_roundapplicanlist);
            $voteApplySetTable[$quota_type_idx]['list'] = $vote_roundapplicanlist;
        }
        $this->assign('voteApplySetTable', $voteApplySetTable);
        $this->display();
    }

    /**
     * 轮次筛选排序
     * @param type $person
     * @return type
     */
    private function _calc_round_query_bubble_weight($person) {
        $weight = 0;
        $vote_info = $this->_calc_person_vote_number($person);
        $weight = intval($vote_info['max']) * 1000 + intval($vote_info['total']);
        return $weight;
    }


    /**
     *
     * @param type $person
     * @return type
     */
    private function _calc_person_vote_number($person) {
//        $voteMax = 0;
//        $voteTotal = 0;
        $passed = false;
        if (2 == count($person['myson'])) {
            // double title
            $voteMax = max($person['myson'][0]['is_belong_to'] == '1' ? $person['myson'][0]['select_total'] : 0,
                $person['myson'][1]['is_belong_to'] == '1' ? $person['myson'][1]['select_total'] : 0);
            $voteTotal = ($person['myson'][0]['is_belong_to'] == '1' ? $person['myson'][0]['select_total'] : 0)
                + ($person['myson'][1]['is_belong_to'] == '1' ? $person['myson'][1]['select_total'] : 0);
            foreach ($person['myson'] as $key => $value) {
                if ($value['is_belong_to'] == '1' && intval($value['applicant_status']) % 3 == 2) { //表示通过
                    $passed = true;
                    break;
                }
            }
        } else {
            // single title
            $voteMax = intval($person['select_total']);
            $voteTotal = intval($person['select_total']);
            if (intval($person['applicant_status']) % 3 == 2) {//表示通过
                $passed = true;
            }
        }
        return array("max" => $voteMax, "total" => $voteTotal, "passed" => $passed);
    }

    /**
     * 轮次详情界面查询
     * @param type $person
     * @return type
     */
    private function _calc_round_detail_bubble_weight($person) {
        $base = 1000 - $person['ordernumber'];
        $vote_weight = 0;
        $vote_info = $this->_calc_person_vote_number($person);
        if ($vote_info['passed']) {
            $vote_weight += 5000000;
        }
        $vote_weight += intval($vote_info['max']) * 50000 + intval($vote_info['total'] * 1000);
        return $base + $vote_weight;
    }

    //二维数组去重复
    function remove_duplicate($array) {
        $result = array();
        foreach ($array as $key => $value) {
            $has = false;
            foreach ($result as $val) {
                if ($val['employee_id'] == $value['employee_id']) {
                    $has = true;
                    break;
                }
            }
            if (!$has)
                $result[] = $value;
        }
        return $result;
    }

    //投票管理列表
    public function guanli($vote_id = 0) {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $anum = M('voteround');
        $prefix = C('DB_PREFIX');
        $where = '1 = 1 ';
        $this->assign('vote_id', $vote_id);
        if ($vote_id) {
            $where .= "and {$prefix}voteround.vote_id = {$vote_id}";
        }
        //默认按照时间降序
        $orderby = "addtime desc";
        //获取栏目分类
        $category = M('category')->field('category_id,category_name')->select();
        $this->assign('category', $category); //导航
        $list = $anum->field("{$prefix}voteround.*,{$prefix}vote.*,{$prefix}judgetype.judge_type")->where($where)->order($orderby)->join("{$prefix}vote ON {$prefix}voteround.vote_id = {$prefix}vote.vote_id")->join("{$prefix}judgetype ON {$prefix}voteround.judgetype_id = {$prefix}judgetype.judgetype_id")->order('qw_voteround.judgetype_id desc,qw_voteround.round desc')->select();

        $this->assign('list', $list);
        $this->display();
    }

    public function add() {
        $category = M('category')->field('category_id,category_name')->select();
        $this->assign('category', $category); //导航
        $this->display('form');
    }

    public function update() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $data['category_id'] = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $data['vote_name'] = isset($_POST['vote_name']) ? $_POST['vote_name'] : false;
        $data['professional_id'] = isset($_POST['professional_id']) ? $_POST['professional_id'] : '1';
        $data['add_t'] = time();
        $data['start_t'] = time();
        $data['end_t'] = 0;
        //查询投票标题是否重复
        $datawh['vote_name'] = $data['vote_name'];
        $toutitle = M('vote')->where($datawh)->find();
        if (!empty($toutitle)) {
            $this->error('标题重复，请重新填写', '/Home/Index/add');
        }
        //根据组别id查询组别名称
        $wherezb['category_id'] = $data['category_id'];
        $zbname = M('category')->where($wherezb)->find();
        $data['category_name'] = $zbname['category_name'];

        //根据职称id查询职称名称
        $wherezhi['professional_id'] = $data['professional_id'];
        $zhichengname = M('professional')->where($wherezhi)->find();
        $data['professional_name'] = $zhichengname['professional_name'];

        $vote_id = M('vote')->data($data)->add();
        //投票轮次表加入一条信息
        $datalc['vote_id'] = $vote_id;
        $datalc['round'] = 1;
        $datalc['addtime'] = time();
        $datalc['judgetype_id'] = 1;
        $lunci = M('voteround')->data($datalc)->add();
        if ($vote_id) {
            //导入申报名单
            $info = array('ordernumber', 'employee_id', 'office_name', 'applicant_name', 'apply_title', 'quota_log', 'apply_total', 'subject_limit', 'committee_limit', 'applicant_style', 'is_quota');
            $this->data_import($lunci, $vote_id, $filePath, self::ROW_START, $info, 'qw_applicant', M('applicant'));

            //查找当前轮次的申请人信息在轮次详情中加入该轮次信息的所有application
            $daupwhe['vote_id'] = $vote_id;
            $daupwhe['round_id'] = $lunci;
            $shenArr = M('applicant')->where($daupwhe)->select();
            foreach ($shenArr as $m => $n) {
                $datadet['round_id'] = $n['round_id'];
                $datadet['applicant_id'] = $n['applicant_id'];
                $datadet['applicant_name'] = $n['applicant_name'];
                $datadet['apply_title'] = $n['apply_title'];
                $datadet['employee_id'] = $n['employee_id'];
                $rounddetailadd = M('rounddetail')->data($datadet)->add(); //轮次详情信息添加   
            }
            $this->success('恭喜！投票新增成功！', '/Home/Index/index');
        } else {
            $this->error('抱歉，未知错误！');
        }
    }

    /* Excel表中数据导入数据库功能 */

    public function data_import($lunci, $vote_id, $filePath, $rowstart, $info, $table, $model) {
        header("Content-type: text/html; charset=utf-8");
        set_time_limit(0);
        if (IS_POST) {
            if (isset($_FILES["import"]) && ($_FILES["import"]["error"] == 0)) {
                $filePath = $_FILES["import"]["tmp_name"];
                /* 数据导入数据库 */
                $result = importExcel($lunci, $vote_id, $filePath, $rowstart, $info, $table, $model, null);
                if (!$result) {
                    //删除已添加的数据信息
                    $votedelwhere['vote_id'] = $vote_id;
                    $datelvote = M('vote')->where($votedelwhere)->delete();
                    $this->error('抱歉，文件格式不对！');
                    die;
                }
            } else {
                $this->success('请选择导入的文件！');
                die;
            }
        }
    }

    /* 对应组别查询职称 */

    public function getzc() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $category_id = I('get.category_id');
        if ($category_id) {
            $where['category_id'] = $category_id;
            $list_json = M('professional')->where($where)->select();
            echo json_encode($list_json);
        }
    }

    //启动投票
    public function open($vote_id = 0, $round_id = 0, $vote_sort_type = 0) {
        $uwhere['uid'] = $_SESSION['uid'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $vote_id = intval($vote_id);
        $round_id = intval($round_id);
        $where['round_id'] = $round_id;
        //如果当前轮次是学科组，则自动关闭上一轮学科组     
        $danqianlc = M('voteround')->where($where)->find();
        if ($danqianlc['judgetype_id'] == 1) {
            //查找上一轮信息
            $czsyl['judgetype_id'] = '1';
            $czsyl['round_status'] = '0'; //0表示启动状态
            $czsyl['round'] = intval($danqianlc['round']) - 1;
            $sylxin = M('voteround')->where($czsyl)->find();
            if (!empty($sylxin)) {//关闭上一轮
                $datagb['round_status'] = '2'; //关闭
                $wherebefore['round_id'] = $sylxin['round_id'];
                $sylclose = M('voteround')->where($wherebefore)->save($datagb);
            }
        }
        //如果当前轮次是评审委员会第1轮，则判断当前组的学科组是否完成     
        if ($danqianlc['judgetype_id'] == 2 && $danqianlc['round'] == 1) {
            //查找学科组是否完成
            $whereispw['vote_id'] = $vote_id;
            $whereispw['judgetype_id'] = '1';
            $shifouqid = M('voteround')->where($whereispw)->select();
            foreach ($shifouqid as $key => $value) {
                if ($value['round_status'] == '0' || $value['round_status'] == '1' || $value['round_status'] == '3') {//如果还有轮次正在进行中
                    $this->error('抱歉，还有投票正在进行中！');
                    die();
                }
            }
        }
        //如果当前轮次是评审委员会第n轮，则关闭上一轮评审委员会评审
        if ($danqianlc['judgetype_id'] == 2 && intval($danqianlc['round']) > 1) {
            //查找上一轮信息
            $czsyl['judgetype_id'] = '2';
            $czsyl['round_status'] = '0';
            $czsyl['round'] = intval($danqianlc['round']) - 1;
            $sylxin = M('voteround')->where($czsyl)->find();
            if (!empty($sylxin)) {//关闭上一轮
                $datagb['round_status'] = '2'; //关闭
                $wherebefore['round_id'] = $sylxin['round_id'];
                $sylclose = M('voteround')->where($wherebefore)->save($datagb);
            }
        }
        $datadangqian['round_status'] = '0'; //开启当前选择的轮次
        $datadangqian['vote_sort_type'] = $vote_sort_type;
        $upopen = M('voteround')->where($where)->save($datadangqian);
        $this->success('操作成功！');
    }

    //终止当前轮次的投票
    public function closeroundnow($vote_id = 0, $round_id = 0) {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $vote_id = intval($vote_id);
        $round_id = intval($round_id);

        $where['round_id'] = $round_id;
        $data['round_status'] = '3'; //终止本轮投票,后台评审开始
        $upopen = M('voteround')->where($where)->save($data);
        $this->success('操作成功！');
    }

    //开启下一轮学科组评审投票
    public function opennext() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $apenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false; //获取所有轮次申请人详情rounddetail_id        
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id
        $where['round_id'] = $round_id;
        $now = M('voteround')->where($where)->find();

        if (!empty($apenids)) {
            //加一条轮次信息
            $datalc['vote_id'] = $vote_id;
            $datalc['round'] = intval($now['round']) + 1; //加一轮投票
            $datalc['addtime'] = time();
            $datalc['judgetype_id'] = 1;
            $lunci = M('voteround')->data($datalc)->add();
            //关闭当前轮次
            $datagb['round_status'] = '2'; //关闭
            $wherebefore['round_id'] = $now['round_id'];
            $sylclose = M('voteround')->where($wherebefore)->save($datagb);
            //将挑选的轮次详情申请人更换当前轮次
            foreach ($apenids as $key => $value) {
                $dangqianroundetail['rounddetail_id'] = $value;
                $rounddetailfind = M('rounddetail')->where($dangqianroundetail)->find();
                //更改当前轮次的申请人状态为进入下一轮学科组
                $rounddetailstatus['applicant_status'] = '1';
                $roundwhere['rounddetail_id'] = $value;
                $rounddetailupdate = M('rounddetail')->where($roundwhere)->save($rounddetailstatus);

                //更改所选申请人的轮次状态
                $datenow['round_id'] = $lunci;
                $daupwhe['applicant_id'] = $rounddetailfind['applicant_id'];
                $upshen = M('applicant')->where($daupwhe)->save($datenow);

                //添加所选人到轮次详情记录            
                $datadet['round_id'] = $lunci;
                $datadet['applicant_id'] = $rounddetailfind['applicant_id'];
                $datadet['applicant_name'] = $rounddetailfind['applicant_name'];
                $datadet['apply_title'] = $rounddetailfind['apply_title'];
                $datadet['employee_id'] = $rounddetailfind['employee_id'];
                $datadet['previous_round_id'] = $round_id;
                $datadet['previous_select_total'] = $rounddetailfind['select_total'];
                $rounddetailadd = M('rounddetail')->data($datadet)->add(); //轮次详情信息添加
            }
        }
        $urlnow = '/Home/Index/guanli/vote_id/' . $vote_id;
        $this->success('操作成功', $urlnow);
    }

    //加入医院评审委员会投票
    public function yiyuanps() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $apenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false; //获取所有申请人id
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id

        if (!empty($apenids)) {
            //查找第一轮医院评审委员会信息
            $datalc['vote_id'] = $vote_id;
            $datalc['round'] = 1;
            $datalc['judgetype_id'] = 2;
            $lunciinfo = M('voteround')->where($datalc)->find();
            $lunci = $lunciinfo['round_id'];
            if (empty($lunci)) {//如果没有则先加一条
                $dataadd['vote_id'] = $vote_id;
                $dataadd['round'] = 1; //评审委员会第一轮                  
                $dataadd['addtime'] = time();
                $dataadd['judgetype_id'] = 2;
                $lunci = M('voteround')->data($dataadd)->add();
            }
            //将挑选的申请人更换当前轮次
            foreach ($apenids as $key => $value) {
                $dangqianroundetail['rounddetail_id'] = $value;
                $rounddetailfind = M('rounddetail')->where($dangqianroundetail)->find();
                //更改当前轮次的申请人状态为进入医院评审委员会
                $rounddetailstatus['applicant_status'] = '2';
                $roundwhere['rounddetail_id'] = $value;
                $rounddetailupdate = M('rounddetail')->where($roundwhere)->save($rounddetailstatus);

                //更改所选申请人的轮次状态
                $datenow['round_id'] = $lunci;
                $daupwhe['applicant_id'] = $rounddetailfind['applicant_id'];
                $upshen = M('applicant')->where($daupwhe)->save($datenow);

                //添加所选人到轮次详情记录            
                $datadet['round_id'] = $lunci;
                $datadet['applicant_id'] = $rounddetailfind['applicant_id'];
                $datadet['applicant_name'] = $rounddetailfind['applicant_name'];
                $datadet['apply_title'] = $rounddetailfind['apply_title'];
                $datadet['employee_id'] = $rounddetailfind['employee_id'];
                $datadet['applicant_status'] = '3';
                $datadet['previous_round_id'] = $round_id;
                $datadet['previous_select_total'] = $rounddetailfind['select_total'];
                $rounddetailadd = M('rounddetail')->data($datadet)->add(); //轮次详情信息添加
            }

            //如果当前轮次没有人了，则关闭当前轮次
            $dangqianapp['round_id'] = $round_id;
            $gbmrapp = M('applicant')->where($dangqianapp)->find();
            if (empty($gbmrapp)) {
                //关闭当前轮次
                $datagb['round_status'] = '2'; //关闭        
                $nowclose = M('voteround')->where($dangqianapp)->save($datagb);
            }
        }
        //$urlnow = '/Home/Index/guanli/vote_id/' . $vote_id;
        $this->success('操作成功');
    }


    //学科组评审完成
    public function xkzwc() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $apenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false; //获取所有申请人id
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id  

        $where['round_id'] = $round_id;
        $now = M('voteround')->where($where)->find();
        if (!empty($apenids)) {//修改状态
            foreach ($apenids as $key => $value) {
                //查询当前轮次修改状态            
                $updateappstatus['applicant_status'] = '2';
                $updatewhere['rounddetail_id'] = $value;
                $updatenowround = M('rounddetail')->where($updatewhere)->save($updateappstatus);
            }
        }

        //关闭当前轮次 
        $dangqianapp['round_id'] = $round_id;
        $datagb['round_status'] = '2'; //关闭        
        $nowclose = M('voteround')->where($dangqianapp)->save($datagb);

        $this->check_and_set_vote_end_time($vote_id); // 检查并设置投票截止时间

        $urlnow = '/Home/Index/guanli/vote_id/' . $vote_id;
        $this->success('操作成功', $urlnow);
    }

    //开启下一轮评审委员会
    public function pswyh() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $apenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false; //获取所有申请人id
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id

        //轮次信息
        $where['round_id'] = $round_id;
        $now = M('voteround')->where($where)->find();
        //加一条轮次信息
        if (!empty($apenids)) {
            $datalc['vote_id'] = $vote_id;
            $datalc['round'] = intval($now['round']) + 1; //加一轮投票       
            $datalc['addtime'] = time();
            $datalc['judgetype_id'] = 2;
            $lunci = M('voteround')->data($datalc)->add();

            //将挑选的申请人更换当前轮次
            foreach ($apenids as $key => $value) {
                $dangqianroundetail['rounddetail_id'] = $value;
                $rounddetailfind = M('rounddetail')->where($dangqianroundetail)->find();
                //更改当前轮次的申请人状态为进入下一轮医院评审委员会
                $rounddetailstatus['applicant_status'] = '4';
                $roundwhere['rounddetail_id'] = $value;
                $rounddetailupdate = M('rounddetail')->where($roundwhere)->save($rounddetailstatus);

                //更改所选申请人的轮次状态
                $datenow['round_id'] = $lunci;
                $daupwhe['applicant_id'] = $rounddetailfind['applicant_id'];
                $upshen = M('applicant')->where($daupwhe)->save($datenow);

                //添加所选人到轮次详情记录            
                $datadet['round_id'] = $lunci;
                $datadet['applicant_id'] = $rounddetailfind['applicant_id'];
                $datadet['applicant_name'] = $rounddetailfind['applicant_name'];
                $datadet['apply_title'] = $rounddetailfind['apply_title'];
                $datadet['employee_id'] = $rounddetailfind['employee_id'];
                $datadet['applicant_status'] = '3';
                $datadet['previous_round_id'] = $round_id;
                $datadet['previous_select_total'] = $rounddetailfind['select_total'];
                $rounddetailadd = M('rounddetail')->data($datadet)->add(); //轮次详情信息添加
            }

            //关闭当前轮次
            $datagb['round_status'] = '2'; //关闭
            $wherebefore['round_id'] = $now['round_id'];
            $sylclose = M('voteround')->where($wherebefore)->save($datagb);

        }
        $urlnow = '/Home/Index/guanli/vote_id/' . $vote_id;
        $this->success('操作成功', $urlnow);
    }

    //医院评审完成
    public function yypswc() {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $apenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false; //获取所有申请人id
        $vote_id = intval($_REQUEST['vote_id']);
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id

        $where['round_id'] = $round_id;
        $now = M('voteround')->where($where)->find();
        if (!empty($apenids)) {//修改状态
            foreach ($apenids as $key => $value) {
                //查询当前轮次修改状态            
                $updateappstatus['applicant_status'] = '5';
                $updatewhere['rounddetail_id'] = $value;
                $updatenowround = M('rounddetail')->where($updatewhere)->save($updateappstatus);
            }
        }
        //关闭当前轮次
        $datagb['round_status'] = '2'; //关闭
        $wherebefore['round_id'] = $now['round_id'];
        $sylclose = M('voteround')->where($wherebefore)->save($datagb);

        $this->check_and_set_vote_end_time($vote_id); // 检查并设置投票截止时间

        $urlnow = '/Home/Index/guanli/vote_id/' . $vote_id;
        $this->success('操作成功', $urlnow);
    }

    //医院评审通过
    public function tgyyps() {
        $apenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false; //获取所有申请人id
        $round_id = intval($_REQUEST['round_id']); //获取当前轮次id
        $vote_id = intval($_REQUEST['vote_id']);

        foreach ($apenids as $key => $value) {//修改当前人员的最终状态（通过与否）
            //更改所选申请人的轮次状态
            $dangqianroundetail['rounddetail_id'] = $value;
            $rounddetailfind = M('rounddetail')->where($dangqianroundetail)->find();
            //更改当前轮次的申请人状态为通过医院评审
            $rounddetailstatus['applicant_status'] = '5';
            $roundwhere['rounddetail_id'] = $value;
            $rounddetailupdate = M('rounddetail')->where($roundwhere)->save($rounddetailstatus);
        }
        //$urlnow = '/Home/Index/guanli/vote_id/' . $vote_id;
        $this->success('操作成功');
    }

    //提交时候判断是否超过人数。超过人数返回false
    public function isoverpeople($vote_id, $round_id, $check_applicantid) {
        $backjson = array('result' => 0);// success;
        $backinfo = $this->get_applicant_backinfo($vote_id, $round_id, $check_applicantid, '1');
        if ($backinfo) {
            $backjson = $backinfo;//提示超过的信息            
        }
        echo json_encode($backjson);
    }

    //触发点击时候查询下当前人数
    public function get_applicant_backinfo($vote_id, $round_id, $check_applicantid, $ispanduan = 0) {
        //通过vote_id查询出当前已经通过的人员数量
        $prefix = C('DB_PREFIX');
        //查询当前投票信息
        $vote_id_where['vote_id'] = $vote_id;
        $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();

        //查询出当前轮次信息
        $round_info_where['round_id'] = $round_id;
        $round_now_info = M('voteround')->where($round_info_where)->find();
        $judgetype = $round_now_info['judgetype_id'];//当前轮次的评审类型

        //获取勾选人数组合数组       
        $rounddetailidsarr = explode(',', $check_applicantid);

        // 准备指标分组的列表信息；
        $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->order('ordernumber asc')->where($vote_id_where)->select();

        foreach ($voteApplySetTable as $quota_type_idx => $quota_type_value) {
            //获取所有参加本轮次的人员信息
            $now_round_allapplicant = $this->now_round_applicant_list($round_id, $judgetype, $quota_type_value['quota_log']);
            // 获取已通过的 applicantId->employeeId的数组
            $passed_id_map = $this->already_passed_list($vote_id, $judgetype, $quota_type_value['quota_log']);
            // 通过勾选的roundDetailId获取已勾选的applicantId->employeeId数组
            $selected_id_map = $this->selected_in_current_round_list($vote_id, $round_id, $judgetype, $quota_type_value['quota_log'], $check_applicantid);

            $passed_id_map = array_unique($passed_id_map);// 去除重复的employeeId
            $selected_id_map = array_unique($selected_id_map);// 去除重复的employeeId
            $now_round_allapplicant = remove_duplicate($now_round_allapplicant);// 去除重复的employeeId

            $passed_count = count($passed_id_map); // 计算已通过的employeeId个数（通过多少人）
            $intersect_count = count(array_intersect($passed_id_map, $selected_id_map));//计算已通过的employeeId有多少出现在当前勾选的人员中
            $final_select_count = count($selected_id_map) - $intersect_count;//实际已勾选的人数（排除已通过的重复人员）

            // 至此，已经拿到关键计算数据；后面的逻辑和原来的计算一样
            $lastlimit = min((intval($quota_type_value['limit_num']) - intval($passed_count)), count($now_round_allapplicant));//获取当前轮次可选值的最小值

            $chaoguorenshu = intval($lastlimit) - intval($final_select_count); //最后可选的人数（排除勾选的值）

            //当通过人数为0的时候不显示
            if (intval($passed_count) == 0) {
                $tongguorenshutixing = '';
            } else {
                $tongguorenshutixing = '，已通过' . intval($passed_count) . '人';
            }

            if (intval($chaoguorenshu) > 0) {
                $chaoguorenshu = $chaoguorenshu;
                $lasttishiinfo = '当前' . $quota_type_value['quota_log'] . '类型' . $tongguorenshutixing . '，已勾选' . intval($final_select_count) . '人，可选' . $chaoguorenshu . '人';
                $backinfo[] = array('result' => 1, 'detail_msg' => $lasttishiinfo);//提醒可选的人数
            } else if (intval($chaoguorenshu) == 0) {
                $chaoguorenshu = '0';
                $lasttishiinfo = '当前' . $quota_type_value['quota_log'] . '类型' . $tongguorenshutixing . '，已勾选' . intval($final_select_count) . '人，可选' . $chaoguorenshu . '人';
                $backinfo[] = array('result' => 0);//表示是选择人数刚好
            } else {
                $chaoguorenshu = abs(intval($chaoguorenshu));
                $lasttishiinfo = '当前' . $quota_type_value['quota_log'] . '类型' . $tongguorenshutixing . '，已勾选' . intval($final_select_count) . '人，已超过' . $chaoguorenshu . '人';
                $backinfo[] = array('result' => 2, 'detail_msg' => $lasttishiinfo);//强制判断已超过不能提交
            }
            $jieguoinfo[] = $lasttishiinfo;
        }
        $jieguoinfolast = array();
        $jieguoinfolast[]['lastinfo'] = implode('<br/>', $jieguoinfo);

        if ($ispanduan == 3) {//表示投票界面第一次提醒值
            $jieguoinfofirstinfo = implode('<br/>', $jieguoinfo);
            return $jieguoinfofirstinfo;
            exit();
        }
        if ($ispanduan == 1) {//表示提交时的提醒
            foreach ($backinfo as $tixinkey => $tixinvalue) {
                if ($tixinvalue['result'] == '2') { //人数超出提醒，结束循环
                    return $tixinvalue;
                    break;
                }
                if ($tixinvalue['result'] == '1') {//人数减少提醒，结束循环
                    return $tixinvalue;
                    break;
                }
            }
            return $tixinvalue;
        }

        echo json_encode($jieguoinfolast);
    }

    //查询当前投票已经通过的人数，不限制轮次
    public function have_tongguo($vote_id, $judgetype, $quota_type) {
        $prefix = C('DB_PREFIX');
        if ($judgetype == '1') {//返回学科组已经通过的人数
            $yijingtongguowhere['qw_rounddetail.applicant_status'] = '2';//学科组通过
        } else {//返回评审委员会已经通过的人数
            $yijingtongguowhere['qw_rounddetail.applicant_status'] = '5';//评审委员会通过
        }
        $yijingtongguowhere['qw_applicant.vote_id'] = $vote_id;
        $yijingtongguowhere['qw_applicant.quota_log'] = $quota_type;
        $applicantlistAll = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.quota_log,{$prefix}applicant.vote_id")->where($yijingtongguowhere)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->group('qw_rounddetail.employee_id')->select();
        if (empty($applicantlistAll)) {
            return 0;
        } else {
            return count($applicantlistAll);
        }
    }

    private function check_and_set_vote_end_time($vote_id) {
        $roundwhere['round_status'] = array('neq', 2);; //关闭
        $roundwhere['vote_id'] = $vote_id;
        $notClosedRound = M('voteround')->where($roundwhere)->select();
        if (0 == count($notClosedRound)) {
            $this->set_vote_end_time($vote_id);
        }
    }

    private function set_vote_end_time($vote_id) {
        $vote_updateendtime['end_t'] = time();
        $vote_updatewhere['vote_id'] = $vote_id;
        M('vote')->where($vote_updatewhere)->save($vote_updateendtime);
    }
}
