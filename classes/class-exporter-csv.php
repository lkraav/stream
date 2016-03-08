<?php
namespace WP_Stream;

class Exporter_CSV extends Exporter{

	/**
	 * Exporter slug
	 *
	 * @var string
	 */
	public $name = 'csv';

	/**
	 * Outputs CSV data for download
	 *
	 * @param array $data Array of data to output.
	 * @param array $columns Column names included in data set.
	 * @return void
	 */
	public function output_file( $data, $columns ) {

		if ( ! defined( 'WP_STREAM_TESTS' ) || ( defined( 'WP_STREAM_TESTS' ) && ! WP_STREAM_TESTS ) ) {
			header( 'Content-type: text/csv' );
			header( 'Content-Disposition: attachment; filename="stream.csv"' );
		}

		$output = join( ',', array_values( $columns ) ) . "\n";
		foreach ( $data as $row ) {
			$output .= join( ',', $row ) . "\n";
		}

		echo $output;
		if ( ! defined( 'WP_STREAM_TESTS' ) || ( defined( 'WP_STREAM_TESTS' ) && ! WP_STREAM_TESTS ) ) {
			exit;
		}
	}
}
