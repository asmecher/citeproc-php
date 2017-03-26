<?php
/**
 * citeproc-php
 *
 * @link        http://github.com/seboettg/citeproc-php for the source repository
 * @copyright   Copyright (c) 2016 Sebastian Böttger.
 * @license     https://opensource.org/licenses/MIT
 */

namespace Seboettg\CiteProc\Style\Sort;

use Seboettg\CiteProc\CiteProc;
use Seboettg\CiteProc\Data\DataList;
use Seboettg\CiteProc\Util\Variables;
use Seboettg\CiteProc\Util\Date;
use Seboettg\Collection\ArrayList;


/**
 * Class Sort
 *
 * cs:citation and cs:bibliography may include a cs:sort child element before the cs:layout element to specify the
 * sorting order of respectively cites within citations, and bibliographic entries within the bibliography.
 *
 * The cs:sort element must contain one or more cs:key child elements. The sort key, set as an attribute on cs:key, must
 * be a variable (see Appendix IV - Variables) or macro name. For each cs:key element, the sort direction can be set to
 * either “ascending” (default) or “descending” with the sort attribute.
 *
 * @package Seboettg\CiteProc\Style
 *
 * @author Sebastian Böttger <seboettg@gmail.com>
 */
class Sort
{
    /**
     * ordered list contains sorting keys
     *
     * @var ArrayList
     */
    private $sortingKeys;

    /**
     * @var \SimpleXMLElement $node
     */
    public function __construct(\SimpleXMLElement $node)
    {
        $this->sortingKeys = new ArrayList();
        /** @var \SimpleXMLElement $child */
        foreach ($node->children() as $child) {
            if ("key" === $child->getName()) {
                $this->sortingKeys->append(new Key($child));
            }
        }
    }

    /**
     * Function in order to sort a set of csl items by one or multiple sort keys.
     * Sort keys are evaluated in sequence. A primary sort is performed on all items using the first sort key.
     * A secondary sort, using the second sort key, is applied to items sharing the first sort key value. A tertiary
     * sort, using the third sort key, is applied to items sharing the first and second sort key values. Sorting
     * continues until either the order of all items is fixed, or until the sort keys are exhausted. Items with an
     * empty sort key value are placed at the end of the sort, both for ascending and descending sorts.
     *
     * @param DataList $data reference
     */
    public function sort(&$data)
    {
        $dataToSort = $data->toArray();
        $data->replace($this->performSort(0, $dataToSort));
    }

    /**
     * Recursive function in order to sort a set of csl items by one or multiple sort keys.
     * All items will be distributed by the value (defined in respective sort key) in an associative array (grouped).
     * Afterwards the array will be sorted by the array key. If a further sort key exist, each of these groups will be
     * sorted by a recursive function call. Finally the array will be flatted.
     *
     * @param $keyNumber
     * @param array $dataToSort
     * @return array
     */
    private function performSort($keyNumber, $dataToSort)
    {
        if (count($dataToSort) < 2) {
            return $dataToSort;
        }

        /** @var Key $key */
        $key = $this->sortingKeys->get($keyNumber);
        $variable = $key->getVariable();
        $groupedItems = [];

        //grouping by value
        foreach ($dataToSort as $citationNumber => $dataItem) {
            if ($key->isNameVariable()) {
                $sortKey = Variables::nameHash($dataItem, $variable);
            } else if ($key->isNumberVariable()) {
                $sortKey = $dataItem->{$variable};
            } else if ($key->isDateVariable()) {
                $sortKey = Date::getSortKeyDate($variable, $dataItem);
            } else if ($key->isMacro()) {
                $sortKey = mb_strtolower(strip_tags(CiteProc::getContext()->getMacro($key->getMacro())->render($dataItem, $citationNumber)));
            } else if ($variable === "citation-number") {
                $sortKey = $citationNumber + 1;
            } else {
                $sortKey = mb_strtolower(strip_tags($dataItem->{$variable}));
            }
            $groupedItems[$sortKey][] = $dataItem;
        }

        if ($this->sortingKeys->count() > ++$keyNumber) {
            foreach ($groupedItems as $group => &$array) {
                if (count($array) > 1) {
                    $array = $this->performSort($keyNumber, $array);
                }
            }
        }

        //sorting by array keys
        if ($key->getSort() === "ascending") {
            ksort($groupedItems); //ascending
        } else {
            krsort($groupedItems); //reverse
        }

        //the flattened array is the result
        $sortedDataGroups = array_values($groupedItems);
        return $this->flatten($sortedDataGroups);
    }

    public function flatten($array) {
        $returnArray = [];
        array_walk_recursive($array, function($a) use (&$returnArray) { $returnArray[] = $a; });
        return $returnArray;
    }

    /**
     * @return ArrayList
     */
    public function getSortingKeys()
    {
        return $this->sortingKeys;
    }
}