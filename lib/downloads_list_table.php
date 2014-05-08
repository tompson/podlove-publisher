<?php
namespace Podlove;

class Downloads_List_Table extends \Podlove\List_Table {

	function __construct(){
		global $status, $page;
		        
		// Set parent defaults
		parent::__construct( array(
		    'singular'  => 'download',   // singular name of the listed records
		    'plural'    => 'downloads',  // plural name of the listed records
		    'ajax'      => false       // does this table support ajax?
		) );
	}

	public function column_episode( $episode ) {
		return $episode['title'];
	}

	public function column_downloads( $episode ) {
		return $episode['downloads'];
	}

	public function get_columns(){
		return array(
			'episode'   => __( 'Episode', 'podlove' ),
			'downloads' => __( 'Total Downloads', 'podlove' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'episode'   => array('episode', true),
			'downloads' => array('downloads', true)
		);
	}	

	public function prepare_items() {
		global $wpdb;

		// number of items per page
		$per_page = 20;
		
		// define column headers
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby_map = array(
			'episode'   => 'p.post_date',
			'downloads' => 'downloads'
		);

		// look for order options
		if( isset($_GET['orderby']) && isset($orderby_map[$_GET['orderby']])  ) {
			$orderby = 'ORDER BY ' . $orderby_map[$_GET['orderby']];
		} else{
			$orderby = 'ORDER BY p.post_date';
		}

		// look how to sort
		if( isset($_GET['order'])  ) {
			$order = strtoupper($_GET['order']) == 'ASC' ? 'ASC' : 'DESC';
		} else{
			$order = 'DESC';
		}
		
		// retrieve data
		$sql = "
			SELECT
				e.id, p.post_title title, COUNT(di.id) downloads
			FROM
				" . Model\Episode::table_name() . " e
				JOIN " . $wpdb->posts . " p ON e.post_id = p.ID
				JOIN " . Model\MediaFile::table_name() . " mf ON e.id = mf.episode_id
				LEFT JOIN " . Model\DownloadIntent::table_name() . " di ON di.media_file_id = mf.id
			WHERE
				p.post_status IN ('publish', 'private')
			GROUP BY
				e.id
			$orderby $order
		";

		$data = $wpdb->get_results($sql, ARRAY_A);

		// get current page
		$current_page = $this->get_pagenum();
		// get total items
		$total_items = count( $data );
		// extrage page for current page only
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ) , $per_page );
		// add items to table
		$this->items = $data;
		
		// register pagination options & calculations
		$this->set_pagination_args( array(
		    'total_items' => $total_items,
		    'per_page'    => $per_page,
		    'total_pages' => ceil( $total_items / $per_page )
		) );
	}

}