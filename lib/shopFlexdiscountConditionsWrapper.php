<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of shopFlexdiscountConditionsWrapper
 *
 * @author Константин
 */
class shopFlexdiscountConditionsWrapper extends shopFlexdiscountConditions{
    private static function execute_operator($operator, $val1, $val2, $range_value = false)
    {
        $operator_name = 'operator_' . $operator . ($range_value ? "_range" : "");
        return self::$operator_name($val1, $val2);
    }

    private static function operator_eq($val1, $val2)
    {
        if (is_array($val2)) {
            return in_array($val1, $val2);
        } elseif (is_array($val1)) {
            return in_array($val2, $val1);
        } else {
            return $val1 == $val2;
        }
    }
    
    private static function operator_neq($val1, $val2)
    {
        return !self::operator_eq($val1, $val2);
    }
    
    protected static function getCategoryTree($category_id)
    {
        $cm = new shopCategoryModel();
        $categories = $cm->getTree($category_id);
        
        $category_array = array();
        
        foreach ($categories as $category) {
            $category_array[] = $category['id'];
        }        
        
        return $category_array;
    }

    protected static function filter_by_cat($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                $cat_id = isset($item['id']) ? (int) $item['id'] : 0;
                if (!self::execute_operator($params['op'], $cat_id, $params['value'])) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }    
    
    protected static function filter_by_cat_all($items, $params)
    {
        if (!empty($params['value'])) {
            foreach ($items as $k => $item) {
                $cat_id = isset($item['id']) ? (int) $item['id'] : 0;
                if (!self::execute_operator($params['op'], $cat_id, self::getCategoryTree($params['value']))) {
                    unset($items[$k]);
                }
            }
        }
        return $items;
    }
    
    protected static function get_instance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = get_class();
        }
        return $instance;
    }
    
    private static function prepare_operator($operator)
    {
        $instance = self::get_instance();
        $operator_func = 'operator_' . $operator;
        if (!method_exists($instance, $operator_func)) {
            return false;
        } else {
            return $operator_func;
        }
    }
    
    private static function sort_by_precedence($conditions)
    {
        $sorted_conditions = array(0 => array(), 1 => array(), 2 => array(), 3 => array());
        foreach ($conditions as $c) {
            if (isset($c['group_op'])) {
                $sorted_conditions[0][] = $c;
            } else {
                if (isset(self::$type_precedence[$c['type']])) {
                    $sorted_conditions[self::$type_precedence[$c['type']]][] = $c;
                }
            }
        }
        $result = array();
        foreach ($sorted_conditions as $sc) {
            $result = array_merge($result, $sc);
        }
        return $result;
    }
    
    public static function filter_conditions($conditions, $filter_by = array(), $type = 'discount')
    {
        foreach ($conditions as $k => $c) {
            if (isset($c['group_op'])) {
                $conditions[$k]['conditions'] = self::filter_conditions(self::decode($c['conditions']), $filter_by, $type);
                if (!$conditions[$k]['conditions']) {
                    if ($type == 'discount') {
                        $conditions[$k] = array("op" => "gte", "value" => "0", "type" => "num");
                    } else {
                        unset($conditions[$k]);
                    }
                }
            } else {
                if (isset(self::$type_precedence[$c['type']])) {
                    $filter_by[] = 'storefront';
                    // Для скидок удаляем все правила, согласно фильтру. 
                    // Если фильтра нет, то удаляем все правила, у которых старшинство не равно 1
                    if ($type == 'discount' && ((!empty($filter_by) && !in_array($c['type'], $filter_by) && self::$type_precedence[$c['type']] !== 1) || (empty($filter_by) && self::$type_precedence[$c['type']] !== 1))) {
                        $conditions[$k] = array("op" => "gte", "value" => "0", "type" => "num");
                    }
                    // Для запретов удаляем все правила со старшинством равным 3
                    elseif ($type == 'deny' && (self::$type_precedence[$c['type']] === 3)) {
                        unset($conditions[$k]);
                    }
                    // Если выводим все скидки, то учитываем только фильтр
                    elseif ($type == 'all' && !empty($filter_by) && !in_array($c['type'], $filter_by)) {
                        $conditions[$k] = array("op" => "gte", "value" => "0", "type" => "num");
                    }
                }
            }
        }
        return $conditions;
    }
    
    public static function filter_items($items, $group_andor, $conditions)
    {
        $instance = self::get_instance();

        $result_items = $group_andor == 'and' ? $items : array();

        // Сортируем условия по старшинству
        $conditions = self::sort_by_precedence($conditions);

        foreach ($conditions as $c) {
            // Если перед нами группа скидок, разбираем ее
            if (isset($c['group_op'])) {
                $conditions2 = self::decode($c['conditions']);
                $result = self::filter_items($items, $c['group_op'], $conditions2);
                if ($group_andor == 'and' && !$result) {
                    $result_items = array();
                    break;
                }
                $result_items += $result;
            } else {
                // Проверяем работоспособность оператора
                if (isset($c['op']) && !self::prepare_operator($c['op'])) {
                    continue;
                }
                // Фильтруем товары по типу
                $function_name = 'filter_by_' . $c['type'];
                if (method_exists($instance, $function_name)) {
                    if ($group_andor == 'and') {
                        $result_items = self::$function_name($result_items, $c);
                        if (!$result_items) {
                            break;
                        }
                    } else {
                        $result_items += self::$function_name($items, $c);
                    }
                }
            }
        }

        return $result_items;
    }    
}
