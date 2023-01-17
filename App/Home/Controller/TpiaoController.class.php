<?php
/**
 * 版    本：1.0.0
 * 功能说明：文章控制器。
 *
 **/

namespace Home\Controller;

class TpiaoController extends ComController
{

    public function index() {
        //根据当前登录的用户判断可投票信息列表
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();

        $article = M('vote');
        $prefix = C('DB_PREFIX');
        $where = '1 = 1 ';
        if ($nowuser['judgetype_id']) {
            $where .= "and {$prefix}voteround.judgetype_id ={$nowuser['judgetype_id']} ";
        }

        //如果是学科评审，则需要限制组别类型，如果是医院评审委员会评审，则不需要限制组别
        if ($nowuser['judgetype_id'] == '1') {
            if ($nowuser['category_id']) {//统一组别学科组评审才能投票
                $where .= "and {$prefix}vote.category_id ={$nowuser['category_id']} ";
            }
        }
        $where .= "and {$prefix}voteround.round_status = 0 ";//表示启动中的投票。

        $list = $article->field("{$prefix}vote.*,{$prefix}voteround.*")->where($where)->join("{$prefix}voteround ON {$prefix}voteround.vote_id = {$prefix}vote.vote_id")->select();
        $this->assign('list', $list);
        $this->display();
    }
	
	public function nowvote() {
		//获取当前登录评委
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        $shenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;//获取所有被勾选的申请人id        
        $round_id = isset($_REQUEST['roundid']) ? $_REQUEST['roundid'] : false;//获取轮次id 
		$havaSaveinfo = array();//表示保存中

		$havaAddinfo = array();//表示需要添加的
		$voteRoundCount = array();


        $selectyitoupiao['judge_id'] = $nowuser['user_id'];
        $selectyitoupiao['round_id'] = $round_id;
        $votedetails = M('votedetail')->where($selectyitoupiao)->select();
        $votes = [];
        foreach ($votedetails as $key => $value) {
            $votes[$value['rounddetail_id']] = $value;
        }

		if ($shenids) {
            foreach ($shenids as $key => $value) {//循环添加投票信息
                //过滤已经投票的
//                $selectyitoupiao['rounddetail_id'] = $value;
//                $selectyitoupiao['judge_id'] = $nowuser['user_id'];
//                $shaiyjtp = M('votedetail')->where($selectyitoupiao)->find();
                $shaiyjtp = $votes[$value];
                if ($shaiyjtp['is_toup'] == '0') {//表示已投票

                } else if ($shaiyjtp['is_toup'] == '2') {//表示保存中
					//获取保存中的id
					$havaSaveinfo[] = $value;
					//当前伦次投票数总计
					$voteRoundCount[] = $value;                    

                } else {//添加投票
					//获取需要添加投票的ID
					$havaAddinfo[] = array('rounddetail_id'=>$value,'round_id'=>$round_id,'judge_id'=>$nowuser['user_id'],'judge_name'=>$nowuser['user_name'],'is_toup'=>'0');      
					//当前伦次投票数总计
					$voteRoundCount[] = $value;
                }
            }
			if(!empty($havaSaveinfo)){
				$havaSaveinfoString = implode(',', $havaSaveinfo);
				$dataupdate['is_toup'] = '0'; //投票成功
				$wherebefore['rounddetail_id'] = array('in',$havaSaveinfoString);
				$wherebefore['judge_id'] = $nowuser['user_id'];
				$updatestatus = M('votedetail')->where($wherebefore)->save($dataupdate);
			}
			if(!empty($havaAddinfo)){
				$havaAddnow = M('votedetail')->addAll($havaAddinfo); //添加投票信息				
			}
			if(!empty($voteRoundCount)){


                $voteRoundCount= $this->getRoundDetailIdByFxh($round_id,$voteRoundCount);

				//修改投票数量值
				$voteRoundCountString = implode(',', $voteRoundCount);
				$addonewhere['rounddetail_id'] = array('in',$voteRoundCountString);
				$rounddetailsaddone = M('rounddetail')->where($addonewhere)->setInc('select_total');
			}
            $this->success('投票成功！');
        } else {
            $this->error('参数错误！');
        }
	}

	public function getVoteNumber($voteRoundCount){
        $voteRoundCountString = implode(',', $voteRoundCount);
        $addonewhere['rounddetail_id'] = array('in',$voteRoundCountString);
        $rounddetailsaddone = M('rounddetail')->field('rounddetail_id')->where($addonewhere)->order('select_total desc')->select();
        return $rounddetailsaddone;
    }

	public function  getReducePerson($round_id){

        $where=array();
        $where['round_id'] = $round_id;
        $reducePersonAll=  M('reducelog')->field('employee_id,cishu')->where($where)->select();//所有已经减分人员
        $reducePerson= [];  //记录 和employee_id 和 次数  减分次数
        foreach ($reducePersonAll as $key => $value) {
            $reducePerson[$value['employee_id']] = $value['cishu'];
        }
        return $reducePerson;
    }

//根据发薪号 获取detail ids
	public function  getRoundDetailIdByFxh($round_id,$voteRoundCount){
        $rangeList2= M('person')->field('employee_id')->select();//待处理加分账号
        $where=array();
        $where['round_id'] = $round_id;
        $personListAll =  M('rounddetail')->field('rounddetail_id,employee_id,select_total')->where($where)->select();//所有被打分人员
        $typePersonAll =M('applicant')->field('employee_id,quota_log')->where($where)->select();//所有被打分人员
        //M()->startTrans();//开启事务
       // M('reducelog')->lock(true).find();
        $reducePersonAll=  M('reducelog')->field('employee_id,cishu')->where($where)->select();//所有已经减分人员

        $where2=array();
        $where2['round_id'] = $round_id;
        $where2['is_toup'] = 0;
        $toup= M('votedetail')->distinct(true)->field('judge_id')->where($where2)->select();//已经投票的评委
        if(count($toup)<=2){
            $voteTotalNum =0;
        }
        else {
            $voteTotalNum = ceil(count($toup) * (2 / 3)); // 投票人总数*2/3
        }
        //dump('投票总数:'+$voteTotalNum);

        $personList = []; //记录 rounddetail_id 和employee_id
        $scoreList= [];  //记录 rounddetail_id 和 select_total
        $typePerson= []; //记录 和employee_id 和 quota_log 占指标类型
      //  $reducePerson= [];  //记录 和employee_id 和 次数  减分次数

        foreach ($typePersonAll as $key => $value) {
            $typePerson[$value['employee_id']] = $value['quota_log'];
        }
//        foreach ($reducePersonAll as $key => $value) {
////            $reducePerson[$value['employee_id']] = $value['cishu'];
////        }

        foreach ($personListAll as $key => $value) {
            $personList[(string)$value['rounddetail_id']] = $value['employee_id'];
            $scoreList[(string)$value['rounddetail_id']] = $value['select_total'];
        }
        $employeeids= array_values($personList); //所有人员发薪号
        //dump($rangeList);
        //dump($voteRoundCount);

        //$_SESSION['hasReduceEmployid']= [];//已经做过减法的
        $rangeList= [];
        foreach ($rangeList2 as  $value5) {
            array_unshift($rangeList,$value5['employee_id']);
        }

        foreach ($rangeList as  $value) { //循环待处理加分账号
            if(in_array($value, $employeeids)) { // 是否本轮次人员
                //dump($value);
                //dump('11111');
                $personDetailids= array_keys($personList,$value);// 获取当前发薪号 对应的所有detailids
                //dump($personDetailids);
                //dump('8888');
                if(count($personDetailids)>1){ //包含两个职称
                    if(in_array($personDetailids[0],$voteRoundCount) ||in_array($personDetailids[1],$voteRoundCount) ){//有其中一个在投票中的
                        if(!in_array($personDetailids[0],$voteRoundCount)){ //第一个不在投票列表
                            //   select_count +1
                            if($scoreList[$personDetailids[0]]<$voteTotalNum) { //当现在的投票数 总2/3  +1
                                array_unshift($voteRoundCount, $personDetailids[0]);
                            }
                        }
                        if(!in_array($personDetailids[1],$voteRoundCount)){ //第2个不在投票列表
                            //   select_count +1
                            if($scoreList[$personDetailids[1]]<$voteTotalNum) { //当现在的投票数 总2/3  +1
                                array_unshift($voteRoundCount, $personDetailids[1]);
                            }
                        }
                    }

                    else{
                         $flag=0;

                        $voteRoundCount=   $this->calcReduce($scoreList,$personDetailids,$voteTotalNum,$voteRoundCount,$personList,$rangeList,$index=0,$flag,$typePerson,$round_id);
                        $voteRoundCount=  $this->calcReduce($scoreList,$personDetailids,$voteTotalNum,$voteRoundCount,$personList,$rangeList,$index=1,$flag,$typePerson,$round_id);
                    }
                }
                else{

                    if(!in_array($personDetailids[0],$voteRoundCount)) { //不在投票列表
                        $flag = 0;
                        $voteRoundCount = $this->calcReduce($scoreList, $personDetailids, $voteTotalNum, $voteRoundCount, $personList, $rangeList, $index = 0, $flag, $typePerson,$round_id);
                    }
                }
            }
        }
        //die('');
        //dump($reducePerson);
        //dump($voteRoundCount);

      //  M()->commit();//事务提交
       return $voteRoundCount;
    }
    //减去一票的计算
    public function  calcReduce($scoreList,$personDetailids,$voteTotalNum,$voteRoundCount,$personList,$rangeList,$index,&$flag,$typePerson,$round_id){
        if($scoreList[$personDetailids[$index]]<$voteTotalNum) { //当现在的投票数 总2/3  +1
            if($flag==0) { // 上一个已经减人，第二个不需要减

                $reduceEmployeeid = '';//减分人员发薪号


               // $rounddetailsaddone= getReducePerson($voteRoundCount);//  按分数多少排序
                foreach ($voteRoundCount as $value3) {// detailid 遍历所有被选选择打分的50个人
                    // $personList[$value3]  //得出发薪号
                    if($typePerson[$personList[$value3]]==$typePerson[$personList[$personDetailids[$index]]]) {// 是否同一类型的占指标

                        $reducePerson =$this->getReducePerson($round_id);
                        if (!in_array($personList[$value3], $rangeList) && !array_key_exists($personList[$value3], $reducePerson)) { //不在待加分列表和已经减分列表
                           // array_unshift($personList[$value3], $_SESSION['hasReduceEmployid']); // 把当前人加入已经减分列表
                           // $aa= array($personList[$value3]=>1);
                         //   array_unshift($aa,$reducePerson);//放入当前减分列表
                            $reducePerson[$personList[$value3]]=1;
                            $reduceEmployeeid=$personList[$value3];

                            $saveinfo = array('employee_id'=>$reduceEmployeeid,'cishu'=>1,'round_id'=>$round_id);
                            M('reducelog')->add($saveinfo); //添加减分人员列表 数据库

                            $detailidReduce = array_keys($personList, $personList[$value3]);// 获取当前发薪号 对应的所有detailids
                            //dump('4444');
                           // //dump($detailidReduce);
                            //dump($reducePerson);
                            if (count($detailidReduce) > 1) { //包含两个职称
                                if (in_array($detailidReduce[0], $voteRoundCount)) { //第一个在投票列表
                                    $key = array_search($detailidReduce[0], $voteRoundCount);
                                    if (isset($key)) {
                                        unset($voteRoundCount[$key]);
                                    }
                                }
                                if (in_array($detailidReduce[1], $voteRoundCount)) { //第2个在投票列表
                                    $key = array_search($detailidReduce[1], $voteRoundCount);
                                    if (isset($key)) {
                                        unset($voteRoundCount[$key]);
                                    }
                                }
                            } else {
                                if (in_array($detailidReduce[0], $voteRoundCount)) { //第一个在投票列表
                                    $key = array_search($detailidReduce[0], $voteRoundCount);
                                    if (isset($key)) {
                                        unset($voteRoundCount[$key]);
                                    }
                                }
                            }
                            break;
                        }

                    }
                }
                if($reduceEmployeeid == '') { //所有的人 都已经做过减法
                    $reducePerson =$this->getReducePerson($round_id);
                    asort($reducePerson); //根据减值 做升序 
                    //$frist=array_shift($reducePerson);
                    foreach($reducePerson as $x=>$x_value){
                        $is_reduce='0';
                        $detailids2 = array_keys($personList, $x);// 获取当前发薪号 对应的所有detailids
                        if (count($detailids2) > 1) { //包含两个职称
                            if (in_array($detailids2[0], $voteRoundCount)) { //第一个在投票列表
                                $key = array_search($detailids2[0], $voteRoundCount);
                                if (isset($key)) {
                                    unset($voteRoundCount[$key]);
                                }
                                $is_reduce='1';
                            }
                            if (in_array($detailids2[1], $voteRoundCount)) { //第2个在投票列表
                                $key = array_search($detailids2[1], $voteRoundCount);
                                if (isset($key)) {
                                    unset($voteRoundCount[$key]);
                                }
                                $is_reduce='1';
                            }
                        } else {
                            if (in_array($detailids2[0], $voteRoundCount)) { //第一个在投票列表
                                $key = array_search($detailids2[0], $voteRoundCount);
                                if (isset($key)) {
                                    unset($voteRoundCount[$key]);
                                }
                                $is_reduce='1';
                            }
                        }
                        if($is_reduce=='1'){ //如果需要减的人 不在当前列表，继续寻找下一个
                          //  $reducePerson[$x] = $reducePerson[$x]+1; // 值相加
                            $reduceEmployeeid= $x;
                            $reducewhere['employee_id'] = $x;
                            $reducewhere['round_id'] = $round_id;

                             M('reducelog')->where($reducewhere)->setInc('cishu');
                            break;
                        }
                    }
                }
                if($reduceEmployeeid != ''){
                    $flag=1;
                }
            }
            array_unshift($voteRoundCount, $personDetailids[$index]); //当前人票数+1
        }
        return $voteRoundCount;
    }

	
    public function nowvote_old() {
        //获取当前登录评委
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        $shenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;//获取所有被勾选的申请人id        
        $round_id = isset($_REQUEST['roundid']) ? $_REQUEST['roundid'] : false;//获取轮次id         

        if ($shenids) {
            foreach ($shenids as $key => $value) {//循环添加投票信息 
                //查询改声请人详细信息
                $rounddetailsinfowhere['rounddetail_id'] = $value;
                $rounddetailsinfo = M('rounddetail')->where($rounddetailsinfowhere)->find();

                //过滤已经投票的
                $selectyitoupiao['rounddetail_id'] = $value;
                $selectyitoupiao['judge_id'] = $nowuser['user_id'];
                $shaiyjtp = M('votedetail')->where($selectyitoupiao)->find();
                if ($shaiyjtp['is_toup'] == '0') {//表示已投票

                } else if ($shaiyjtp['is_toup'] == '2') {//表示保存中
                    $dataupdate['is_toup'] = '0'; //投票成功
                    $wherebefore['rounddetail_id'] = $value;
                    $wherebefore['judge_id'] = $nowuser['user_id'];
                    $updatestatus = M('votedetail')->where($wherebefore)->save($dataupdate);

                    //当前伦次投票数+1
                    $addonewhere['rounddetail_id'] = $value;
                    $rounddetailsaddone = M('rounddetail')->where($addonewhere)->setInc('select_total');

                } else {//添加投票
                    $dataadd['rounddetail_id'] = $value;
                    $dataadd['round_id'] = $round_id;
                    $dataadd['judge_id'] = $nowuser['user_id'];
                    $dataadd['judge_name'] = $nowuser['user_name'];
                    $dataadd['is_toup'] = '0';//添加
                    $votedetail = M('votedetail')->data($dataadd)->add(); //投票详情添加

                    //当前伦次投票数+1
                    $addonewhere['rounddetail_id'] = $value;
                    $rounddetailsaddone = M('rounddetail')->where($addonewhere)->setInc('select_total');

                }
            }
            $this->success('投票成功！');
        } else {
            $this->error('参数错误！');
        }
    }
    
	    //定时临时保存
    public function timebaocun($vote_id, $round_id, $check_applicantid) {
        //获取当前登录评委
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        //获取勾选人数组合数组
        if (empty($check_applicantid)) {
            $shenids = array();
        } else {
            $shenids = explode(',', $check_applicantid);
        } 
        $round_id = $round_id;
		
		//删除该用户所有已经临时保存的信息
		$alllinshibaocunwhere['judge_id'] = $nowuser['user_id'];
		$alllinshibaocunwhere['round_id'] = $round_id;
		$alllinshibaocunwhere['is_toup'] = '2';
		$alllinshibaocun = M('votedetail')->where($alllinshibaocunwhere)->delete();

        $selectyitoupiao['judge_id'] = $nowuser['user_id'];
        $selectyitoupiao['round_id'] = $round_id;
        $votedetails = M('votedetail')->where($selectyitoupiao)->select();
        $votes = [];
        foreach ($votedetails as $key => $value) {
            $votes[$value['rounddetail_id']] = $value;
        }

		$dataList = array();
        if (!empty($shenids)){
		foreach ($shenids as $key => $value) {//循环获取需要添加的值             
			//过滤已经投票的
//			$selectyitoupiao['rounddetail_id'] = $value;
//			$selectyitoupiao['judge_id'] = $nowuser['user_id'];
//			$shaiyjtp = M('votedetail')->where($selectyitoupiao)->find();
            $shaiyjtp = $votes[$value];
			if (($shaiyjtp['is_toup'] == '0')) {//过滤已投票
				} else {//批量获取信息添加保存投票信息
					$dataList[] = array('rounddetail_id'=>$value,'round_id'=>$round_id,'judge_id'=>$nowuser['user_id'],'judge_name'=>$nowuser['user_name'],'is_toup'=>'2');      
				}
			}
        }
		if(!empty($dataList)){			
			$votedetail = M('votedetail')->addAll($dataList); //投票详情添加   
		}
			
        $tixinvalue['result'] == '1';
        echo json_encode($tixinvalue);
    }
	
    //定时临时保存
    public function timebaocunold($vote_id, $round_id, $check_applicantid) {
        //获取当前登录评委
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        //获取勾选人数组合数组
        if (empty($check_applicantid)) {
            $shenids = array();
        } else {
            $shenids = explode(',', $check_applicantid);
        } 
        $round_id = $round_id;
        if (!empty($shenids)){
            //查询出所有已经临时保存的信息
            $alllinshibaocunwhere['judge_id'] = $nowuser['user_id'];
            $alllinshibaocunwhere['round_id'] = $round_id;
            $alllinshibaocunwhere['is_toup'] = '2';
            $alllinshibaocun = M('votedetail')->field('votedetail_id,rounddetail_id')->where($alllinshibaocunwhere)->select();
            foreach ($alllinshibaocun as $m => $n) {
                if (in_array($n['rounddetail_id'], $shenids)) {//查看当前申请值是否已存在

                } else { //如果不存在则删除
                    $delwhere['votedetail_id'] = $n['votedetail_id'];
                    $delvotedetail = M('votedetail')->where($delwhere)->delete();
                }
            }

            foreach ($shenids as $key => $value) {//循环添加投票信息
                //查询改声请人详细信息
                $rounddetailsinfowhere['rounddetail_id'] = $value;
                $rounddetailsinfo = M('rounddetail')->where($rounddetailsinfowhere)->find();
                //过滤已经投票的
                $selectyitoupiao['rounddetail_id'] = $value;
                $selectyitoupiao['judge_id'] = $nowuser['user_id'];
                $shaiyjtp = M('votedetail')->where($selectyitoupiao)->find();
                if (($shaiyjtp['is_toup'] == '0') || ($shaiyjtp['is_toup'] == '2')) {//如果是已投票或者已保存

                } else {//添加保存投票信息
                    $databaoxun['rounddetail_id'] = $value;
                    $databaoxun['round_id'] = $round_id;
                    $databaoxun['judge_id'] = $nowuser['user_id'];
                    $databaoxun['judge_name'] = $nowuser['user_name'];
                    $databaoxun['is_toup'] = '2';//保存中
                    $votedetail = M('votedetail')->data($databaoxun)->add(); //投票详情添加                   
                }
            }
        } else {
            $shenids = array();
            //查询出所有已经临时保存的信息
            $alllinshibaocunwhere['judge_id'] = $nowuser['user_id'];
            $alllinshibaocunwhere['round_id'] = $round_id;
            $alllinshibaocunwhere['is_toup'] = '2';
            $alllinshibaocun = M('votedetail')->field('votedetail_id,rounddetail_id')->where($alllinshibaocunwhere)->select();
            foreach ($alllinshibaocun as $m => $n) {
                if (in_array($n['rounddetail_id'], $shenids)) {//查看当前申请值是否已存在

                } else { //如果不存在则删除
                    $delwhere['votedetail_id'] = $n['votedetail_id'];
                    $delvotedetail = M('votedetail')->where($delwhere)->delete();
                }
            }
        }        
        $tixinvalue['result'] == '1';
        echo json_encode($tixinvalue);
    }
    
	    public function linshi() {
        //获取当前登录评委
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        $shenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;//获取所有被勾选的轮次详情申请id  
        $round_id = isset($_REQUEST['roundid']) ? $_REQUEST['roundid'] : false;
		
		//删除该用户所有已经临时保存的信息
		$alllinshibaocunwhere['judge_id'] = $nowuser['user_id'];
		$alllinshibaocunwhere['round_id'] = $round_id;
		$alllinshibaocunwhere['is_toup'] = '2';
		$alllinshibaocun = M('votedetail')->where($alllinshibaocunwhere)->delete();

        $selectyitoupiao['judge_id'] = $nowuser['user_id'];
        $selectyitoupiao['round_id'] = $round_id;
        $votedetails = M('votedetail')->where($selectyitoupiao)->select();
        $votes = [];
        foreach ($votedetails as $key => $value) {
            $votes[$value['rounddetail_id']] = $value;
        }

		$dataList = array();
        if (!empty($shenids)){
		foreach ($shenids as $key => $value) {//循环获取需要添加的值             
			//过滤已经投票的
//			$selectyitoupiao['rounddetail_id'] = $value;
//			$selectyitoupiao['judge_id'] = $nowuser['user_id'];
//			$shaiyjtp = M('votedetail')->where($selectyitoupiao)->find();
            $shaiyjtp = $votes[$value];
			if (($shaiyjtp['is_toup'] == '0')) {//过滤已投票
				} else {//批量获取信息添加保存投票信息
					$dataList[] = array('rounddetail_id'=>$value,'round_id'=>$round_id,'judge_id'=>$nowuser['user_id'],'judge_name'=>$nowuser['user_name'],'is_toup'=>'2');      
				}
			}
        }
		if(!empty($dataList)){			
			$votedetail = M('votedetail')->addAll($dataList); //投票详情添加   
		}
        $this->success('保存成功！');
    }

    public function linshiold() {
        //获取当前登录评委
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        $shenids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;//获取所有被勾选的轮次详情申请id  
        $round_id = isset($_REQUEST['roundid']) ? $_REQUEST['roundid'] : false;
        if ($shenids) {
            //查询出所有已经临时保存的信息
            $alllinshibaocunwhere['judge_id'] = $nowuser['user_id'];
            $alllinshibaocunwhere['round_id'] = $round_id;
            $alllinshibaocunwhere['is_toup'] = '2';
            $alllinshibaocun = M('votedetail')->field('votedetail_id,rounddetail_id')->where($alllinshibaocunwhere)->select();
            foreach ($alllinshibaocun as $m => $n) {
                if (in_array($n['rounddetail_id'], $shenids)) {//查看当前申请值是否已存在

                } else { //如果不存在则删除
                    $delwhere['votedetail_id'] = $n['votedetail_id'];
                    $delvotedetail = M('votedetail')->where($delwhere)->delete();
                }
            }

            foreach ($shenids as $key => $value) {//循环添加投票信息
                //查询改声请人详细信息
                $rounddetailsinfowhere['rounddetail_id'] = $value;
                $rounddetailsinfo = M('rounddetail')->where($rounddetailsinfowhere)->find();
                //过滤已经投票的
                $selectyitoupiao['rounddetail_id'] = $value;
                $selectyitoupiao['judge_id'] = $nowuser['user_id'];
                $shaiyjtp = M('votedetail')->where($selectyitoupiao)->find();
                if (($shaiyjtp['is_toup'] == '0') || ($shaiyjtp['is_toup'] == '2')) {//如果是已投票或者已保存

                } else {//添加保存投票信息
                    $databaoxun['rounddetail_id'] = $value;
                    $databaoxun['round_id'] = $round_id;
                    $databaoxun['judge_id'] = $nowuser['user_id'];
                    $databaoxun['judge_name'] = $nowuser['user_name'];
                    $databaoxun['is_toup'] = '2';//保存中
                    $votedetail = M('votedetail')->data($databaoxun)->add(); //投票详情添加                   
                }
            }
        } else {
            $shenids = array();
            //查询出所有已经临时保存的信息
            $alllinshibaocunwhere['judge_id'] = $nowuser['user_id'];
            $alllinshibaocunwhere['round_id'] = $round_id;
            $alllinshibaocunwhere['is_toup'] = '2';
            $alllinshibaocun = M('votedetail')->field('votedetail_id,rounddetail_id')->where($alllinshibaocunwhere)->select();
            foreach ($alllinshibaocun as $m => $n) {
                if (in_array($n['rounddetail_id'], $shenids)) {//查看当前申请值是否已存在

                } else { //如果不存在则删除
                    $delwhere['votedetail_id'] = $n['votedetail_id'];
                    $delvotedetail = M('votedetail')->where($delwhere)->delete();
                }
            }
        }
        $this->success('保存成功！');
    }

    public function  userInfo($userAccount ="",$ids=""){
        $this->assign('userAccount', $userAccount);
        $this->assign('ids', $ids);
        $this->display();
    }

    public function toupiao($vote_id = 0, $round_id = 0) {
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        $rounddetail = M('rounddetail');
        $prefix = C('DB_PREFIX');
        $this->assign('round_id', $round_id);
        $this->assign('vote_id', $vote_id);

        //查询当前投票信息轮次信息
        $nowvoteinfowhere['qw_voteround.vote_id'] = $vote_id;
        $nowvoteinfowhere['qw_voteround.round_id'] = $round_id;
        $votelunciinfo = M('voteround')->field("{$prefix}voteround.*,{$prefix}vote.*")->join("{$prefix}vote ON {$prefix}voteround.vote_id = {$prefix}vote.vote_id")->where($nowvoteinfowhere)->find();
        $this->assign('votelunciinfo', $votelunciinfo);

        //查询组别职称
        $voteidwhere['vote_id'] = $vote_id;
        $voteinfo = M('vote')->where($voteidwhere)->find();
        $this->assign('voteinfo', $voteinfo);

        //查询出当前轮次信息
        $round_info_where['round_id'] = $round_id;
        $round_now_info = M('voteround')->where($round_info_where)->find();
        $judgetype = $round_now_info['judgetype_id'];//当前轮次的评审类型
        $round_now = $round_now_info['round'];

        // 准备指标分组的列表信息；
        $vote_id_where['vote_id'] = $vote_id;
        $listtype = M('applicant')->distinct(true)->field('quota_log,apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->order('ordernumber asc')->where($vote_id_where)->select();

        //$listtype = $rounddetail->field("{$prefix}rounddetail.*,{$prefix}voteround.*,{$prefix}applicant.*")->where($where)->join("{$prefix}voteround ON {$prefix}rounddetail.round_id = {$prefix}voteround.round_id")->join("{$prefix}applicant ON {$prefix}rounddetail.applicant_id = {$prefix}applicant.applicant_id")->group('qw_applicant.quota_log')->select();  

        //查询当前人是否已经提交投票
        $whereistjtp['round_id'] = $round_id;
        $whereistjtp['judge_id'] = $nowuser['user_id'];
        $whereistjtp['is_toup'] = '0';
        $istijiaotoupiao = M('votedetail')->where($whereistjtp)->find();
        $this->assign('istijiaotoupiao', $istijiaotoupiao);

        //获取提示信息
        $jieguoinfofirstinfo = $this->toupiaojieguo($vote_id, $round_id, "", '3');
        $this->assign('jieguoinfo', $jieguoinfofirstinfo);

        //准备职称数据
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
        $roundWhere['qw_applicant.vote_id'] = $vote_id;
        $roundDetailTbl = M('rounddetail')->field("{$prefix}rounddetail.round_id, select_total,qw_voteround.round, applicant_status, {$prefix}rounddetail.applicant_id")->where($roundWhere)
            ->join(" {$prefix}applicant on {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id  ")
            ->join("qw_voteround ON qw_voteround.round_id = qw_rounddetail.round_id ")
            ->select();
        $rounds = [];
        foreach ($roundDetailTbl as $key => $value) {
            if (isset($rounds[$value['applicant_id']])){
                $rounds[$value['applicant_id']][] = $value;
            }else {
                $rounds[$value['applicant_id']] = array($value);
            }
        }
        //准备本轮职称数据
        $rwhere["{$prefix}rounddetail.round_id"] = $round_id;
        $rdetailTbl = M('votedetail')->field('applicant_id,is_toup')->where(" {$prefix}votedetail.round_id = {$round_id} AND judge_id = {$nowuser['user_id']}")->join(" {$prefix}rounddetail ON   {$prefix}votedetail.rounddetail_id = {$prefix}rounddetail.rounddetail_id  ")->select();
//        $rdetailTbl = M('votedetail')->field('is_toup')->where("round_id =  {$round_id}")->select();
        $rdetails = [];
        foreach ($rdetailTbl as $key => $value) {
            $rdetails[$value['applicant_id']] = $value;
        }

        foreach ($listtype as $x => $y) {
            $wherelist = "1 = 1 "; //hsc
            $wherelist .= "and {$prefix}rounddetail.round_id = {$round_id} ";
            $wherelist .= "and {$prefix}applicant.quota_log = '{$y['quota_log']}' ";
            $wherelist .= "and {$prefix}voteround.round_status = 0 ";//表示启动中的投票。
            if ($judgetype == '1') {
                $wherelist .= "and {$prefix}rounddetail.applicant_status = 0 ";//表示学科组未通过的投票。
            } else {
                $wherelist .= "and {$prefix}rounddetail.applicant_status = 3 ";//表示评审委员会未通过的投票。
            }

            //  $list = $rounddetail->field("{$prefix}rounddetail.*,{$prefix}voteround.*,{$prefix}applicant.*")->where($wherelist)->join("{$prefix}voteround ON {$prefix}rounddetail.round_id = {$prefix}voteround.round_id")->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->select();
            $list = $rounddetail->field("{$prefix}rounddetail.*,{$prefix}voteround.*,{$prefix}applicant.*")->where($wherelist)->join("{$prefix}voteround ON {$prefix}rounddetail.round_id = {$prefix}voteround.round_id")->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_applicant.ordernumber asc')->select();
         //   $list = $rounddetail->field("{$prefix}rounddetail.*,{$prefix}voteround.*,{$prefix}applicant.*")->join("{$prefix}voteround ON {$prefix}rounddetail.round_id = {$prefix}voteround.round_id")->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->where($wherelist)->order('qw_applicant.ordernumber asc')->select();
            //去除双职称重复的名字
            $list = $this->remove_duplicate($list);

            $havetongguoapplicant = 0;
            foreach ($list as $roundapplicanlistkey => $roundapplicanlistvalue) {
                $is_dobule_applicant_pass = is_dobule_pass_new($app, $rounds, $roundapplicanlistvalue['employee_id'], $judgetype);
                if (!$is_dobule_applicant_pass) {
                    if ($roundapplicanlistvalue['quota_log'] == $y['quota_log']) {
                        $havetongguoapplicant = intval($havetongguoapplicant) + 1;
                    }
                }
            }

            //获取当前类型已经通过的人数 修改总人数和还需要选择的人数
            $yitongguorenshu = $this->have_tongguo($vote_id, $judgetype, $y['quota_log']);
            if (intval($yitongguorenshu) > 0) { //减去通过的人数
                $listtype[$x]['apply_total'] = intval($y['apply_total']) - intval($yitongguorenshu);
                $listtype[$x]['limit_num'] = intval($y['limit_num']) - intval($yitongguorenshu);
            }
            if (intval($listtype[$x]['limit_num']) < 0) {
                $listtype[$x]['renshutixing'] = count($list) . '人中已经超出' . abs(intval($listtype[$x]['limit_num'])) . '人';
            } else {
                $listtype[$x]['renshutixing'] = count($list) . '人中选出' . min($listtype[$x]['limit_num'], intval($havetongguoapplicant)) . '人';
            }

            //过滤已投过的票数情况
            foreach ($list as $key => $value) {
                $selectvotedetail['rounddetail_id'] = $value['rounddetail_id'];
                $selectvotedetail['judge_id'] = $nowuser['user_id'];
                if (!isset($rdetails[$value['applicant_id']])) {
                    $istoupiao['is_toup'] = '';
                }else{
                    $istoupiao = $rdetails[$value['applicant_id']];//M('votedetail')->where($selectvotedetail)->find();
                }

                if (($istoupiao['is_toup'] == '0') || ($istoupiao['is_toup'] == '2')) {//如果是已投票或者已保存

                    $list[$key]['is_yiyou'] = '0';//表示已投
                } else {
                    $list[$key]['is_yiyou'] = '1';//表示没投     
                }
                //循环查找同一个人不同职称
                $dobule_applicant_where['employee_id'] = $value['employee_id'];
                $dobule_applicant_where['vote_id'] = $vote_id;
                $dobule_applicantArr =  $app[$value['employee_id']];
                if (count($dobule_applicantArr) > 1) {
                    foreach ($dobule_applicantArr as $m => $n) {
                        //查询当前职位该轮次是否存在及返回信息
                        $first_round_details = _applicant_have_in($round_id, $n['applicant_id']);

                        $idarr['rounddetail_id'] = $first_round_details['rounddetail_id'];
                        $idarr['applicant_id'] = $first_round_details['applicant_id'];
                        $idarr['apply_title'] = $first_round_details['apply_title'];
                        $idarr['select_total'] = $first_round_details['select_total'];
                        $idarr['applicant_status'] = $first_round_details['applicant_status'];
                        $idarr['previous_select_total'] = $first_round_details['previous_select_total'];

                        //判断双职称是否已经通过评审                                                
                        if ($first_round_details['is_notnowround'] == '1') {
                            $idarr['is_belong_to'] = '2';//表示不是该轮次的人员
                            $idarr['is_yiyou'] = '1';//表示没投
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
                            $selectvoteson['rounddetail_id'] = $first_round_details['rounddetail_id'];
                            $selectvoteson['judge_id'] = $nowuser['user_id'];
                            $istoupiaoson = M('votedetail')->where($selectvoteson)->find();
                            if (($istoupiaoson['is_toup'] == '0') || ($istoupiaoson['is_toup'] == '2')) {//如果是已投票或者已保存
                                $idarr['is_yiyou'] = '0';//表示已投
                            } else {
                                $idarr['is_yiyou'] = '1';//表示没投     
                            }
                        }
                        //加入导出需要的字段
                        $idarr['result'] = _title_pass_info($first_round_details['applicant_id'], '1');               
                        $list[$key]['myson'][] = $idarr;
                    }
                }else{
                    //加入导出需要的字段
                    $list[$key]['result'] = _title_pass_info_new($rounds, $value['applicant_id'], '1');
                }
            }
            if ($round_now_info['vote_sort_type'] == '1') {
                //排序
                $rating = array();
                //判断是否是评审第一轮
                if($round_now == '1' && $judgetype == '2'){
                    foreach ($list as $tmpIdx => $person) {
                        $rating[$tmpIdx] = $this->_calc_final_export_bubble_weight($person,$tmpIdx);
                    }
                }else{
                    foreach ($list as $tmpIdx => $person) {
                        $rating[$tmpIdx] = $this->_calc_toupiao_query_bubble_weight($person);
                    }
                }
                array_multisort($rating, SORT_DESC, $list);
            }
            else if ($round_now_info['vote_sort_type'] == '2') {
                //排序
                $keshi = array();
                $keshi_names = array();
                $pos = 1;
                foreach ($list as $tmpIdx => $person) {
                    if (!array_key_exists($person["office_name"], $keshi_names)){
                        $keshi_names[$person["office_name"]] = $pos;
                        $pos=$pos+1;
                    }

                    $keshi[$tmpIdx] = $keshi_names[$person["office_name"]];
                }
                //排序
                $rating = array();
                //判断是否是评审第一轮
                if($round_now == '1' && $judgetype == '2'){
                    foreach ($list as $tmpIdx => $person) {
                        $rating[$tmpIdx] = $this->_calc_final_export_keshi_bubble_weight($person,$tmpIdx, $keshi[$tmpIdx]);
                    }
                }else{
                    foreach ($list as $tmpIdx => $person) {
                        $rating[$tmpIdx] = $this->_calc_toupiao_query_keshi_bubble_weight($person) +(100-$keshi[$tmpIdx]) * 10000000;
                    }
                }
                array_multisort($rating, SORT_DESC, $list);
            }
            $ids = implode(',',array_column($list,'employee_id'));
          //  //dump($list);die;
            $listtype[$x]['ids'] = $ids;
            $listtype[$x]['list'] = $list;
        }
//        $listtype = [];

        $this->assign('listtype', $listtype);
        $this->display();
    }

    //获取提示信息
    public function toupiaojieguo($vote_id, $round_id, $check_applicantid, $ispanduan = 0) {
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
        if (empty($check_applicantid)) {
            $rounddetailidsarr = array();
        } else {
            $rounddetailidsarr = explode(',', $check_applicantid);
        }
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
			//通过的人数如果超过了已规定的通过的人数，则取最小值
            $surePasscount = min($passed_count,$quota_type_value['limit_num']);
            $lastlimit = min((intval($quota_type_value['limit_num']) - intval($surePasscount)), count($now_round_allapplicant));//获取当前轮次可选值的最小值
            
            //$lastlimit = min((intval($quota_type_value['limit_num']) - intval($passed_count)), count($now_round_allapplicant));//获取当前轮次可选值的最小值

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
                $lasttishiinfo1 = $quota_type_value['quota_log'] . '，已选' . intval($final_select_count) . '，可选' . $chaoguorenshu;
                $backinfo[] = array('result' => 1, 'detail_msg' => $lasttishiinfo);//提醒可选的人数
                $result = 1;
            } else if (intval($chaoguorenshu) == 0) {
                $chaoguorenshu = '0';
                $lasttishiinfo = '当前' . $quota_type_value['quota_log'] . '类型' . $tongguorenshutixing . '，已勾选' . intval($final_select_count) . '人，可选' . $chaoguorenshu . '人';
                $lasttishiinfo1 = $quota_type_value['quota_log'] .'，已选' . intval($final_select_count) . '，可选' . $chaoguorenshu;
                $backinfo[] = array('result' => 0);//表示是选择人数刚好
                $result = 0;
            } else {
                $chaoguorenshu = abs(intval($chaoguorenshu));
                $lasttishiinfo = '当前' . $quota_type_value['quota_log'] . '类型' . $tongguorenshutixing . '，已勾选' . intval($final_select_count) . '人，已超过' . $chaoguorenshu . '人';
                $lasttishiinfo1 = $quota_type_value['quota_log'] . '，已选' . intval($final_select_count) . '，已超过' . $chaoguorenshu;
                $backinfo[] = array('result' => 2, 'detail_msg' => $lasttishiinfo);//强制判断已超过不能提交
                $result = 2;
            }
            $jieguoinfo[] = array('tishijieguo'=>$lasttishiinfo, 'tishijieguo1'=> $lasttishiinfo1, 'result'=>$result);
            $jieguoinfofirstinfo[] = $lasttishiinfo;//第三种情况下获取值
        }
        if ($ispanduan == 3) {//表示投票界面第一次提醒值
            return $jieguoinfofirstinfo;
            exit();
        }
        if ($ispanduan == 1) {//表示提交时的提醒
            foreach ($backinfo as $tixinkey => $tixinvalue) {
                if ($tixinvalue['result'] == '2') { //人数超出提醒，结束循环
                    return $tixinvalue;
                    break;
                }
            }

            foreach ($backinfo as $tixinkey => $tixinvalue) {
                if ($tixinvalue['result'] == '1') {//人数减少提醒，结束循环
                    return $tixinvalue;
                    break;
                }
            }
            return $tixinvalue;
        }
        echo json_encode($jieguoinfo);
    }

    //提交时候判断是否超过人数。超过返回false
    public function isoverpeople($vote_id, $round_id, $check_applicantid, $ispanduan = 0) {
        $backjson = array('result' => 0);// success;
        $backinfo = $this->toupiaojieguo($vote_id, $round_id, $check_applicantid, '1');
        if ($backinfo) {
            $backjson = $backinfo;//提示超过的信息            
        }
        echo json_encode($backjson);
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

    /**
     * 投票排序
     * @param type $person
     * @return type
     */
    private function _calc_toupiao_query_bubble_weight($person) {
        $vote_info = $this->_calc_person_vote_number($person);
        $weight = (1000 - $person['ordernumber']) // Base
            + intval($vote_info['max']) * 50000
            + intval($vote_info['total'] * 1000);
        return $weight;
    }

    /**
     * 投票排序
     * @param type $person
     * @return type
     */
    private function _calc_toupiao_query_keshi_bubble_weight($person) {
        $vote_info = $this->_calc_person_vote_number($person);
        $weight = (1000 - $person['ordernumber']) // Base
            + intval($vote_info['max']) * 50000
            + intval($vote_info['total'] * 1000);
        return $weight ;
    }

    /**
     *
     * @param type $person
     * @return type
     */
    private function _calc_person_vote_number($person) {
//        $voteMax = 0;
//        $voteTotal = 0;
//        $passed = false;
        if (array_key_exists("myson", $person) && 2 == count($person['myson'])) {
            // double title
            $voteMax = max(intval($person['myson'][0]['is_belong_to'] == '1' ? $person['myson'][0]['previous_select_total'] : 0),
                $person['myson'][1]['is_belong_to'] == '1' ? $person['myson'][1]['previous_select_total'] : 0);
            $voteTotal = ($person['myson'][0]['is_belong_to'] == '1' ? $person['myson'][0]['previous_select_total'] : 0)
                + ($person['myson'][1]['is_belong_to'] == '1' ? $person['myson'][1]['previous_select_total'] : 0);
//            } else {
//                // single title
//                $voteMax = intval($person['myson'][0]['is_belong_to'] == '1' ? $person['previous_select_total'] : 0);
//                $voteTotal = $person['myson'][0]['is_belong_to'] == '1' ? $person['previous_select_total'] : 0;
//            }
        } else {
            $voteMax = $person['previous_select_total'];
            $voteTotal = $person['previous_select_total'];
        }
        return array("max" => $voteMax, "total" => $voteTotal);
    }
    
    //评审第一轮时候排序
    public function _calc_final_export_bubble_weight($person, $importOrder) {
        $base = 1000 - $importOrder;
        $roundWeight = 0;
        if (array_key_exists("myson", $person) && 2 == count($person['myson'])) {//如果是双职称
            if ($person['myson'][0]['result']['passed']) {
                $roundWeight = (100 - $person['myson'][0]['result']['passed_round']) * 50000
                    + $person['myson'][0]['result']['select_total'] * 1000;
            }
            if ($person['myson'][1]['result']['passed']) {
                $roundWeight = max($roundWeight,
                    ((100 - $person['myson'][1]['result']['passed_round']) * 50000
                        + $person['myson'][1]['result']['select_total'] * 1000));
            }
        }else{
            if ($person['result']['passed']) {
                $roundWeight = (100 - $person['result']['passed_round']) * 50000
                    + $person['result']['select_total'] * 1000;
            }
        }
        
        return $base + $roundWeight;
    }

    public function _calc_final_export_keshi_bubble_weight($person, $importOrder, $keshi) {
        $base = 1000 - $importOrder;
        $roundWeight = 0;
        if (array_key_exists("myson", $person) && 2 == count($person['myson'])) {//如果是双职称
            if ($person['myson'][0]['result']['passed']) {
                $roundWeight = (100 - $person['myson'][0]['result']['passed_round']) * 50000
                    + $person['myson'][0]['result']['select_total'] * 1000;
            }
            if ($person['myson'][1]['result']['passed']) {
                $roundWeight = max($roundWeight,
                    ((100 - $person['myson'][1]['result']['passed_round']) * 50000
                        + $person['myson'][1]['result']['select_total'] * 1000));
            }
        }else{
            if ($person['result']['passed']) {
                $roundWeight = (100 - $person['result']['passed_round']) * 50000
                    + $person['result']['select_total'] * 1000;
            }
        }

        return $base + $roundWeight + (100-$keshi) * 100000000;
    }

    public function is_cancel($round_id) {
        $cancel_tixing = array('result' => '1', 'info' => '');
        //查询当前轮次投票信息是否已经关闭
        $roundwhere['round_id'] = $round_id;
        $roundstatus = M('voteround')->where($roundwhere)->find();
        if ($roundstatus['round_status'] != '0') {
            $cancel_tixing = array('result' => '2', 'info' => '当前轮次已终止，暂不能操作！');
        }
        //查询当前投票人员是否投票完成
        $userwhere['user_id'] = $_SESSION['user_id'];
        $nowuser = M('judges')->where($userwhere)->find();
        $whereistjtp['round_id'] = $round_id;
        $whereistjtp['judge_id'] = $nowuser['user_id'];
        $whereistjtp['is_toup'] = '0';
        $istijiaotoupiao = M('votedetail')->where($whereistjtp)->find();
        if (!empty($istijiaotoupiao)) {
            $cancel_tixing = array('result' => '2', 'info' => '您已经提交过投票，请不要重复操作！');
        }
        echo json_encode($cancel_tixing);
    }
}
