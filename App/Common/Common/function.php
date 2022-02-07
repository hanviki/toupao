<?php


/**
 *
 * 日    期：2015-09-17
 * 版    本：1.0.0
 * 功能说明：模块公共文件。
 *
 * */
function UpImage($callBack = "image", $width = 100, $height = 100, $image = "") {

    echo '<iframe scrolling="no" frameborder="0" border="0" onload="this.height=this.contentWindow.document.body.scrollHeight;this.width=this.contentWindow.document.body.scrollWidth;" width=' . $width . ' height="' . $height . '"  src="' . U('Upload/uploadpic', array('Width' => $width, 'Height' => $height, 'BackCall' => $callBack)) . '"></iframe>
         <input type="hidden" ' . 'value = "' . $image . '"' . 'name="' . $callBack . '" id="' . $callBack . '">';
}

function BatchImage($callBack = "image", $width = 100, $height = 100, $image = "") {

    echo '<iframe scrolling="no" frameborder="0" border="0" width=100% onload="this.height=this.contentWindow.document.body.scrollHeight;" src="' . U('Upload/batchpic', array('Width' => $width, 'Height' => $height, 'BackCall' => $callBack)) . '"></iframe>
		<input type="hidden" ' . 'value = "' . $image . '"' . 'name="' . $callBack . '" id="' . $callBack . '">';
}

/*
 * 函数：网站配置获取函数
 * @param  string $k      可选，配置名称
 * @return array          用户数据
 */

function setting($k = 'all') {
    $cache = S($k);
    //如果缓存不为空直接返回
    if (null != $cache) {
        return $cache;
    }
    $data = '';
    $setting = M('setting');
    //判断是否查询全部设置项
    if ($k == 'all') {
        $setting = $setting->field('k,v')->select();
        foreach ($setting as $v) {
            $config[$v['k']] = $v['v'];
        }
        $data = $config;
    } else {
        $result = $setting->where("k='{$k}'")->find();
        $data = $result['v'];
    }
    //建立缓存
    if ($data) {
        S($k, $data);
    }
    return $data;
}

/**
 * 函数：格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 函数：加密
 * @param string            密码
 * @return string           加密后的密码
 */
function password($password) {
    /*
     * 后续整强有力的加密函数
     */
    return md5('Q' . $password . 'W');
}

/**
 * 随机字符
 * @param number $length 长度
 * @param string $type 类型
 * @param number $convert 转换大小写
 * @return string
 */
function random($length = 6, $type = 'string', $convert = 0) {
    $config = array(
        'number' => '1234567890',
        'letter' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'string' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'all' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
    );

    if (!isset($config[$type])) {
        $type = 'string';
    }
    $string = $config[$type];

    $code = '';
    $strlen = strlen($string) - 1;
    for ($i = 0; $i < $length; $i++) {
        $code .= $string{mt_rand(0, $strlen)};
    }
    if (!empty($convert)) {
        $code = ($convert > 0) ? strtoupper($code) : strtolower($code);
    }
    return $code;
}

//获取所有的子级id
function category_get_sons($sid, &$array = array()) {
    //获取当前sid下的所有子栏目的id
    $categorys = M("category")->where("pid = {$sid}")->select();

    $array = array_merge($array, array($sid));
    foreach ($categorys as $category) {
        category_get_sons($category['id'], $array);
    }
    $data = $array;
    unset($array);
    return $data;
}

/**
 * 获取文章url地址
 * url结构：ttp://wwww.qwadmin.com/分类/子分类/子分类/id.html
 * 使用方法：模板中{:articleUrl(array('aid'=>$val['aid']))}
 *
 *
 * @param $data
 * @return $string
 */
function articleUrl($data) {
    //如果数组为空直接返回空字符
    if (!$data) {
        return '';
    }
    //如果参数错误直接返回空字符
    if (!isset($data['aid'])) {
        return '';
    }

    $aid = (int)$data['aid'];

    //获取文章信息
    $article = M('article')->where(array('aid' => $aid))->find();
    //获取当前内容所在分类
    $category = M('category')->where(array('id' => $article['sid']))->find();
    //获取当前分类
    $categoryUrl = $category['dir'];
    //遍历获取当前文章所在分类的有上级分类并且组合url
    while ($category['pid'] <> 0) {
        $category = M('category')->where(array('id' => $category['pid']))->find();
        $categoryUrl = $category['dir'] . "/" . $categoryUrl;
        //如果上级分类已经无上级分类则退出
    }

    $categoryUrl = __ROOT__ . "/" . $categoryUrl;
    //组合文章url
    $articleUrl = $categoryUrl . '/' . $aid . ".html";
    return $articleUrl;
}

/**
 * 获取导航URL
 * @param  string $url 导航URL
 * @return string      解析或的url
 */
function get_nav_url($url) {
    switch ($url) {
        case 'http://' === substr($url, 0, 7):
        case '#' === substr($url, 0, 1):
            break;
        default:
            $url = U($url);
            break;
    }
    return $url;
}

function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 *
 * @param  [string] $filePath [需要导入的Excel表]
 * @param  [int] $rowstart [从excel表中的第几行开始插入数据库]
 * @param  [array] $info     [数据库中字段组成的一维数组]
 * @param  [string] $table    [数据库中需要导入数据的数据表]
 * @param  [string] $model    [实例化model]
 */
function importExcel($lunci, $vote_id, $filePath, $rowstart, $info, $table, $model, $files) {
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new PHPExcel();
    $PHPReader = new PHPExcel_Reader_Excel2007(); /* 默认格式excel2007 */
    if (!$PHPReader->canRead($filePath)) {
        $PHPReader = new PHPExcel_Reader_Excel5(); /* Excel5格式 */
        if (!$PHPReader->canRead($filePath)) {
            return;
        }
    }
    $PHPExcel = $PHPReader->load($filePath);
    $currentSheet = $PHPExcel->getSheet(0);
    /* 取取所有列数 */
    $allColumn = $currentSheet->getHighestColumn();
    /* 取取所有行数 */
    $allRow = $currentSheet->getHighestRow();
    /* 循环读取数据,默认输出格式utf-8 */
    $ColumnIndex = PHPExcel_Cell::columnIndexFromString($allColumn); /* 列数索引转换A->0 */
    /* 导入之前清空先前的日志 */
    $strs = array();
    for ($row = $rowstart; $row <= $allRow; $row++) {
        /* ColumnIndex的列数索引从0开始 */
        for ($col = 0; $col < $ColumnIndex; $col++) {
            $strs[$col] = $currentSheet->getCellByColumnAndRow($col, $row)->getValue();
        }
        //判断申请职位是否是单个
        $strzhiwei = $strs[4];
        $backtitle = is_double_title($strzhiwei);
        if (is_array($backtitle)) {//表示双职称
            foreach ($backtitle as $m => $n) {
                $firsttitle = strpos($strzhiwei, $n);
                if (gettype($firsttitle) == 'integer') {
                    if (empty($firsttitle)) {
                        $lasttitle[0] = $n;
                        $lasttitle[1] = substr($strzhiwei, strlen($n));
                    }
                }
            }
            $zhiArr = $lasttitle;
        } else {
            $zhiArr = array('0' => $strzhiwei);
        }
        foreach ($zhiArr as $key => $value) {
            /* 拼装数据插入语句 */
            $strs = TrimArray($strs);
            $bind = array_combine($info, $strs);
            $bind['vote_id'] = $vote_id;
            $bind['round_id'] = $lunci;
            $bind['apply_title'] = $value;
            $res = $bind;
            $bo = array_key_exists('term', $res);
            if ($bo) {
                $tiaojian = array_splice($res, 0, 3);
                $map['term'] = $tiaojian['term'];
            } else {
                $tiaojian = array_splice($res, 0, 2);
            }
            /* 拼装数据查询语句 */
            $res1 = array_filter($res); /* 过滤空数组 */
            /* 执行更新时查看数据中是否有数据 */
            if (!empty($res1)) {
                /* 无数据时添加 */
                unset($map);
                $result = $model->add($bind);
            }
        }
    }
    return $result;
}

/**
 * 导出
 * @param  [type] $expTitle     [description]
 * @param  [type] $expCellName  [description]
 * @param  [type] $expTableData [description]
 * @return [type]               [description]
 */
function exportExcel($expTitle, $expCellName, $expTableData) {
    $xlsTitle = iconv('utf-8', 'gb2312', $expTitle); //文件名称
    $fileName = date('_YmdHis'); //or $xlsTitle 文件名称可根据自己情况设定
    $cellNum = count($expCellName);
    $dataNum = count($expTableData);
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new PHPExcel();
    $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1'); //合并单元格
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  Export time:' . date('Y-m-d H:i:s'));
    for ($i = 0; $i < $cellNum; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
    }
    // Miscellaneous glyphs, UTF-8
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    //设置所有单元格居中
    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

    //设置单元格自动换行
    $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(TRUE);

    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xlsx"');
    header("Content-Disposition:attachment;filename=$xlsTitle.xlsx"); //attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

//导出模板类数据
function finalResultExport($exportSummary, $xlsName, $expTableData, $isFinalResult = false) {

    $xlsTitle = iconv('utf-8', 'gb2312', $xlsName); //文件名称
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new PHPExcel();

    $excelColumns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE',
        'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT',
        'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    $chineseSN = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二', '十三', '十四', '十五', '十六');
    $lastRowNum = 1;//excel 行从1开始

    $objPHPExcel->setActiveSheetIndex(0);// 准备操作第一个工作表
    // TODO 设置全局格式：所有单元格居中
//    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getDefaultStyle()->getFont()->setName('宋体');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(14);

    // 导出Excel的大标题
    _final_export_WriteSummaryTitle($lastRowNum, $excelColumns, $exportSummary, $objPHPExcel, $isFinalResult);

    // 循环写Excel的分组评审结果
    $groupIdx = 0;
    foreach ($expTableData as $groupInfo) {
        _final_export_WriteGroup($lastRowNum, $excelColumns, $chineseSN[$groupIdx++], $groupInfo, $objPHPExcel);
    }

    // 表格填充部分增加border（不包含title）
    $objPHPExcel->getActiveSheet()->getStyle("A3:" . $excelColumns[6] . ($lastRowNum - 1))
        ->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

    //评委签名
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum + 1), '评委签名：');

    // 微调单元格宽度
    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[4])->setWidth(16);
    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[5])->setWidth(10);
    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[6])->setWidth(10);

    $fileName = $xlsTitle . "_" . date("YmdHis");
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $fileName . '.xlsx"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx"); //attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

function _final_export_WriteSummaryTitle(&$lastRowNum, &$excelColumns, $exportSummary, $objPHPExcel, $isFinalResult) {
    // 第一行大标题
    $firstTitle = mb_substr($exportSummary['vote_name'], 0, 4) . '年协和医院' . (intval($exportSummary['category_id']) <= 3 ? '高级' : (intval($exportSummary['category_id'])>=7?'二三级':'中初级'))
        . '专业技术职务评审' . ($isFinalResult ? '最终结果' : '投票结果');
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $firstTitle);//一级大标题信息
    set_cell_style_center_alignment($excelColumns[0] . $lastRowNum, $objPHPExcel);
    set_cell_style_font($excelColumns[0] . $lastRowNum, $objPHPExcel, TRUE, 16);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[6] . $lastRowNum);
    $lastRowNum++;
    // 第二行大标题
    $secondTitle = ($isFinalResult ? '' : $exportSummary['judgetype'] . '   ') . $exportSummary['professional_name'] . '：' . $exportSummary['category_name'];
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $secondTitle);//二级大标题信息
    set_cell_style_center_alignment($excelColumns[0] . $lastRowNum, $objPHPExcel);
    set_cell_style_font($excelColumns[0] . $lastRowNum, $objPHPExcel, TRUE, 14);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[6] . $lastRowNum);
    $lastRowNum++;
}

function _final_export_WriteGroup(&$lastRowNum, &$excelColumns, $snIinChinese, &$groupInfo, $objPHPExcel) {

    $totalPersonCount = count($groupInfo[2]);
    // 写组标题，标题需要跨行合并
    $groupTitle = $snIinChinese . '、' . $groupInfo[0]['category_name'] . '：申请'
        . $groupInfo[0]['applicant_style'] . '（' . $groupInfo[0]['quota_log'] . '，'
        . min($groupInfo[0]['apply_total'], $totalPersonCount) . '人中选出'
        . min($groupInfo[0]['apply_select'], $totalPersonCount) . '人）';

    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $groupTitle);
    $objPHPExcel->getActiveSheet()->getStyle($excelColumns[0] . $lastRowNum)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[6] . $lastRowNum);// 跨行合并
    $lastRowNum++;  //写完标题以后，行号加1

    // 写列名信息
    _final_export_WriteGroupTitle($lastRowNum, $excelColumns, $groupInfo[1], $objPHPExcel);
    $secondTitleExist = array_key_exists('secondTitle', $groupInfo[1]);

    // 写当前指标分组下的每个人的信息
    foreach ($groupInfo[2] as $person) {
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . ($lastRowNum), $person[0]);
        set_cell_number_string_text_format($excelColumns[1] . ($lastRowNum), $objPHPExcel, $person[1]);//employee id
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[2] . ($lastRowNum), $person[2]);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[3] . ($lastRowNum), $person[3]);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum), $person[4]);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . ($lastRowNum), $person['firstTitle']);
        if ($secondTitleExist) {
            $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[6] . ($lastRowNum), $person['secondTitle']);
        } else {
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[5] . ($lastRowNum) . ":" . $excelColumns[6] . ($lastRowNum));// 跨行合并
        }
        $lastRowNum++; // 没写完一行某人的信息，行号加1，方便下一个人书写
    }
//    $lastRowNum++; // 每一组的人员信息写完以后，留个空行
}

function _final_export_WriteGroupTitle(&$lastRowNum, &$excelColumns, &$groupTitle, $objPHPExcel) {

    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, '序号');
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[1] . $lastRowNum, '职工编码');
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[2] . $lastRowNum, '科室');
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[3] . $lastRowNum, '姓名');
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . $lastRowNum, '申报职称');
    $titleCount = count($groupTitle);

    for ($i = 0; $i < 5; ++$i) {
        set_cell_style_center_alignment($excelColumns[$i] . $lastRowNum, $objPHPExcel);
        if (2 == $titleCount) {
            // 当前cell和下一行的cell进行合并； 比如 A15:A16     A20:A21
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[$i] . $lastRowNum . ":" . $excelColumns[$i] . ($lastRowNum + 1));
        }
    }

    // 合并评审意见单元格（左右合并）
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . $lastRowNum, '评审意见');
    set_cell_style_center_alignment($excelColumns[5] . $lastRowNum, $objPHPExcel);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[5] . $lastRowNum . ":" . $excelColumns[6] . ($lastRowNum));

    if (2 == $titleCount) {
        //如果是双职称，则需要在评审意见下面列上第一和第二职称
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . ($lastRowNum + 1), $groupTitle['firstTitle']);
        set_cell_style_center_alignment($excelColumns[5] . ($lastRowNum + 1), $objPHPExcel);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[6] . ($lastRowNum + 1), $groupTitle['secondTitle']);
        set_cell_style_center_alignment($excelColumns[6] . ($lastRowNum + 1), $objPHPExcel);
    }

    $lastRowNum = $lastRowNum + $titleCount; //写完列名以后，修改行号（单职称+1，双职称+2）
}

function set_cell_style_center_alignment($cell, $objPHPExcel) {
    $objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getActiveSheet()->getStyle($cell)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
}

/**
 * @param string $cell
 * @param object $objPHPExcel
 * @param boolean $setBold
 * @param int $size
 */
function set_cell_style_font($cell, $objPHPExcel, $setBold, $size) {
    $objPHPExcel->getActiveSheet()->getStyle($cell)->getFont()->setBold($setBold); //粗体
    $objPHPExcel->getActiveSheet()->getStyle($cell)->getFont()->setSize($size); // 字号
}

function set_cell_number_string_text_format($cell, $objPHPExcel, $value) {
    $objPHPExcel->getActiveSheet()->setCellValueExplicit($cell, $value, PHPExcel_Cell_DataType::TYPE_STRING);
    $objPHPExcel->getActiveSheet()->getStyle($cell)->getNumberFormat()->setFormatCode("@");
}

//判断字符串是一个职称还是多个职称
function is_double_title($inputTitle) {
    $all_applicant = array(
        '教授', '主任医师', '主任护师', '主任药师', '主任技师', '研究员', '正高级工程师', '编审', '研究馆员', '正高级会计师', '正高级统计师', '副教授', '副主任医师', '副主任护师', '副主任药师', '副主任技师', '副研究员', '高级工程师', '副编审', '副研究馆员', '高级会计师', '高级统计师', '讲师', '主治医师', '主管护师', '主管药师', '主管技师', '助理研究员', '工程师', '编辑', '馆员', '会计师', '统计师', '助教', '住院医师', '护师', '药师', '技师', '实习研究员', '助理工程师', '助理编辑', '助理馆员', '助理会计师', '助理统计师', '护士', '药士', '技士', '二级', '三级'
    );
    $isExist = in_array($inputTitle, $all_applicant);
    if ($isExist) {
        return FALSE;
    } else {
        return $all_applicant;
    }
}


/* 去除数组中字符串两边所有的空格 */
function TrimArray($Input) {
    if (!is_array($Input))
        return trim($Input);
    return array_map('TrimArray', $Input);
}

//按照导入顺序导出2018617
function prepare_final_export_orderdao_result($xlsName, $vote_id, $judgetype, $isFinalResult = false) {
    $prefix = C('DB_PREFIX');
    //查询当前投票信息
    $vote_id_where['vote_id'] = $vote_id;
    $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();
    // 1. 准备一、二大标题信息
    if ($judgetype == '1') {//查询学科组通过的信息
        $exportSummary['judgetype'] = '学科组评审';
    } else {//查询
        $exportSummary['judgetype'] = '医院评审委员会评审';
    }

    $exportSummary['vote_name'] = $vote_info['vote_name']; //投票名称
    $exportSummary['category_id'] = $vote_info['category_id']; //组别Id
    $exportSummary['category_name'] = $vote_info['category_name']; //组别名称
    $exportSummary['professional_name'] = $vote_info['professional_name']; //职称

    // 2. 准备指标分组的列表信息；
    $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,applicant_style')->order('ordernumber asc')->where($vote_id_where)->select();

    $vote_quota_types = array();
    foreach ($voteApplySetTable as $quota_data) {
        $vote_quota_types[] = $quota_data;
    }
    $xlsData = array();
    foreach ($vote_quota_types as $quota_type_idx => $quota_type_value) {

        // 1. 通过vote_id,  quota_log 获取基本的groupTitle信息
        $vote_and_quota_where['vote_id'] = $vote_id;
        $vote_and_quota_where['quota_log'] = $quota_type_value['quota_log'];
        $groupTitleTbl = M('applicant')->field('apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->where($vote_and_quota_where)->limit(1)->select();

        $xlsData[$quota_type_idx][0]['category_name'] = $vote_info['category_name']; //组别
        $xlsData[$quota_type_idx][0]['professional_name'] = $vote_info['professional_name']; //职称
        $xlsData[$quota_type_idx][0]['quota_log'] = $quota_type_value['quota_log']; //指标类型
        $xlsData[$quota_type_idx][0]['applicant_style'] = $quota_type_value['applicant_style']; // 申请类别
        $xlsData[$quota_type_idx][0]['apply_total'] = $groupTitleTbl[0]['apply_total']; //总人数
        $xlsData[$quota_type_idx][0]['apply_select'] = $groupTitleTbl[0]['limit_num']; //评审委员会选出人数
        // FINISH
        //====================================================================

        // 2. 通过vote_id 获取 导入序号及人员基础信息；
        $personListTbl = M('applicant')->field('applicant_id,ordernumber,employee_id,office_name,applicant_name,apply_title')->order('ordernumber asc')->where($vote_and_quota_where)->select();
        $personList = array();
        $anyDoubleTitle = false;
        foreach ($personListTbl as $tblIdx => $singlePerson) {
            if (array_key_exists($singlePerson[ordernumber], $personList)) {
                $personList[$singlePerson[ordernumber]]['doubletitle'] = true;
                $personList[$singlePerson[ordernumber]][4] = $personList[$singlePerson[ordernumber]][4] . $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['title'] = $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['applicant_id'] = $singlePerson['applicant_id'];
                $personList[$singlePerson[ordernumber]]['second']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                    $xlsData[$quota_type_idx][1]['secondTitle'] = $personList[$singlePerson[ordernumber]]['second']['title'];
                    $anyDoubleTitle = true;
                }
            } else {
                $personList[$singlePerson[ordernumber]][1] = $singlePerson['employee_id']; //
                $personList[$singlePerson[ordernumber]][2] = $singlePerson['office_name']; //
                $personList[$singlePerson[ordernumber]][3] = $singlePerson['applicant_name']; //
                $personList[$singlePerson[ordernumber]][4] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['title'] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['applicant_id'] = $singlePerson['applicant_id']; //
                $personList[$singlePerson[ordernumber]]['first']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
//                $personList[$singlePerson[ordernumber]]['applicant_id'] = $singlePerson['applicant_id']; //TODO 比较applicant_id 决定双职称顺序
                if (!array_key_exists(1, $xlsData[$quota_type_idx])) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                }
            }
        }

        if ('2' == $judgetype && !$isFinalResult) {
            $personList = array_filter($personList, "_person_involved_committee_judge");
        }

        if (0 < count($personList)) {
            // 终审结果排序（按通过轮次优先）
//            $rating = array();
//            foreach ($personList as $importOrder => $value) {
//                $rating[$importOrder] = _calc_final_export_bubble_weight($value, $importOrder);
//            }
//            array_multisort($rating, SORT_DESC, $personList);

            // 3. 循环人员信息，获取其职位通过情况；
            $seqNum = 1;
            foreach ($personList as $pIdx => $person) {
                $xlsData[$quota_type_idx][2][$pIdx][0] = $seqNum++;
                $xlsData[$quota_type_idx][2][$pIdx][1] = $person[1];
                $xlsData[$quota_type_idx][2][$pIdx][2] = $person[2];
                $xlsData[$quota_type_idx][2][$pIdx][3] = $person[3];
                $xlsData[$quota_type_idx][2][$pIdx][4] = $person[4];
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] = $person['first']['result']['passed'] ? "通过" : "未通过";
                } else {
                    //处理职称1
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['firstTitle'], array_key_exists('doubletitle', $person), $person);
                    // 处理职称2
                    $xlsData[$quota_type_idx][2][$pIdx]['secondTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['secondTitle'], array_key_exists('doubletitle', $person), $person);
                }
            }
        } else {
            array_pop($xlsData);//该类型下没有人，废弃
        }
    }
    finalResultExport($exportSummary, $xlsName, $xlsData, $isFinalResult);
}


//按轮次票数导出2018617
function prepare_final_export_ballotdao_result($xlsName, $vote_id, $judgetype, $isFinalResult = false) {
    $prefix = C('DB_PREFIX');
    //查询当前投票信息
    $vote_id_where['vote_id'] = $vote_id;
    $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();
    // 1. 准备一、二大标题信息
    if ($judgetype == '1') {//查询学科组通过的信息
        $exportSummary['judgetype'] = '学科组评审';
    } else {//查询
        $exportSummary['judgetype'] = '医院评审委员会评审';
    }

    $exportSummary['vote_name'] = $vote_info['vote_name']; //投票名称
    $exportSummary['category_id'] = $vote_info['category_id']; //组别Id
    $exportSummary['category_name'] = $vote_info['category_name']; //组别名称
    $exportSummary['professional_name'] = $vote_info['professional_name']; //职称

    // 2. 准备指标分组的列表信息；
    $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,applicant_style')->order('ordernumber asc')->where($vote_id_where)->select();

    $vote_quota_types = array();
    foreach ($voteApplySetTable as $quota_data) {
        $vote_quota_types[] = $quota_data;
    }
    $xlsData = array();
    foreach ($vote_quota_types as $quota_type_idx => $quota_type_value) {

        // 1. 通过vote_id,  quota_log 获取基本的groupTitle信息
        $vote_and_quota_where['vote_id'] = $vote_id;
        $vote_and_quota_where['quota_log'] = $quota_type_value['quota_log'];
        $groupTitleTbl = M('applicant')->field('apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->where($vote_and_quota_where)->limit(1)->select();

        $xlsData[$quota_type_idx][0]['category_name'] = $vote_info['category_name']; //组别
        $xlsData[$quota_type_idx][0]['professional_name'] = $vote_info['professional_name']; //职称
        $xlsData[$quota_type_idx][0]['quota_log'] = $quota_type_value['quota_log']; //指标类型
        $xlsData[$quota_type_idx][0]['applicant_style'] = $quota_type_value['applicant_style']; // 申请类别
        $xlsData[$quota_type_idx][0]['apply_total'] = $groupTitleTbl[0]['apply_total']; //总人数
        $xlsData[$quota_type_idx][0]['apply_select'] = $groupTitleTbl[0]['limit_num']; //评审委员会选出人数
        // FINISH
        //====================================================================

        // 2. 通过vote_id 获取 导入序号及人员基础信息；
        $personListTbl = M('applicant')->field('applicant_id,ordernumber,employee_id,office_name,applicant_name,apply_title')->order('ordernumber asc')->where($vote_and_quota_where)->select();
        $personList = array();
        $anyDoubleTitle = false;
        foreach ($personListTbl as $tblIdx => $singlePerson) {
            if (array_key_exists($singlePerson[ordernumber], $personList)) {
                $personList[$singlePerson[ordernumber]]['doubletitle'] = true;
                $personList[$singlePerson[ordernumber]][4] = $personList[$singlePerson[ordernumber]][4] . $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['title'] = $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['applicant_id'] = $singlePerson['applicant_id'];
                $personList[$singlePerson[ordernumber]]['second']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                    $xlsData[$quota_type_idx][1]['secondTitle'] = $personList[$singlePerson[ordernumber]]['second']['title'];
                    $anyDoubleTitle = true;
                }
            } else {
                $personList[$singlePerson[ordernumber]][1] = $singlePerson['employee_id']; //
                $personList[$singlePerson[ordernumber]][2] = $singlePerson['office_name']; //
                $personList[$singlePerson[ordernumber]][3] = $singlePerson['applicant_name']; //
                $personList[$singlePerson[ordernumber]][4] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['title'] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['applicant_id'] = $singlePerson['applicant_id']; //
                $personList[$singlePerson[ordernumber]]['first']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
//                $personList[$singlePerson[ordernumber]]['applicant_id'] = $singlePerson['applicant_id']; //TODO 比较applicant_id 决定双职称顺序
                if (!array_key_exists(1, $xlsData[$quota_type_idx])) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                }
            }
        }

        if ('2' == $judgetype && !$isFinalResult) {
            $personList = array_filter($personList, "_person_involved_committee_judge");
        }

        if (0 < count($personList)) {
            // 终审结果排序（按通过轮次优先）
            $rating = array();
            foreach ($personList as $importOrder => $value) {
                $rating[$importOrder] = _calc_final_export_bubble_weight($value, $importOrder);
            }
            array_multisort($rating, SORT_DESC, $personList);

            // 3. 循环人员信息，获取其职位通过情况；
            $seqNum = 1;
            foreach ($personList as $pIdx => $person) {
                $xlsData[$quota_type_idx][2][$pIdx][0] = $seqNum++;
                $xlsData[$quota_type_idx][2][$pIdx][1] = $person[1];
                $xlsData[$quota_type_idx][2][$pIdx][2] = $person[2];
                $xlsData[$quota_type_idx][2][$pIdx][3] = $person[3];
                $xlsData[$quota_type_idx][2][$pIdx][4] = $person[4];
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] = $person['first']['result']['passed'] ? "通过" : "未通过";
                } else {
                    //处理职称1
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['firstTitle'], array_key_exists('doubletitle', $person), $person);
                    // 处理职称2
                    $xlsData[$quota_type_idx][2][$pIdx]['secondTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['secondTitle'], array_key_exists('doubletitle', $person), $person);
                }
            }
        } else {
            array_pop($xlsData);//该类型下没有人，废弃
        }
    }
    finalResultExport($exportSummary, $xlsName, $xlsData, $isFinalResult);
}

function prepare_final_export_keshi_ballotdao_result($xlsName, $vote_id, $judgetype, $isFinalResult = false) {
    $prefix = C('DB_PREFIX');
    //查询当前投票信息
    $vote_id_where['vote_id'] = $vote_id;
    $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();
    // 1. 准备一、二大标题信息
    if ($judgetype == '1') {//查询学科组通过的信息
        $exportSummary['judgetype'] = '学科组评审';
    } else {//查询
        $exportSummary['judgetype'] = '医院评审委员会评审';
    }

    $exportSummary['vote_name'] = $vote_info['vote_name']; //投票名称
    $exportSummary['category_id'] = $vote_info['category_id']; //组别Id
    $exportSummary['category_name'] = $vote_info['category_name']; //组别名称
    $exportSummary['professional_name'] = $vote_info['professional_name']; //职称

    // 2. 准备指标分组的列表信息；
    $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,applicant_style')->order('ordernumber asc')->where($vote_id_where)->select();

    $vote_quota_types = array();
    foreach ($voteApplySetTable as $quota_data) {
        $vote_quota_types[] = $quota_data;
    }
    $xlsData = array();
    foreach ($vote_quota_types as $quota_type_idx => $quota_type_value) {

        // 1. 通过vote_id,  quota_log 获取基本的groupTitle信息
        $vote_and_quota_where['vote_id'] = $vote_id;
        $vote_and_quota_where['quota_log'] = $quota_type_value['quota_log'];
        $groupTitleTbl = M('applicant')->field('apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->where($vote_and_quota_where)->limit(1)->select();

        $xlsData[$quota_type_idx][0]['category_name'] = $vote_info['category_name']; //组别
        $xlsData[$quota_type_idx][0]['professional_name'] = $vote_info['professional_name']; //职称
        $xlsData[$quota_type_idx][0]['quota_log'] = $quota_type_value['quota_log']; //指标类型
        $xlsData[$quota_type_idx][0]['applicant_style'] = $quota_type_value['applicant_style']; // 申请类别
        $xlsData[$quota_type_idx][0]['apply_total'] = $groupTitleTbl[0]['apply_total']; //总人数
        $xlsData[$quota_type_idx][0]['apply_select'] = $groupTitleTbl[0]['limit_num']; //评审委员会选出人数
        // FINISH
        //====================================================================

        // 2. 通过vote_id 获取 导入序号及人员基础信息；
        $personListTbl = M('applicant')->field('applicant_id,ordernumber,employee_id,office_name,applicant_name,apply_title')->order('ordernumber asc')->where($vote_and_quota_where)->select();
        $personList = array();
        $anyDoubleTitle = false;
        foreach ($personListTbl as $tblIdx => $singlePerson) {
            if (array_key_exists($singlePerson[ordernumber], $personList)) {
                $personList[$singlePerson[ordernumber]]['doubletitle'] = true;
                $personList[$singlePerson[ordernumber]][4] = $personList[$singlePerson[ordernumber]][4] . $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['title'] = $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['applicant_id'] = $singlePerson['applicant_id'];
                $personList[$singlePerson[ordernumber]]['second']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                    $xlsData[$quota_type_idx][1]['secondTitle'] = $personList[$singlePerson[ordernumber]]['second']['title'];
                    $anyDoubleTitle = true;
                }
            } else {
                $personList[$singlePerson[ordernumber]][1] = $singlePerson['employee_id']; //
                $personList[$singlePerson[ordernumber]][2] = $singlePerson['office_name']; //
                $personList[$singlePerson[ordernumber]][3] = $singlePerson['applicant_name']; //
                $personList[$singlePerson[ordernumber]][4] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['title'] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['applicant_id'] = $singlePerson['applicant_id']; //
                $personList[$singlePerson[ordernumber]]['first']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
//                $personList[$singlePerson[ordernumber]]['applicant_id'] = $singlePerson['applicant_id']; //TODO 比较applicant_id 决定双职称顺序
                if (!array_key_exists(1, $xlsData[$quota_type_idx])) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                }
            }
        }

        if ('2' == $judgetype && !$isFinalResult) {
            $personList = array_filter($personList, "_person_involved_committee_judge");
        }

        if (0 < count($personList)) {
            //排序
            $keshi = array();
            $keshi_names = array();
            $pos = 1;
            foreach ($personList as $tmpIdx => $person) {
                if (!array_key_exists($person[2], $keshi_names)){
                    $keshi_names[$person[2]] = $pos;
                    $pos=$pos+1;
                }

                $keshi[$tmpIdx] = $keshi_names[$person[2]];
            }

            // 终审结果排序（按通过轮次优先）
            $rating = array();
            foreach ($personList as $importOrder => $value) {
                $rating[$importOrder] = _calc_final_export_keshi_bubble_weight($value, $importOrder, $keshi[$importOrder]);
            }
            array_multisort($rating, SORT_DESC, $personList);

            // 3. 循环人员信息，获取其职位通过情况；
            $seqNum = 1;
            foreach ($personList as $pIdx => $person) {
                $xlsData[$quota_type_idx][2][$pIdx][0] = $seqNum++;
                $xlsData[$quota_type_idx][2][$pIdx][1] = $person[1];
                $xlsData[$quota_type_idx][2][$pIdx][2] = $person[2];
                $xlsData[$quota_type_idx][2][$pIdx][3] = $person[3];
                $xlsData[$quota_type_idx][2][$pIdx][4] = $person[4];
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] = $person['first']['result']['passed'] ? "通过" : "未通过";
                } else {
                    //处理职称1
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['firstTitle'], array_key_exists('doubletitle', $person), $person);
                    // 处理职称2
                    $xlsData[$quota_type_idx][2][$pIdx]['secondTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['secondTitle'], array_key_exists('doubletitle', $person), $person);
                }
            }
        } else {
            array_pop($xlsData);//该类型下没有人，废弃
        }
    }
    finalResultExport($exportSummary, $xlsName, $xlsData, $isFinalResult);
}

function prepare_final_export_final_result($xlsName, $vote_id, $judgetype, $isFinalResult = false) {
    $prefix = C('DB_PREFIX');
    //查询当前投票信息
    $vote_id_where['vote_id'] = $vote_id;
    $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();
    // 1. 准备一、二大标题信息
    if ($judgetype == '1') {//查询学科组通过的信息
        $exportSummary['judgetype'] = '学科组评审';
    } else {//查询
        $exportSummary['judgetype'] = '医院评审委员会评审';
    }

    $exportSummary['vote_name'] = $vote_info['vote_name']; //投票名称
    $exportSummary['category_id'] = $vote_info['category_id']; //组别Id
    $exportSummary['category_name'] = $vote_info['category_name']; //组别名称
    $exportSummary['professional_name'] = $vote_info['professional_name']; //职称

    // 2. 准备指标分组的列表信息；
    $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,applicant_style')->order('ordernumber asc')->where($vote_id_where)->select();

    $vote_quota_types = array();
    foreach ($voteApplySetTable as $quota_data) {
        $vote_quota_types[] = $quota_data;
    }
    $xlsData = array();
    foreach ($vote_quota_types as $quota_type_idx => $quota_type_value) {

        // 1. 通过vote_id,  quota_log 获取基本的groupTitle信息
        $vote_and_quota_where['vote_id'] = $vote_id;
        $vote_and_quota_where['quota_log'] = $quota_type_value['quota_log'];
        $groupTitleTbl = M('applicant')->field('apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->where($vote_and_quota_where)->limit(1)->select();

        $xlsData[$quota_type_idx][0]['category_name'] = $vote_info['category_name']; //组别
        $xlsData[$quota_type_idx][0]['professional_name'] = $vote_info['professional_name']; //职称
        $xlsData[$quota_type_idx][0]['quota_log'] = $quota_type_value['quota_log']; //指标类型
        $xlsData[$quota_type_idx][0]['applicant_style'] = $quota_type_value['applicant_style']; // 申请类别
        $xlsData[$quota_type_idx][0]['apply_total'] = $groupTitleTbl[0]['apply_total']; //总人数
        $xlsData[$quota_type_idx][0]['apply_select'] = $groupTitleTbl[0]['limit_num']; //评审委员会选出人数
        // FINISH
        //====================================================================

        // 2. 通过vote_id 获取 导入序号及人员基础信息；
        $personListTbl = M('applicant')->field('applicant_id,ordernumber,employee_id,office_name,applicant_name,apply_title')->order('ordernumber asc')->where($vote_and_quota_where)->select();
        $personList = array();
        $anyDoubleTitle = false;
        foreach ($personListTbl as $tblIdx => $singlePerson) {
            if (array_key_exists($singlePerson[ordernumber], $personList)) { //双报人
                $personList[$singlePerson[ordernumber]]['doubletitle'] = true;
                $personList[$singlePerson[ordernumber]][4] = $personList[$singlePerson[ordernumber]][4] . $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['title'] = $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['applicant_id'] = $singlePerson['applicant_id'];
                $personList[$singlePerson[ordernumber]]['second']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                    $xlsData[$quota_type_idx][1]['secondTitle'] = $personList[$singlePerson[ordernumber]]['second']['title'];
                    $anyDoubleTitle = true;
                }
            } else {
                $personList[$singlePerson[ordernumber]][1] = $singlePerson['employee_id']; //
                $personList[$singlePerson[ordernumber]][2] = $singlePerson['office_name']; //
                $personList[$singlePerson[ordernumber]][3] = $singlePerson['applicant_name']; //
                $personList[$singlePerson[ordernumber]][4] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['title'] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['applicant_id'] = $singlePerson['applicant_id']; //
                $personList[$singlePerson[ordernumber]]['first']['result'] = _title_pass_info($singlePerson['applicant_id'], $judgetype);
//                $personList[$singlePerson[ordernumber]]['applicant_id'] = $singlePerson['applicant_id']; //TODO 比较applicant_id 决定双职称顺序
                if (!array_key_exists(1, $xlsData[$quota_type_idx])) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                }
            }
        }

        if ('2' == $judgetype && !$isFinalResult) {
            $personList = array_filter($personList, "_person_involved_committee_judge");
        }

        if (0 < count($personList)) {
            // 终审结果排序（按通过轮次优先）
            $rating = array();
            foreach ($personList as $importOrder => $value) {
                $rating[$importOrder] = _calc_final_export_bubble_weight($value, $importOrder);
            }
            array_multisort($rating, SORT_DESC, $personList);

            // 3. 循环人员信息，获取其职位通过情况；
            $seqNum = 1;
            foreach ($personList as $pIdx => $person) {
                $xlsData[$quota_type_idx][2][$pIdx][0] = $seqNum++;
                $xlsData[$quota_type_idx][2][$pIdx][1] = $person[1];
                $xlsData[$quota_type_idx][2][$pIdx][2] = $person[2];
                $xlsData[$quota_type_idx][2][$pIdx][3] = $person[3];
                $xlsData[$quota_type_idx][2][$pIdx][4] = $person[4];
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] = $person['first']['result']['passed'] ? "通过" : "未通过";
                } else {
                    //处理职称1
                    $xlsData[$quota_type_idx][2][$pIdx]['firstTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['firstTitle'], array_key_exists('doubletitle', $person), $person);
                    // 处理职称2
                    $xlsData[$quota_type_idx][2][$pIdx]['secondTitle'] =
                        _result_string_for_double($xlsData[$quota_type_idx][1]['secondTitle'], array_key_exists('doubletitle', $person), $person);
                }
            }
        } else {
            array_pop($xlsData);//该类型下没有人，废弃
        }
    }
    finalResultExport($exportSummary, $xlsName, $xlsData, $isFinalResult);
}

function _person_involved_committee_judge($person) {
    if (_applicant_id_involved_committee($person['first']['applicant_id'])) {
        return true;
    }
    if (array_key_exists('doubletitle', $person)
        && _applicant_id_involved_committee($person['second']['applicant_id'])) {
        return true;
    }
    return false;
}

function _applicant_id_involved_committee($applicantId) {
    $roundWhere['applicant_id'] = $applicantId;
    $roundWhere['applicant_status'] = array('in', '3, 4, 5');
    $roundDetailTbl = M('rounddetail')->field('applicant_status')->where($roundWhere)->select();
    return !empty($roundDetailTbl);
}

function _title_pass_info($applicant_id, $judge_type) {
    $roundWhere['applicant_id'] = $applicant_id;
    $roundDetailTbl = M('rounddetail')->field('qw_rounddetail.round_id, select_total, applicant_status, round')->join("qw_voteround ON qw_voteround.round_id = qw_rounddetail.round_id")->where($roundWhere)->select();
    foreach ($roundDetailTbl as $tblRow) {
        if (('2' == $tblRow['applicant_status'] && 1 == $judge_type)
            || ('5' == $tblRow['applicant_status'] && 2 == $judge_type)) {
            return array('passed' => true, 'passed_round' => $tblRow['round'], 'select_total' => $tblRow['select_total']);
        }
    }
    return array("passed" => false);
}

function _title_pass_info_new($rounds, $applicant_id, $judge_type) {
    $roundDetailTbl = $rounds[$applicant_id];
    foreach ($roundDetailTbl as $tblRow) {
        if (('2' == $tblRow['applicant_status'] && 1 == $judge_type)
            || ('5' == $tblRow['applicant_status'] && 2 == $judge_type)) {
            return array('passed' => true, 'passed_round' => $tblRow['round'], 'select_total' => $tblRow['select_total']);
        }
    }
    return array("passed" => false);
}

function _result_string_for_double($title, $hasDouble, $person) {
    if ($title == $person['first']['title'] ||($person['first']['title']=="副研究员" && $title=="副教授")) {
        return $person['first']['result']['passed'] ? "通过" : "未通过";
    } else if ($hasDouble) {
        return $person['second']['result']['passed'] ? "通过" : "未通过";
    }
    return "////";
}

/**
 * 计算冒泡排序的权重；默认是INT_MAX，需要往后排，然后取职称通过的最小轮次，小的靠前
 * @param $person
 * @param $importOrder
 * @return int|mixed
 */
function _calc_final_export_bubble_weight($person, $importOrder) {
    $base = 1000 - $importOrder;
    $roundWeight = 0;
    if ($person['first']['result']['passed']) {
        $roundWeight = (100 - $person['first']['result']['passed_round']) * 50000
            + $person['first']['result']['select_total'] * 1000;
    }
    if (array_key_exists('doubletitle', $person) && $person['doubletitle']) {
        if ($person['second']['result']['passed']) {
            $roundWeight = max($roundWeight,
                ((100 - $person['second']['result']['passed_round']) * 50000
                    + $person['second']['result']['select_total'] * 1000));
        }
    }
    return $base + $roundWeight;
}

function _calc_final_export_keshi_bubble_weight($person, $importOrder, $keshi) {
    $base = 1000 - $importOrder;
    $roundWeight = 0;
    if ($person['first']['result']['passed']) {
        $roundWeight = (100 - $person['first']['result']['passed_round']) * 50000
            + $person['first']['result']['select_total'] * 1000;
    }
    if (array_key_exists('doubletitle', $person) && $person['doubletitle']) {
        if ($person['second']['result']['passed']) {
            $roundWeight = max($roundWeight,
                ((100 - $person['second']['result']['passed_round']) * 50000
                    + $person['second']['result']['select_total'] * 1000));
        }
    }
    return $base + $roundWeight + (100-$keshi) * 10000000;
}

function get_chinese_vote_status_word($value) {
    switch ($value) {
        case '0':
        case '3':
            return '未通过';
        case '1':
        case '4':
            return '进入下一轮';
        case '2':
        case '5':
            return '通过';
        default:
            return '未知';
    }
}

//导出投票轮次的评委信息
function prepare_and_export_round_data($round_id, $vote_id, $exportRoundType) {
    $prefix = C('DB_PREFIX');
    //查询当前投票信息
    $vote_id_where['vote_id'] = $vote_id;
    $vote_info = M('vote')->field("{$prefix}vote.*")->where($vote_id_where)->find();

    //查询当前轮次信息
    $round_id_where['round_id'] = $round_id;
    $round_info = M('voteround')->field("{$prefix}voteround.*")->where($round_id_where)->find();

    $judgetype = $round_info['judgetype_id'];//评审类型id

    $voters_where['is_toup'] = 0;
    $voters_where['round_id'] = $round_id;

    $voters_list = array();
    $votersM = M("votedetail")->distinct(true)->field('judge_name')->where($voters_where)->order('judge_name')->select();
    foreach ($votersM as $judgeName) {
        $voters_list[] = $judgeName['judge_name'];
    }

    // 1. 准备一、二大标题基本信息
    if ($judgetype == '1') {//查询学科组通过的信息
        $exportSummary['judgetype'] = '学科组评审';
    } else {//查询
        $exportSummary['judgetype'] = '医院评审委员会评审';
    }

    $exportSummary['vote_name'] = $vote_info['vote_name']; //投票名称
    $exportSummary['category_id'] = $vote_info['category_id']; //组别Id
    $exportSummary['category_name'] = $vote_info['category_name']; //组别
    $exportSummary['professional_name'] = $vote_info['professional_name']; //职称
    $exportSummary['round_num'] = $round_info['round'];

    // 2. 准备指标分组的列表信息；
    $voteApplySetTable = M('applicant')->distinct(true)->field('quota_log,applicant_style')->order('ordernumber asc')->where($vote_id_where)->select();

    $vote_quota_types = array();
    foreach ($voteApplySetTable as $quota_data) {
        $vote_quota_types[] = $quota_data;
    }
    $xlsData = array();
    foreach ($vote_quota_types as $quota_type_idx => $quota_type_value) {
        //根据round_id quota_log查询出当前轮次当前类型的人数
        $rounddetailwhere = '1 = 1 ';
        $rounddetailwhere .= "and {$prefix}rounddetail.round_id = {$round_id} ";
        $rounddetailwhere .= "and {$prefix}applicant.quota_log = '{$quota_type_value['quota_log']}' ";
        $vote_roundapplicanlist = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log")->where($rounddetailwhere)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->order('qw_applicant.ordernumber asc')->select();
        //去除双职称重复的名字
        $vote_roundapplicanlist = remove_duplicate($vote_roundapplicanlist);

        //查询改类型之前轮次已通过的人数
        $havetongguoapplicant = 0;
        $havetongguoapplicant_backinfo = is_beforeround_pass($vote_id, $judgetype, $quota_type_value['quota_log'], $round_id);
        if (!empty($havetongguoapplicant_backinfo)) {
            $havetongguoapplicant = count($havetongguoapplicant_backinfo);
        }

        // 1. 通过vote_id,  quota_log 获取基本的groupTitle信息
        $vote_and_quota_where['vote_id'] = $vote_id;
        $vote_and_quota_where['quota_log'] = $quota_type_value['quota_log'];
        $groupTitleTbl = M('applicant')->field('apply_total,' . ($judgetype == 1 ? "subject_limit as limit_num" : "committee_limit as limit_num"))->where($vote_and_quota_where)->limit(1)->select();

        //选出的人数减去之前轮次已通过的人数即为最终选出的人数
        $last_select_applicant = min(count($vote_roundapplicanlist), intval($groupTitleTbl[0]['limit_num']) - intval($havetongguoapplicant));

        $xlsData[$quota_type_idx][0]['category_name'] = $vote_info['category_name']; //组别
        $xlsData[$quota_type_idx][0]['professional_name'] = $vote_info['professional_name']; //职称
        $xlsData[$quota_type_idx][0]['quota_log'] = $quota_type_value['quota_log']; //指标类型
        $xlsData[$quota_type_idx][0]['applicant_style'] = $quota_type_value['applicant_style']; // 申请类别
        $xlsData[$quota_type_idx][0]['apply_total'] = min($groupTitleTbl[0]['apply_total'], count($vote_roundapplicanlist)); //总人数
        $xlsData[$quota_type_idx][0]['apply_select'] = intval($last_select_applicant); //评审委员会选出人数
        // FINISH
        //====================================================================

        // 2. 通过vote_id 获取 导入序号及人员基础信息;
        $personListTbl = M('applicant')->field('applicant_id,ordernumber,employee_id,office_name,applicant_name,apply_title')->where($vote_and_quota_where)->order('qw_applicant.applicant_id asc')->select();
        
        //$personListTbl = M('applicant')->field('applicant_id,ordernumber,employee_id,office_name,applicant_name,apply_title')->where($vote_and_quota_where)->select();

        //通过round_id 筛选该轮次中的人员信息
        $personShaixuanhouListTbl = get_round_id_applicant($personListTbl, $round_id);

        $personList = array();
        $anyDoubleTitle = false;
        foreach ($personShaixuanhouListTbl as $tblIdx => $singlePerson) {
            if (array_key_exists($singlePerson[ordernumber], $personList)) {
                $personList[$singlePerson[ordernumber]]['doubletitle'] = true;
                $personList[$singlePerson[ordernumber]][4] = $personList[$singlePerson[ordernumber]][4] . $singlePerson['apply_title'];
                $personList[$singlePerson[ordernumber]]['second']['title'] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['second']['select_total'] = $singlePerson['select_total']; //
                $personList[$singlePerson[ordernumber]]['second']['applicant_status'] = $singlePerson['applicant_status']; //
                $personList[$singlePerson[ordernumber]]['second']['judgeList'] = get_judgesArr($singlePerson['rounddetail_id']);
                if (!$anyDoubleTitle) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                    $xlsData[$quota_type_idx][1]['secondTitle'] = $personList[$singlePerson[ordernumber]]['second']['title'];
                    $anyDoubleTitle = true;
                }
            } else {
                $personList[$singlePerson[ordernumber]][1] = $singlePerson['employee_id']; //
                $personList[$singlePerson[ordernumber]][2] = $singlePerson['office_name']; //
                $personList[$singlePerson[ordernumber]][3] = $singlePerson['applicant_name']; //
                $personList[$singlePerson[ordernumber]][4] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['title'] = $singlePerson['apply_title']; //
                $personList[$singlePerson[ordernumber]]['first']['select_total'] = $singlePerson['select_total']; //
                $personList[$singlePerson[ordernumber]]['first']['applicant_status'] = $singlePerson['applicant_status']; //
                $personList[$singlePerson[ordernumber]]['first']['judgeList'] = get_judgesArr($singlePerson['rounddetail_id']);
                if (!array_key_exists(1, $xlsData[$quota_type_idx])) {
                    $xlsData[$quota_type_idx][1]['firstTitle'] = $personList[$singlePerson[ordernumber]]['first']['title'];
                }
            }
        }
        // PersonList 排序
        // TODO
        // TODO

        // 3. 循环人员信息，获取其职位投票人信息；
        if (0 < count($personList)) {

            $seqNum = 1;
            foreach ($personList as $pIdx => $person) {
                $xlsData[$quota_type_idx][2][$pIdx][0] = $seqNum++;
                $xlsData[$quota_type_idx][2][$pIdx][1] = $person[1];
                $xlsData[$quota_type_idx][2][$pIdx][2] = $person[2];
                $xlsData[$quota_type_idx][2][$pIdx][3] = $person[3];
                $xlsData[$quota_type_idx][2][$pIdx][4] = $person[4];
                $xlsData[$quota_type_idx][2][$pIdx]['first'] = $person['first'];
                if (array_key_exists('doubletitle', $person)) {
                    $xlsData[$quota_type_idx][2][$pIdx]['second'] = $person['second'];
                }
            }
        } else {
            array_pop($xlsData);//该类型下没有人，废弃
        }

    }

    if ('1' == $exportRoundType) {
        write_round_vote_result_excel($exportSummary, $xlsData);
    } else if ('2' == $exportRoundType) {
        write_round_vote_detail_excel($voters_list, $exportSummary, $xlsData);
    }
}

function get_round_id_applicant($personListTbl, $round_id) {
    //通过循环导入的职称判断该职称是否在该轮次中,并且存入该申请人的轮次详情信息返回
    $personShaixuanhouListTbl = array();
    foreach ($personListTbl as $personkey => $personvalue) {
        $round_applicant_where['applicant_id'] = $personvalue['applicant_id'];
        $round_applicant_where['round_id'] = $round_id;
        $rounddetails_applicant = M('rounddetail')->field('rounddetail_id,applicant_id,round_id,select_total,applicant_status')->where($round_applicant_where)->find();
        if (!empty($rounddetails_applicant)) {
            $personvalue['rounddetail_id'] = $rounddetails_applicant['rounddetail_id'];
            $personvalue['round_id'] = $rounddetails_applicant['round_id'];
            $personvalue['select_total'] = $rounddetails_applicant['select_total'];
            $personvalue['applicant_status'] = $rounddetails_applicant['applicant_status'];
            $personShaixuanhouListTbl[] = $personvalue;
        }
    }
    return $personShaixuanhouListTbl;
}

function get_judgesArr($rounddetail_id) {
    $lastvotedetails_judge_name = array();
    //根据$rounddetail_id查询出该职称下所有评委信息
    $rounddetail_id_where['rounddetail_id'] = $rounddetail_id;
    $rounddetail_id_where['is_toup'] = '0';//表示成功的投票；
    $votedetails_judge_name = M('votedetail')->field('judge_name')->where($rounddetail_id_where)->group('judge_id')->select();
    foreach ($votedetails_judge_name as $key => $value) {
        $lastvotedetails_judge_name[] = $value['judge_name'];
    }
    return $lastvotedetails_judge_name;
}

function write_round_vote_result_excel($exportSummary, $expTableData) {
    $xlsTitle = iconv('utf-8', 'gb2312', '投票结果'); //文件名称
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new PHPExcel();
    $excelColumns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE',
        'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT',
        'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    $chineseSN = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二', '十三', '十四', '十五', '十六');

    $lastRowNum = 1;//excel 行从1开始

    $objPHPExcel->setActiveSheetIndex(0);// 准备操作第一个工作表
    // TODO 设置全局格式：所有单元格居中
//    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getDefaultStyle()->getFont()->setName('宋体');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(14);
    $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);

    // 导出Excel的大标题
    _write_round_vote_result_title($lastRowNum, $exportSummary, $objPHPExcel, $excelColumns);

    // 循环写Excel的分组评审结果
    $groupIdx = 0;
    foreach ($expTableData as $groupInfo) {
        _write_round_vote_result_group($lastRowNum, $chineseSN[$groupIdx++], $groupInfo, $objPHPExcel, $excelColumns);
    }

    // 表格填充部分增加border（不包含title）
    $objPHPExcel->getActiveSheet()->getStyle("A3:" . $excelColumns[6] . ($lastRowNum - 1))
        ->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

    //评委签名
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum + 1), '评委签名：');

    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[4])->setWidth(16); //职称字段
    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[6])->setWidth(12); //评委结果-状态

    $fileName = $xlsTitle . "_" . date("YmdHis");
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $fileName . '.xlsx"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx"); //attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

function _write_round_vote_result_title(&$lastRowNum, $exportSummary, $objPHPExcel, $excelColumns) {
    // 第一行大标题
    $firstTitle = mb_substr($exportSummary['vote_name'], 0, 4) . '年协和医院' . $exportSummary['judgetype']
        . (intval($exportSummary['category_id']) <= 3 ? '高级' : (intval($exportSummary['category_id'])>=7?'二三级':'中初级')) . '第' . $exportSummary['round_num'] . '轮投票结果';
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $firstTitle);//一级大标题信息
    set_cell_style_center_alignment($excelColumns[0] . $lastRowNum, $objPHPExcel);
    set_cell_style_font($excelColumns[0] . $lastRowNum, $objPHPExcel, TRUE, 16);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[6] . $lastRowNum);
    $lastRowNum++;
    // 第二行大标题
    $secondTitle = $exportSummary['judgetype'] . '   ' . $exportSummary['professional_name'] . '：' . $exportSummary['category_name'];
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $secondTitle);//二级大标题信息
    set_cell_style_center_alignment($excelColumns[0] . $lastRowNum, $objPHPExcel);
    set_cell_style_font($excelColumns[0] . $lastRowNum, $objPHPExcel, TRUE, 14);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[6] . $lastRowNum);
    $lastRowNum++;
}

function _write_round_vote_result_group(&$lastRowNum, $snIinChinese, $groupInfo, $objPHPExcel, $excelColumns) {
    // 写组标题，标题需要跨行合并
    $groupTitle = $snIinChinese . '、' . $groupInfo[0]['category_name'] . '：申请' . $groupInfo[0]['applicant_style']
        . '（' . $groupInfo[0]['quota_log'] . '，' . $groupInfo[0]['apply_total'] . '人中选出' . $groupInfo[0]['apply_select'] . '人）';

    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $groupTitle);
    $objPHPExcel->getActiveSheet()->getStyle($excelColumns[0] . $lastRowNum)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[6] . $lastRowNum);// 跨行合并
    $lastRowNum++;  //写完标题以后，行号加1

    // 写列名信息
    _write_round_vote_result_group_title($lastRowNum, $excelColumns, $objPHPExcel);

    // 写当前指标分组下的每个人的信息
    foreach ($groupInfo[2] as $person) {
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . ($lastRowNum), $person[0]);
        set_cell_number_string_text_format($excelColumns[1] . ($lastRowNum), $objPHPExcel, $person[1]);//employee id
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[2] . ($lastRowNum), $person[2]);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[3] . ($lastRowNum), $person[3]);

        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum), $person['first']['title']);
        set_cell_number_string_text_format($excelColumns[5] . ($lastRowNum), $objPHPExcel, $person['first']['select_total']);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[6] . ($lastRowNum), get_chinese_vote_status_word($person['first']['applicant_status']));

        $secondExist = array_key_exists('second', $person);
        if ($secondExist) {
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[0] . ($lastRowNum + 1));// 跨行合并
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[1] . $lastRowNum . ":" . $excelColumns[1] . ($lastRowNum + 1));// 跨行合并
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[2] . $lastRowNum . ":" . $excelColumns[2] . ($lastRowNum + 1));// 跨行合并
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[3] . $lastRowNum . ":" . $excelColumns[3] . ($lastRowNum + 1));// 跨行合并

            $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum + 1), $person['second']['title']);
            set_cell_number_string_text_format($excelColumns[5] . ($lastRowNum + 1), $objPHPExcel, $person['second']['select_total']);
            $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[6] . ($lastRowNum + 1), get_chinese_vote_status_word($person['second']['applicant_status']));
        }
        $lastRowNum += ($secondExist ? 2 : 1); // 没写完一行某人的信息，变更行号
    }
}

function _write_round_vote_result_group_title(&$lastRowNum, $excelColumns, $objPHPExcel) {
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, '序号');//0
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[1] . $lastRowNum, '职工编码');//1
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[2] . $lastRowNum, '科室');//2
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[3] . $lastRowNum, '姓名');//3
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . $lastRowNum, '申报职称');//4
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . $lastRowNum, '票数');//5
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[6] . $lastRowNum, '评审状态');//6
    $lastRowNum++; //
}


function write_round_vote_detail_excel($voters_list, $exportSummary, $expTableData) {

    $xlsTitle = iconv('utf-8', 'gb2312', '投票详情'); //文件名称
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new PHPExcel();
    $excelColumns = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N',
        'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE',
        'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT',
        'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
    $chineseSN = array('一', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二', '十三', '十四', '十五', '十六');

    // TODO 设置全局格式：所有单元格居中
//    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    $objPHPExcel->getDefaultStyle()->getFont()->setName('宋体');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(14);
    $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);

    foreach ($voters_list as $sheetIdx => $vote_name) {
        _write_vote_detail_single_voter($sheetIdx, $vote_name, $exportSummary, $expTableData, $objPHPExcel, $excelColumns, $chineseSN);
    }

    $objPHPExcel->setActiveSheetIndex(0); //保存前，让第一个工作表保持激活；
    $fileName = $xlsTitle . "_" . date("YmdHis");
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $fileName . '.xlsx"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx"); //attachment新窗口打印inline本窗口打印
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

function _write_vote_detail_single_voter($sheetIdx, $vote_i, $exportSummary, $expTableData, $objPHPExcel, $excelColumns, $chineseSN) {
    $lastRowNum = 1;//excel 行从1开始

    if (0 < $sheetIdx) {
        $objPHPExcel->createSheet();
    }
    $objPHPExcel->setActiveSheetIndex($sheetIdx);// 准备操作第一个工作表
    $objPHPExcel->getActiveSheet()->setTitle($vote_i);

    // 导出Excel的大标题
    _write_vote_detail_title($lastRowNum, $exportSummary, $objPHPExcel, $excelColumns);

    // 循环写Excel的分组评审结果
    $groupIdx = 0;
    foreach ($expTableData as $groupInfo) {
        _write_vote_detail_group($vote_i, $lastRowNum, $chineseSN[$groupIdx++], $groupInfo, $objPHPExcel, $excelColumns);
    }

    // 表格填充部分增加border（不包含title）
    $objPHPExcel->getActiveSheet()->getStyle("A3:" . $excelColumns[5] . ($lastRowNum - 1))
        ->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

    //评委签名
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum + 1), '评委签名：');
//
//    // 微调单元格宽度
    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[4])->setWidth(16);//职称
    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[5])->setWidth(12);//投票状态
//    $objPHPExcel->getActiveSheet()->getColumnDimension($excelColumns[6])->setWidth(10);
}

function _write_vote_detail_title(&$lastRowNum, $exportSummary, $objPHPExcel, $excelColumns) {
    // 第一行大标题
    $firstTitle = mb_substr($exportSummary['vote_name'], 0, 4) . '年协和医院' . $exportSummary['judgetype']
        . (intval($exportSummary['category_id']) <= 3 ? '高级' : (intval($exportSummary['category_id'])>=7?'二三级':'中初级')) . '第' . $exportSummary['round_num'] . '轮评委投票单';
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $firstTitle);//一级大标题信息
    set_cell_style_center_alignment($excelColumns[0] . $lastRowNum, $objPHPExcel);
    set_cell_style_font($excelColumns[0] . $lastRowNum, $objPHPExcel, TRUE, 16);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[5] . $lastRowNum);
    $lastRowNum++;
    // 第二行大标题
    $secondTitle = $exportSummary['judgetype'] . '   ' . $exportSummary['professional_name'] . '：' . $exportSummary['category_name'];
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $secondTitle);//二级大标题信息
    set_cell_style_center_alignment($excelColumns[0] . $lastRowNum, $objPHPExcel);
    set_cell_style_font($excelColumns[0] . $lastRowNum, $objPHPExcel, TRUE, 14);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[5] . $lastRowNum);
    $lastRowNum++;
}

function _write_vote_detail_group($vote_i, &$lastRowNum, $snIinChinese, $groupInfo, $objPHPExcel, $excelColumns) {
    // 写组标题，标题需要跨行合并
    $groupTitle = $snIinChinese . '、' . $groupInfo[0]['category_name'] . '：申请' . $groupInfo[0]['applicant_style']
        . '（' . $groupInfo[0]['quota_log'] . '，' . $groupInfo[0]['apply_total'] . '人中选出' . $groupInfo[0]['apply_select'] . '人）';

    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, $groupTitle);
    $objPHPExcel->getActiveSheet()->getStyle($excelColumns[0] . $lastRowNum)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[5] . $lastRowNum);// 跨行合并
    $lastRowNum++;  //写完标题以后，行号加1

    // 写列名信息
    _write_vote_detail_group_title($vote_i, $lastRowNum, $excelColumns, $objPHPExcel);

    // 写当前指标分组下的每个人的信息
    foreach ($groupInfo[2] as $person) {
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . ($lastRowNum), $person[0]);
        set_cell_number_string_text_format($excelColumns[1] . ($lastRowNum), $objPHPExcel, $person[1]);//employee id
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[2] . ($lastRowNum), $person[2]);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[3] . ($lastRowNum), $person[3]);

        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum), $person['first']['title']);
        $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . ($lastRowNum), in_array($vote_i, $person['first']['judgeList']) ? '同意' : '不同意');

        $secondExist = array_key_exists('second', $person);
        if ($secondExist) {
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[0] . $lastRowNum . ":" . $excelColumns[0] . ($lastRowNum + 1));// 跨行合并
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[1] . $lastRowNum . ":" . $excelColumns[1] . ($lastRowNum + 1));// 跨行合并
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[2] . $lastRowNum . ":" . $excelColumns[2] . ($lastRowNum + 1));// 跨行合并
            $objPHPExcel->getActiveSheet()->mergeCells($excelColumns[3] . $lastRowNum . ":" . $excelColumns[3] . ($lastRowNum + 1));// 跨行合并

            $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . ($lastRowNum + 1), $person['second']['title']);
            $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . ($lastRowNum + 1), in_array($vote_i, $person['second']['judgeList']) ? '同意' : '不同意');
        }
        $lastRowNum += ($secondExist ? 2 : 1); // 没写完一行某人的信息，变更行号
    }
}

function _write_vote_detail_group_title($vote_i, &$lastRowNum, $excelColumns, $objPHPExcel) {
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[0] . $lastRowNum, '序号');//0
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[1] . $lastRowNum, '职工编码');//1
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[2] . $lastRowNum, '科室');//2
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[3] . $lastRowNum, '姓名');//3
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[4] . $lastRowNum, '申报职称');//4,5
    $objPHPExcel->getActiveSheet()->setCellValue($excelColumns[5] . $lastRowNum, '评委' . $vote_i . '投票赞成情况');//6,7
    $lastRowNum++; //
}

/*
 * _applicant_pass_info() _applicant_have_in() is_dobule_pass()次三个方法用于投票界面 轮次界面 查询界面的双职称显示以及是否计数双职称方法
 */
//判断是否已经通过评审  
function _applicant_pass_info($applicant_status, $judge_type) {
    if (('2' == $applicant_status && 1 == $judge_type) || ('3' == $applicant_status && 1 == $judge_type) || ('4' == $applicant_status && 2 == $judge_type) || ('5' == $applicant_status)) {
        return array('passed' => '10');//表示通过
    }
    if (('0' == $applicant_status && 1 == $judge_type) || ('3' == $applicant_status && 2 == $judge_type)) {
        return array('passed' => '12');//表示不通过
    }
    return false;
}

//判断改职称是否存在该轮次中
function _applicant_have_in($round_id, $applicant_id) {
    $roundWhere['applicant_id'] = $applicant_id;
    $roundWhere['round_id'] = $round_id;
    //$roundWhere['applicant_status'] = array('in', '0,1,3,4');//筛选已经通过的人
    $roundDetailfind = M('rounddetail')->where($roundWhere)->find();
    if (empty($roundDetailfind)) {//表示不存在
        //查询改职称最后一轮的轮次状态信息
        $applicantwhere['applicant_id'] = $applicant_id;
        $roundDetailfind = M('rounddetail')->where($applicantwhere)->order('rounddetail_id desc')->find();
        $roundDetailfind['is_notnowround'] = '1';
    }
    return $roundDetailfind;
}

//判断该人员是否是双职称并且已经有一个职称通过了
function is_dobule_pass($rounddetail_id, $vote_id, $judge_type) {
    $roundDetailWhere['rounddetail_id'] = $rounddetail_id;
    $roundDetailfind = M('rounddetail')->where($roundDetailWhere)->find();
    //根据employee_id vote_id查询是否是双职称
    $dobule_applicant_where['employee_id'] = $roundDetailfind['employee_id'];
    $dobule_applicant_where['vote_id'] = $vote_id;
    $dobule_applicantArr = M('applicant')->where($dobule_applicant_where)->order('applicant_id asc')->select();
    if (count($dobule_applicantArr) > 1) {
        foreach ($dobule_applicantArr as $m => $n) {
            $roundWhere['applicant_id'] = $n['applicant_id'];
            $roundDetailTbl = M('rounddetail')->field('round_id, applicant_status')->where($roundWhere)->select();
            foreach ($roundDetailTbl as $tblRow) {
                if (('2' == $tblRow['applicant_status'] && 1 == $judge_type)
                    || ('5' == $tblRow['applicant_status'] && 2 == $judge_type)) {
                    return true;
                }
            }
        }
    }
    return false;
}

//判断该人员是否是双职称并且已经有一个职称通过了
function is_dobule_pass_new($app, $rounds, $employee_id,  $judge_type) {
    $dobule_applicantArr = $app[$employee_id];
    if (count($dobule_applicantArr) > 1) {
        foreach ($dobule_applicantArr as $m => $n) {
            $rounddetail = $rounds[$n['applicant_id']];
            foreach ($rounddetail as $key => $value) {
                if (('2' == $value['applicant_status'] && 1 == $judge_type)
                    || ('5' == $value['applicant_status'] && 2 == $judge_type)) {
                    return true;
                }
            }
        }
    }
    return false;
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


//判断该投票已经通过多少人了
function is_beforeround_pass($vote_id, $judge_type, $quota_log, $round_id) {
    $prefix = C('DB_PREFIX');
    $beforeround_pass_applicanlist = array();
    //查询该类型下人数
    $rounddetailwhere = '1 = 1 ';
    $rounddetailwhere .= "and {$prefix}applicant.vote_id = {$vote_id} ";
    $rounddetailwhere .= "and {$prefix}applicant.quota_log = '{$quota_log}' ";
    $rounddetailwhere .= "and {$prefix}rounddetail.round_id < {$round_id} ";  //轮次小于本轮次的
    $rounddetailwhere .= "and {$prefix}voteround.judgetype_id = '{$judge_type}' ";//区分评审类型
    if ($judge_type == '1') {
        $rounddetailwhere .= "and {$prefix}rounddetail.applicant_status = '2' ";
    } else {
        $rounddetailwhere .= "and {$prefix}rounddetail.applicant_status = '5' ";
    }
    $beforeround_pass_applicanlist = M('rounddetail')->field("{$prefix}rounddetail.*,{$prefix}applicant.applicant_id,{$prefix}applicant.ordernumber,{$prefix}applicant.office_name,{$prefix}applicant.quota_log,{$prefix}voteround.round_id,{$prefix}voteround.judgetype_id")->where($rounddetailwhere)->join("{$prefix}applicant ON {$prefix}applicant.applicant_id = {$prefix}rounddetail.applicant_id")->join("{$prefix}voteround ON {$prefix}voteround.round_id = {$prefix}rounddetail.round_id")->order('qw_applicant.ordernumber asc')->select();
    //去重
    $beforeround_pass_applicanlist = remove_duplicate($beforeround_pass_applicanlist);
    return $beforeround_pass_applicanlist;

}
