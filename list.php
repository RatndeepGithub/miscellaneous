<?php

require_once 'config.php';

function get_page_link( $type, $total_pages = 1 ) {

	$parse_url = parse_url( $_SERVER['REQUEST_URI'] );
	parse_str( $parse_url['query'], $params );

	$current_page = $_GET['page'] ?? 1;

	switch ( $type ) {

		case 'previous':
			$params['page'] = ( $current_page - 1 ) !== 0 ? $current_page - 1 : 1;
			$url            = $parse_url['path'] . '?' . http_build_query( $params );
			break;

		case 'next':
			$params['page'] = ( $current_page + 1 ) < $total_pages ? $current_page + 1 : $total_pages;
			$url            = $parse_url['path'] . '?' . http_build_query( $params );
			break;

		default:
			$url = $_SERVER['REQUEST_URI'];
			break;
	}

	return $url;
}

$mysqli = mysqli_init();
$mysqli->real_connect( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

$per_page = 25;
$offset   = isset( $_GET['page'] ) ? ( ( (int) $_GET['page'] - 1 ) * $per_page ) : 0;

if ( isset( $_REQUEST['s'] ) ) {
	$s      = $_REQUEST['s'];
	$result = $mysqli->query( 'SELECT * FROM `ced_api_logs` WHERE `seller_id` LIKE "%' . $s . '%" LIMIT ' . $per_page . ' OFFSET ' . $offset . '' );
	$all    = $mysqli->query( 'SELECT * FROM `ced_api_logs` WHERE `seller_id` LIKE "%' . $s . '%" ' );
} else {
	$result = $mysqli->query( 'SELECT * FROM `ced_api_logs` ORDER BY `id` LIMIT ' . $per_page . ' OFFSET ' . $offset . '' );
	$all    = $mysqli->query( 'SELECT * FROM ced_api_logs' );
}

$total       = $all->num_rows;
$total_pages = ceil( $total / $per_page );

$previous = get_page_link( 'previous' );
$next     = get_page_link( 'next', $total_pages );

if ( $result->num_rows ) {

	$html = '<style>.pagination,body{font-family:Arial,sans-serif}.pagination a,td,th{border:1px solid #ddd}body{background-color:#f4f4f4;margin:0;padding:20px}table{width:100%;border-collapse:collapse;margin-bottom:20px}td,th{padding:8px;text-align:left}th{background-color:#6495ed;color:#fff}tr:nth-child(2n){background-color:#f2f2f2}.pagination a:hover,tr:hover{background-color:#ddd}p{margin:5px 0}label{font-weight:700;color:#333}span{color:#555}.pagination{display:flex;justify-content:center;align-items:center;padding:10px 0}.pagination a{color:#000;padding:8px 16px;text-decoration:none;margin:0 4px;transition:background-color .3s,color .3s}.pagination a.active{background-color:#6495ed;color:#fff;border:1px solid #6495ed}.pagination a.disabled{pointer-events:none;color:#ccc}.pagination span{margin:0 8px;font-weight:700;color:#333}.pagination span:last-child{margin-left:20px;font-weight:700;color:#333}.search-container{margin-bottom:20px;text-align:center}.search-container input[type=text]{padding:8px;width:300px;border:1px solid #ddd;border-radius:4px}.search-container input[type=submit]{padding:8px 16px;background-color:#6495ed;color:#fff;border:none;border-radius:4px;cursor:pointer;margin-left:10px}.search-container input[type=submit]:hover{opacity:.9}</style>';

	$html .= '<div class="pagination">';
	$html .= '<a href="' . ( $previous ) . '">&laquo;</a>';
	$html .= '<a href="#" class="active">' . ( isset( $_GET['page'] ) ? $_GET['page'] : 1 ) . '</a> of ' . $total_pages;
	$html .= '<a href="' . ( $next ) . '">&raquo;</a>';
	$html .= '<span>Total Records: ' . $total . '</span>';
	$html .= '</div>';

	$html .= '<div class="search-container">';
	$html .= '<form id="searchForm" method="get" action="">';
	$html .= '<input value="' . ( $_REQUEST['s'] ?? '' ) . '" type="text" id="sellerSearch" name="s" placeholder="Search by Seller ID..." required>';
	$html .= '<input type="submit" value="Search">';
	$html .= '</form>';
	$html .= '</div>';

	$html .= '<table>';
	$html .= '<tr>';
	$html .= '<th>ID</th>';
	$html .= '<th>Seller ID</th>';
	$html .= '<th>Marketplace</th>';
	$html .= '<th>API Log</th>';
	$html .= '</tr>';

	while ( $api_log = $result->fetch_assoc() ) {

		$request_count = $api_log['request_count'] ? unserialize( $api_log['request_count'] ) : array();
		$html         .= '<tr>';
		$html         .= '<td>' . ( $api_log['id'] ?? 0 ) . '</td>';
		$html         .= '<td>' . ( $api_log['seller_id'] ?? 0 ) . '</td>';
		$html         .= '<td>' . ( $api_log['marketplace'] ?? 0 ) . '</td>';
		$html         .= '<td>';

		foreach ( $request_count[ date( 'm-Y' ) ] as $topic => $count ) {
			$html .= '<p><label><strong>' . $topic . ' : </strong></label><span>' . $count . '</span></p>';
		}

		$html .= '</td>';
		$html .= '</tr>';
	}

	$html .= '</table>';
}

echo $html;
