<?php

/**
 *
 * 获取用户信息
 *
 **/
function member($uid, $field = false)
{
    $model = M('judges');
    if ($field) {
        return $model->field($field)->where(array('user_id' => $uid))->find();
    } else {
        return $model->where(array('user_id' => $uid))->find();
    }
}