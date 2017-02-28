<?php

require('shopFlexdiscountConditionsWrapper.php');

class shopHeaderPlugin extends shopPlugin
{
    protected static function filter_discounts($category_array)
    {
        // Правила скидок
        $discount_groups = shopFlexdiscountHelper::getDiscounts();

        // Выполняем перебор групп скидок
        foreach ($discount_groups as $group_id => $group) {
            $result_items = array();
            $rules = $group_id === 0 ? $group : $group['items'];
            foreach ($rules as $rule) {
                $coupon_cnt = 0;
                if ($rule['status'] && empty($rule['hide_storefront']) && !$rule['deny']) {
                    if(!empty($rule['coupons'])){
                        foreach ($rule['coupons'] as $coupon_id) {
                            $coupon_plugin = new shopFlexdiscountCouponPluginModel();
                            $coupon = $coupon_plugin->getCoupon($coupon_id);
                            // Проверяем наличие купонов у правил скидок
                            if ($coupon && shopFlexdiscountHelper::getCouponStatus($coupon) > 0) {
                               $coupon_cnt++;
                            }
                        }
                    }
                    if ($coupon_cnt == 1) {
                        $cond = shopFlexdiscountConditionsWrapper::decode($rule['conditions']);

                        $filter_by = array();
                        $filter_by[] = 'cat';
                        $filter_by[] = 'cat_all';
                        $filter_type = 'all';

                        if (!empty($cond['conditions'])) {
                            $cond['conditions'] = shopFlexdiscountConditionsWrapper::filter_conditions($cond['conditions'], $filter_by, $filter_type);
                        }

                        if(!empty($cond)){
                            $filter_items = shopFlexdiscountConditionsWrapper::filter_items($category_array, $cond['group_op'], $cond['conditions']);
                            if(!empty($filter_items)){
                                $result_items[] = $rule;
                            }
                        }                            
                    }
                }
            }
        }
        return $result_items;
    }
    
    public static function getCategoryDiscount($category)
    {
        $settings = wa('shop')->getPlugin('header')->getSettings();
        
        $category_array = array();
        $category_array[] = $category;
        
        /*$cm = new shopCategoryModel();
        $category_n = $category;
        
        while (!empty($category_n)):
            $category_array[] = $category_n;
            $category_n = $cm->getById($category_n[$cm->getTableParent()]);
        endwhile;*/
        
        $discounts = self::filter_discounts($category_array);
        
        $html_exist = false;

        if (!empty($discounts)) {
            foreach ($discounts as $item) {
                if(!$html_exist)
                {
                    $html_exist = true;
                    $html = $settings['begin_html'];
                }
                
                $view = wa()->getView();
                $view->clearAllAssign();
                $view->assign($item);

                $body = $view->fetch('string:'.$settings['each_row']);

                $html = $html.$body;
            }
            
            if($html_exist)
            {
                $html = $html.$settings['end_html'];
            }
        }        

        return $html;        
    }
    
    public static function getCategoryDiscount_old($category)
    {
        $settings = wa('shop')->getPlugin('header')->getSettings();
        
        //waLog::log('TEST!', 'shop/plugins/spam/spam.log');
        
        $cm = new shopCategoryModel();
        $category_n = $category;
        
        while (!empty($category_n)):
            $category_str = $category_str . " OR category_id = '".(int) $category_n['id']."'";
            $category_n = $cm->getById($category_n[$cm->getTableParent()]);
        endwhile;
        
        $category_str = "AND (0 " . $category_str . ")";
        
        $m = new waModel();
        $sql = "SELECT * FROM shop_flexdiscount WHERE 1 $category_str ORDER BY category_id ASC";

        $result = $m->query($sql);
        
        $time = time();
        
        $html_exist = false;

        if ($result) {
            foreach ($result as $k => $d) {
                $discounts[$k] = $d;
                $discounts[$k]['block'] = 0;
                
                if (isset($d['expire_datetime']) && strtotime($d['expire_datetime']) < $time) {
                    $discounts[$k]['block'] = 1;
                }
                if($discounts[$k]['block'] == 0)
                {
                    if(!$html_exist)
                    {
                        $html_exist = true;
                        $html = $settings['begin_html'];
                    }
                    $view = wa()->getView();
                    $view->clearAllAssign();
                    $view->assign($discounts[$k]);

                    $body = $view->fetch('string:'.$settings['each_row']);
                    
                    $html = $html.$body;
                }
            }
            
            if($html_exist)
            {
                $html = $html.$settings['end_html'];
            }
        }        

        return $html;
    }
}