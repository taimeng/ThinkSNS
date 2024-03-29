<?php
return array (
  'announcement' => 
  array (
    'is_open' => '1',
    'content' => '欢迎使用SociaxTeam',
  ),
  'nav' => 
  array (
    'navi_name' => '123',
    'attach' => '',
    'app_name' => '',
    'url' => '123',
    'target' => 'appoint',
    'status' => 'appoint',
    'position' => '',
    'guest' => 'appoint',
    'is_app_navi' => 'appoint',
    'parent_id' => '123',
    'order_sort' => '123',
  ),
  'attach' => 
  array (
    'attach_path_rule' => 'Y/md/H/',
    'attach_max_size' => '15',
    'attach_allow_extension' => 'gif,png,jpeg,bmp,zip,rar,doc,xls,ppt,docx,xlsx,pptx,pdf,jpg',
  ),
  'invite' => 
  array (
    'send_email_num' => '3',
    'send_link_num' => '3',
  ),
  'cloudimage' => 
  array (
    'cloud_image_open' => '0',
    'cloud_image_api_url' => 'http://v0.api.upyun.com',
    'cloud_image_bucket' => '',
    'cloud_image_form_api_key' => '',
    'cloud_image_prefix_urls' => '',
    'cloud_image_admin' => '',
    'cloud_image_password' => '',
  ),
  'cloudattach' => 
  array (
    'cloud_attach_open' => '0',
    'cloud_attach_api_url' => 'http://v0.api.upyun.com',
    'cloud_attach_bucket' => '',
    'cloud_attach_form_api_key' => '',
    'cloud_attach_prefix_urls' => '',
    'cloud_attach_admin' => '',
    'cloud_attach_password' => '',
  ),
  'seo_feed_topic' => 
  array (
    'name' => '话题页',
    'title' => '{topicName}',
    'keywords' => '{topicName}',
    'des' => '{topicNote}-{topicDes}-{lastTopic}',
    'node' => '',
    'sub' => '保存',
  ),
  'seo_feed_detail' => 
  array (
    'name' => '微博详情页',
    'title' => '{uname}的微博',
    'keywords' => '{uname}的微博',
    'des' => '{uname}的微博：{content}',
    'node' => '',
    'sub' => '保存',
  ),
  'seo_user_profile' => 
  array (
    'name' => '个人主页',
    'title' => '{uname}的主页',
    'keywords' => '{uname}的主页',
    'des' => '{lastFeed}',
    'node' => '',
    'sub' => '保存',
  ),
  'audit' => 
  array (
    'open' => '1',
    'replace' => '**',
  ),
  'register' => 
  array (
    'register_type' => 'open',
    'email_suffix' => '',
    'captcha' => '1',
    'register_audit' => '0',
    'need_active' => '0',
    'photo_open' => '1',
    'need_photo' => '0',
    'tag_open' => '1',
    'tag_num' => '5',
    'interester_open' => '1',
    'interester_rule' => 
    array (
      0 => 'area',
      1 => 'tag',
    ),
    'avoidSubmitByReturn' => '',
    'interester_recommend' => '',
    'default_follow' => '',
    'each_follow' => '',
    'default_user_group' => 
    array (
      0 => '3',
    ),
    'welcome_email' => '0',
  ),
  'attachimage' => 
  array (
    'attach_max_size' => '1',
    'attach_allow_extension' => 'jpeg,jpg,gif,png',
    'auto_thumb' => '0',
  ),
  'email' => 
  array (
    'email_sendtype' => 'smtp',
    'email_host' => 'smtp.exmail.qq.com',
    'email_ssl' => '0',
    'email_port' => '25',
    'email_account' => '',
    'email_password' => '',
    'email_sender_name' => 'Meng',
    'email_sender_email' => '',
    'email_test' => '',
  ),
  'site' => 
  array (
    'site_closed' => '1',
    'site_name' => 'MengMeng',
    'site_slogan' => '萌萌，其实你一点都不胖...',
    'site_header_keywords' => '',
    'site_header_description' => '',
    'site_company' => '',
    'site_footer' => '©2014 Taimeng.',
    'site_logo' => '86',
    'site_logo_w3g' => '',
    'login_bg' => '77',
    'site_closed_reason' => '抱歉，本站暂停访问。',
    'sys_domain' => 'admin',
    'sys_nickname' => '管理员,超级管理员',
    'sys_email' => 'huoda@taimeng.net',
    'home_page' => '0',
    'site_theme_name' => 'stv1',
    'sys_version' => '20130523',
    'site_online_count' => '1',
    'site_rewrite_on' => '0',
    'site_analytics_code' => '',
  ),
  'feed' => 
  array (
    'weibo_nums' => '1000',
    'weibo_type' => 
    array (
      0 => 'face',
      1 => 'at',
      2 => 'image',
      3 => 'video',
      4 => 'file',
      5 => 'topic',
      6 => 'contribute',
    ),
    'weibo_premission' => 
    array (
      0 => 'repost',
      1 => 'comment',
    ),
    'weibo_send_info' => '新的活动：',
    'weibo_default_topic' => '',
    'weibo_at_me' => '0',
  ),
);
?>