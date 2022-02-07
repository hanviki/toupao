<?php
/**
 * 版    本：1.0.0
 * 功能说明：文章控制器。
 *
 **/
namespace Home\Controller;
use Vendor\Tree;
header('Content-Type:text/html;charset=utf-8');

class PingController extends ComController
{
    public function quxiao()
    {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }   
        $judgetype_id = isset($_REQUEST['judgetype_id']) ? $_REQUEST['judgetype_id'] : false;
        $category_id = isset($_REQUEST['category_id']) ? $_REQUEST['category_id'] : false;
         if(!empty($judgetype_id) && !empty($category_id)){
            $this->assign('judgetype_id', $judgetype_id);
            $this->assign('category_id', $category_id);
            $where['judgetype_id'] = $judgetype_id;
            $where['category_id'] = $category_id;            
            //失效评委
            $data['user_status'] = '2';//失效
            M('judges')->where($where)->save($data);
            $this->success('操作成功！');
        } else {
            $this->error('参数错误！');
        }
    }

    public function add($judgetype_id = 0, $category_id = 0)
    {
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }   
        $category = M('category')->field('category_id,category_name')->select();
        $this->assign('category', $category);//组别

        $pingtype = M('judgetype')->select();
        $this->assign('pingtype', $pingtype);//评审类型
        if(!empty($judgetype_id) && !empty($category_id)){
            $this->assign('judgetype_id', $judgetype_id);
            $this->assign('category_id', $category_id);
            $where['judgetype_id'] = $judgetype_id;
            $where['category_id'] = $category_id;    
        }else{
            $where['judgetype_id'] = $pingtype[0]['judgetype_id'];
            $where['category_id'] = $category[0]['category_id'];            
        } 
        //查询出生成的未激活的评委信息
        $where['user_status'] = array('neq',2);
        $weiji = M('judges')->where($where)->select();
        $this->assign('weiji', $weiji);
        $this->assign('judgetype_id',$judgetype_id);
        $this->display('form');
    }   
    
    
    public function update($judgetype_id = 0, $category_id = 0)
    {      
        $judgetype_id = intval($judgetype_id);
        $category_id = intval($category_id);

        $data['judgetype_id'] = $judgetype_id;
        $data['category_id'] = $category_id;
        $where1 = "judgetype_id = {$judgetype_id} and category_id = {$category_id} and user_status != '2'";
        $num = isset($_POST['num']) ? intval($_POST['num']) : 0;

        if($_POST['panduan'] == 'panduan'){//代表失效
            $where['judgetype_id'] = $judgetype_id;
            $where['category_id'] = $category_id;
            $datastatus['user_status'] = '2';//失效
            M('judges')->where($where)->save($datastatus);
            $this->success('操作成功！',"/Home/Ping/add?judgetype_id={$judgetype_id}&category_id={$category_id}");
            die();
        }

        //查询当前组别的评委数量，判断是否超过50.
        $nowshuliang = M('judges')->where($where1)->count();
        $totlesum = intval($num) + intval($nowshuliang);
        if($totlesum > 99){
            echo "<script>alert('改组别生成评委数量不能超过50!');window.history.back(-1);</script>";
            exit(); 
        }
        
        for ($i=0; $i < $num; $i++) {
            $maxlast = M('judges')->max('user_id');
            $lastid = $maxlast+1;
            $data['user_name'] = 'p'.str_pad(dechex($lastid), 5, "0", STR_PAD_LEFT);
            $data['password'] = rand(1000,9999);
            $madd = M('judges')->data($data)->add();
        }

        if ($madd) {            
            $this->success('恭喜！操作成功！',"/Home/Ping/add?judgetype_id={$judgetype_id}&category_id={$category_id}");
        } else {
            $this->error('抱歉，未知错误！');
        }
    }


    public function getpw(){
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $category_id = I('get.category_id');
        $judgetype_id = I('get.judgetype_id');
        if (!empty($judgetype_id) && !empty($category_id)){
            $where['judgetype_id'] = $judgetype_id;
            if($judgetype_id == '1'){
                $where['category_id'] = $category_id;
            } 
            $where['user_status'] = array('neq',2);
            //查询出生成的未激活的评委信息
            $weiji = M('judges')->where($where)->select();
            echo json_encode($weiji);
        }else{
            die('0');
        }
    }
    
    
    //导出并激活评委
    public function daoji(){
        $uwhere['user_id'] = $_SESSION['user_id'];
        $userinfo = M('judges')->where($uwhere)->find();
        if ($userinfo['user_type'] == '0') {
            $url = U('Tpiao/index');
            header("Location: $url");
            exit(0);
        }
        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;
        if ($aids) {
            if (is_array($aids)) {
                $aids = implode(',', $aids);
                $map['user_id'] = array('in', $aids);
            } else {
                $map = 'user_id=' . $aids;
            }
            $date = array('user_status'=>'1');
            M('judges')->data($date)->where($map)->save();//激活评委
            //操作导出评委
            $xlsName = '';//导出数据名称
            $xlsCell = array();//数据标题
            $xlsData = array();//数据
            $xlslist = M('judges')->field('user_id,user_name,password,category_id,judgetype_id')->where($map)->select();
            foreach ($xlslist as $key => &$value) { 
                    //查询组别 评审类型
                    $zlei['category_id'] = $value['category_id'];
                    $zbtype = M('category')->field('category_name')->where($zlei)->find();                    
                    $psmap['judgetype_id'] = $value['judgetype_id'];
                    $pslx = M('judgetype')->field('judge_type')->where($psmap)->find();
                    
                    $xlsData[$key]['xhid'] = $key+1;
                    $xlsData[$key]['user_name'] = $value['user_name'];
                    $xlsData[$key]['password'] = $value['password'];
                    $xlsData[$key]['judge_type'] = $pslx['judge_type'];
                    if($value['judgetype_id'] == '1'){//学科组信息
                        $xlsData[$key]['category_name'] = $zbtype['category_name'];
                    }
                    if(empty($xlsName)){
                        $xlsName = "评委信息_".$pslx['judge_type']."_".$zbtype['category_name'];
                    }
            }
            if($xlslist[0]['judgetype_id'] == '1'){//学科组信息
                $xlsCell = array(
                    array('xhid','序号'),
                    array('user_name','评委用户名'),
                    array('password','评委密码'),
                    array('judge_type','评审类型'),
                    array('category_name','组别'),
                );    
            }else{
                $xlsCell = array(
                    array('xhid','序号'),
                    array('user_name','评委用户名'),
                    array('password','评委密码'),
                    array('judge_type','评审类型'),                    
                ); 
            }                        
            exportExcel($xlsName,$xlsCell,$xlsData);
            $this->success('操作成功！');
        } else {
            $this->error('参数错误！');
        }
    }
}