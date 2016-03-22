<?php

/*
Plugin Name: Foundation6 & Page Builder by SiteOrigin
Plugin URI: https://crumbls.com
Description: Bring Zurb's Foundation 6 to Page Builder by SiteOrigin
Version: 0.1.0
Author: SiteOrigin
Author URI: https://siteorigin.com
License: GPL3
License URI: http://www.gnu.org/licenses/gpl.html
Donate link: http://siteorigin.com/page-builder/#donate
*/

/**
 * A simple plugin to convert SiteOrigin Panel to a Foundation6 layout.
 * Not a lot of functionality other than converting the row system to Zurb's Foundation.
 * Written using SiteOrigin Panels v.2.3.2
 * If there is a request for more functionality, I'll can look into it.
 * This can be included with a theme and it will start working from there.
 **/
namespace Crumbls\Plugins\SiteOrigin;


class plugin
{
    private static $instance;
    private static $i = 0;
    protected static $columns = 12;
    private static $current_column = array();

    public static function get_instance()
    {
        // create an object
        if (!self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function init()
    {
        // Ignore in admin.
        if (is_admin()) {
//    return;
        }


        // SiteOrigin inline css removal.
        remove_action('wp_head', 'siteorigin_panels_print_inline_css', 12);
        remove_action('wp_footer', 'siteorigin_panels_print_inline_css');

        // Parse all content.
        add_filter('siteorigin_panels_data', function ($a) {
            self::$i++;
            return self::parse($a);
        });

//        return;
        // Row clean up.
        add_filter('siteorigin_panels_row_style_attributes', array(self::$instance, 'commonRowAttributes'), PHP_INT_MAX, 2);
//        add_filter('siteorigin_panels_row_style_classes', array(self::$instance, 'commonRowClasses'), 11, 3);

        add_filter('siteorigin_panels_row_cell_classes', array(&$this, 'commonCellClasses'), 11, 2);


    }


    /**
     * Preparse all data.
     * @param $aData
     * @return mixed
     */
    private static function parse($aData)
    {
        self::$current_column[self::$i] = 0;
        $aData['handler_index'] = self::$i;

        // Add in column data.
        foreach ($aData['grids'] as $iRow => $aRow) {
            $aColumns = array_filter($aData['grid_cells'], function ($e) use ($iRow) {
                return $iRow == $e['grid'];
            });

//            print_r($aColumns);
            // Handle this differently.
            $aKeys = array_keys($aColumns);
            $iEnd = array_pop($aKeys);
            $i = 0;
            foreach ($aKeys as $iColumn) {
                $x = round(self::$columns * $aColumns[$iColumn]['weight']);
//                echo $x.'<br />';
                $aData['grid_cells'][$iColumn]['columns'] = $x;
                $i += $x;
            }
            // Last column.  May not exceed our original.
            $x = ceil(self::$columns * $aColumns[$iEnd]['weight']);
            $aData['grid_cells'][$iEnd]['columns'] = Min($x, self::$columns - $i);
        }
        return $aData;
    }

    /**
     * First stage in row class cleanup.  Append classes that should exist and clean up what shouldn't.
     * @param $attributes
     * @param $args
     * @return mixed
     */
    public function commonRowAttributes($attributes, $args)
    {
//        self::$current_row[self::$i] = ;

        // Strip out previous classes.
        $attributes['class'] = array('row');

        // Example of bringing extra data in.
        if (!empty($args['parallax'])) {
            array_push($attributes['class'], 'parallax');
        }

        return $attributes;
    }


    /**
     * Second stage of cleanup for cell classes.
     * @param $aClass
     * @param $aArgs
     * @return array
     */
    public function commonCellClasses($aClass, $aArgs)
    {
        $iColumn = self::$current_column[$aArgs['handler_index']];
        //      echo $iColumn;

        foreach (array('panel-grid-cell') as $k) {
            $i = array_search($k, $aClass);
            if ($i !== false) {
                unset($aClass[$i]);
            }
        }
        $aClass[] = 'columns';
        $aClass[] = 'small-12';
        if (array_key_exists($iColumn, $aArgs['grid_cells'])) {
            $aClass[] = 'medium-' . $aArgs['grid_cells'][$iColumn]['columns'];
        } else {
            // This is to handle a bugfix due to a subarray.  Look for a better fix.
            print_r($aArgs['grid_cells']);


            $aClass[] = 'error';
            echo 'err';
        }

        // Increase our count.
        self::$current_column[$aArgs['handler_index']]++;

        return $aClass;
    }


}

if (!is_admin()) {
    $plugin = plugin::get_instance();
    $plugin->init();
}
