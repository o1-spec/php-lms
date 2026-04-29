<?php

/**
 * Build a pagination HTML block.
 *
 * @param int    $total_records  Total number of records in the result set
 * @param int    $records_per_page  How many to show per page
 * @param int    $current_page  The page we are on right now (1-indexed)
 * @param string $url_pattern  printf-style pattern where %d becomes the page number
 *                             e.g. "/library/books/index.php?page=%d"
 * @return string HTML string
 */
function build_pagination(int $total_records, int $records_per_page, int $current_page, string $url_pattern): string
{
    if ($total_records <= $records_per_page) {
        return '';
    }

    $total_pages = (int) ceil($total_records / $records_per_page);
    $current_page = max(1, min($current_page, $total_pages));

    $start = ($current_page - 1) * $records_per_page + 1;
    $end   = min($current_page * $records_per_page, $total_records);

    $html  = '<div class="pagination-wrapper">';
    $html .= '<span class="pagination-info">Showing ' . $start . '–' . $end . ' of ' . $total_records . ' records</span>';
    $html .= '<ul class="pagination">';

    // Previous
    if ($current_page <= 1) {
        $html .= '<li class="disabled"><span>&#8592;</span></li>';
    } else {
        $html .= '<li><a href="' . sprintf($url_pattern, $current_page - 1) . '">&#8592;</a></li>';
    }

    // Page numbers with ellipsis
    $window = 2; // pages on either side of current
    for ($p = 1; $p <= $total_pages; $p++) {
        if ($p === 1 || $p === $total_pages || abs($p - $current_page) <= $window) {
            $class = ($p === $current_page) ? 'active' : '';
            $href  = sprintf($url_pattern, $p);
            $html .= '<li class="' . $class . '"><a href="' . $href . '">' . $p . '</a></li>';
        } elseif (abs($p - $current_page) === $window + 1) {
            $html .= '<li class="ellipsis"><span>&hellip;</span></li>';
        }
    }

    // Next
    if ($current_page >= $total_pages) {
        $html .= '<li class="disabled"><span>&#8594;</span></li>';
    } else {
        $html .= '<li><a href="' . sprintf($url_pattern, $current_page + 1) . '">&#8594;</a></li>';
    }

    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}
