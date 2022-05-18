<?php
/**
 * This is a basic Array class with little useful functions
 *
 * Created by Francois Dupras
 * October 2012
 */
namespace Void;

class ArrayFunction
{
    static public function getValuesForKey($array, $key) {
        if(function_exists('array_column')) {
            return array_column($array, $key);
        }
        $return = array();
        if(is_array($key)) {
            foreach($array as $cr) {
                foreach($key as $k)
                    $return[$k][] = $cr[$k];
            }

        }
        else {
            foreach($array as $cr) {
                $return[] = $cr[$key];
            }
        }
        return $return;
    }

    static public function arrayToTable(array $data, array $options = array())
    {
        $table='';
        if(isset($options['caption'])) {
            $table.='<caption>'.$options['caption'].'</caption>'.PHP_EOL;
        }
        if(isset($options['thead']) && is_array($options['thead'])) {
            $table.='<thead>'.PHP_EOL.self::arrayToTableRow($options['thead'], $options).'</thead>'.PHP_EOL;
        }

        if(isset($options['noTbodyTag'])) {
            $table.=self::arrayToTableRow($data, $options);
        }
        else {
            $table.='<tbody>'.PHP_EOL.self::arrayToTableRow($data, $options).'</tbody>'.PHP_EOL;
        }

        return '<table'
            .(isset($options['tableClass']) ? ' class="'.$options['tableClass'].'"':'')
            .(isset($options['tableId']) ? ' id="'.$options['tableId'].'"':'')
            .'>'.PHP_EOL
            .$table
            .'</table>'
            .PHP_EOL
        ;
    }

    static public function arrayToTableRow(array $data, array $options = array())
    {
        if(isset($options['useKeyAsTh']) && $options['useKeyAsTh']) {
            $return = '';
            foreach($data as $key=>$val) {
                $return.="<tr><th>{$key}</th><td>".(
                    is_array($val)
                    ? implode('</td><td>', $val)
                    : $val
                ).'</td></tr>';
            };
        }
        else if(is_array(reset($data))) {
             $return = '<tr>'.PHP_EOL.implode('<tr>'.PHP_EOL.'</tr>'.PHP_EOL, array_map(function($x){return '<td>'.implode('</td>'.PHP_EOL.'<td>', $x).'</td>'.PHP_EOL;}, $data)).'</tr>'.PHP_EOL;
        }
        else {
            $return = '<tr><td>'.implode('</td><td>', $data).'</td></tr>';
        }
        return $return;
    }
}
