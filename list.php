<?php

require_once 'config.php';

if ( defined( 'DEBUG' ) && DEBUG ) {
	ini_set( 'display_errors', 1 );
	ini_set( 'display_startup_errors', 1 );
	error_reporting( E_ALL );
}

function get_page_link( $type, $total_pages = 1 ) {

	$parse_url = parse_url( $_SERVER['REQUEST_URI'] );
	parse_str( $parse_url['query'] ?? '', $params );

	$current_page = (int) ( $_GET['page'] ?? 1 );

	if ( $type === 'previous' ) {
		$params['page'] = max( 1, $current_page - 1 );
	} elseif ( $type === 'next' ) {
		$params['page'] = min( $total_pages, $current_page + 1 );
	}

	return $parse_url['path'] . '?' . http_build_query( $params );

}

$mysqli = mysqli_init();
$mysqli->real_connect( DB_HOST, DB_USER, DB_PASSWORD, DB_NAME );

$per_page = 25;
$offset   = isset( $_GET['page'] ) ? ( (int) $_GET['page'] - 1 ) * $per_page : 0;

if ( isset( $_REQUEST['s'] ) ) {

	$s    = '%' . $mysqli->real_escape_string( $_REQUEST['s'] ) . '%';
	$stmt = $mysqli->prepare( 'SELECT SQL_CALC_FOUND_ROWS * FROM `ced_api_logs` WHERE `seller_id` LIKE ? LIMIT ? OFFSET ?' );
	$stmt->bind_param( 'sii', $s, $per_page, $offset );

} else {

	$stmt = $mysqli->prepare( 'SELECT SQL_CALC_FOUND_ROWS * FROM `ced_api_logs` ORDER BY `id` LIMIT ? OFFSET ?' );
	$stmt->bind_param( 'ii', $per_page, $offset );

}

$stmt->execute();
$result = $stmt->get_result();

$total_result = $mysqli->query( 'SELECT FOUND_ROWS()' );
$total_row    = $total_result->fetch_array();
$total        = $total_row[0];
$total_pages  = ceil( $total / $per_page );

$previous = get_page_link( 'previous' );
$next     = get_page_link( 'next', $total_pages );

$html = '';

if ( $result->num_rows ) {

	$html .= '<link rel="stylesheet" href="style.css">';

	$html .= '<div class="pagination">';
	$html .= '<a href="' . htmlspecialchars( $previous ) . '">&laquo;</a>';
	$html .= '<a href="#" class="active">' . htmlspecialchars( $_GET['page'] ?? 1 ) . '</a> of ' . $total_pages;
	$html .= '<a href="' . htmlspecialchars( $next ) . '">&raquo;</a>';
	$html .= '<span>Total Records: ' . $total . '</span>';
	$html .= '</div>';

	$html .= '<div class="search-container">';
	$html .= '<form id="searchForm" method="get" action="">';
	$html .= '<input value="' . htmlspecialchars( $_REQUEST['s'] ?? '' ) . '" type="text" id="sellerSearch" name="s" placeholder="Search by Seller ID..." required>';
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

		$html .= '<tr>';
		$html .= '<td>' . htmlspecialchars( $api_log['id'] ?? 0 ) . '</td>';
		$html .= '<td>' . htmlspecialchars( $api_log['seller_id'] ?? 0 ) . '</td>';
		$html .= '<td>' . htmlspecialchars( $api_log['marketplace'] ?? 0 ) . '</td>';
		$html .= '<td>';

		foreach ( $request_count[ date( 'm-Y' ) ] as $topic => $count ) {
			$html .= '<p><label><strong>' . htmlspecialchars( $topic ) . ' : </strong></label><span>' . htmlspecialchars( $count ) . '</span></p>';
		}

		$html .= '</td>';
		$html .= '</tr>';
	}

	$html .= '</table>';

}

echo $html;

