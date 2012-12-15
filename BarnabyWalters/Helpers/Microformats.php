<?php

namespace BarnabyWalters\Helpers;

/**
 * Microformats Helpers
 *
 * @author Barnaby Walters
 */
class Microformats {
    
    /**
     * Inline h-card
     * 
     * @param array $hcard
     * @return string
     */
    public static function inlineHCard(array $hcard) {
        $out = '';
        $p = (array) $hcard['properties'];
        
        foreach ($p as $key => $prop) {
            if (is_array($prop))
                $p['key'] = $prop[0];
        }
        
        if (array_key_exists('url', $p))
            $out .= '<a class="h-card" href="' . $p['url'] . '">';
        else
            $out .= '<span class="h-card">';
        
        if (array_key_exists('photo', $p))
            $out .= '<img src="' . $p['photo'] . '" alt="" />';
        
        $out .= $p['name'];
        
        $out .= array_key_exists('url', $p)
                ? '</a>'
                : '</span>';
        
        return $out;
    }
    
    /**
     * Normalise h-entry Dates
     * 
     * Given an array of h-entries, iterate through them an try to fill in any
     * blank published dates.
     * 
     * If there’s an updated date but no published date, the updated date is 
     * used for the published date. If there is neither a published or an 
     * updated date but there was one for a previous item, the datetime 1 second
     * after the publised datetime of the previous item is applied.
     * 
     * @param array $items An array of h-entry microformat array structures
     * @return array
     */
    public static function normaliseHEntryDates(array $items) {
        // Deal with any missing datetimes
        $lastUpdatedDate = null;

        foreach ($items as $item) {
            // Get the lastUpdatedDate
            if (!empty($item['properties']['updated']))
                $lastUpdatedDate = new DateTime($item['properties']['updated'][0]);
            else if (!empty($item['properties']['published']))
                $lastUpdatedDate = new DateTime($item['properties']['published'][0]);

            // If there’s an updated date but no published, assume published is the 
            // same as updated
            if (empty($item['properties']['published']) and !empty($item['properties']['updated'])) {
                $item['properties']['published'] = array($item['properties']['updated'][0]);
                continue;
            }

            // If both published and updated are empty, add a second to the last 
            // found date and use that for published
            if (empty($item['properties']['published']) and empty($item['properties']['updated']))
                $item['properties']['published'] = array($lastUpdatedDate->add(new DateInterval('PT1S'))->format(DateTime::W3C));
        }

        return $items;
    }

    /**
     * Find h-entries
     * 
     * @param array $µf
     * @return array
     */
    public static function findHEntries(array $µf) {
        // Look for a h-feed
        foreach ($µf['items'] as $item) {
            if (in_array('h-feed', $item['type']) && !empty($item['children'])) {
                $items = $item['children'];
                break;
            }
        }

        // If no h-feed was found, use the root items[] array
        if (empty($items))
            $items = $µf['items'];

        // Reduce to just h-entries
        $items = array_filter($items, function ($item) {
                    return in_array('h-entry', $item['type']);
                });

        return $items;
    }

    /**
     * Find h-cards
     * @param array $µfs
     * @return array
     */
    public static function findHCards(array $µfs) {
        $hCards = array();

        if (!empty($µfs['items']))
            $µfs = $µfs['items'];

        foreach ($µfs as $µf) {

            if (in_array('h-card', $µf['type'])) {
                $hCards[] = $µf;
                continue;
            }

            // TODO: Look in children of top-level µf too
        }

        return $hCards;
    }

}

// EOF
