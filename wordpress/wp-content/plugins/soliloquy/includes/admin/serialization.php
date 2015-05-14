<?php
/**
 * Common serialization class. Fixes issues with broken serialized strings
 * typically caused by users migrating sites + running a search/replace MySQL
 * query on the post meta table (therefore breaking Soliloquy + it 'losing'
 * all slides etc).
 *
 * @since 2.3.9.6
 *
 * @package Soliloquy
 * @author  Tim Carr
 */
class Soliloquy_Serialization_Admin {

    /**
     * Holds the class object.
     *
     * @since 2.3.9.6
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 2.3.9.6
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Holds the base class object.
     *
     * @since 2.3.9.6
     *
     * @var object
     */
    public $base;

    /**
     * Primary class constructor.
     *
     * @since 2.3.9.6
     */
    public function __construct() {
	    
	}
	
	/**
	 * Fix a serialized string
	 *
	 * @since 2.3.9.6
	 *
	 * @param string $string Serialized string to fix
	 * @return array Unserialized data
	 */
	public function fix_serialized_string( $string ) {
		
		// Check string is serialised and if it already works return it
		if ( !preg_match('/^[aOs]:/', $string) ) {
			return $string;
		}
		if ( @unserialize( $string ) !== false ) {
			return @unserialize( $string );
		}
		
		// String needs fixing - fix it
		$string = preg_replace_callback('/\bs:(\d+):"(.*?)"/', array($this, 'fix_str_length'), $string);
		
		// Return fixed unserialized data
		return @unserialize( $string );
		
	}

	/**
	 * Callback function for replacing the string's length paramter on a broken
	 * serialized string
	 *
	 * @since 2.3.9.6
	 *
	 * @param array $matches preg_replace matches
	 * @return string Replacement string
	 */
	private function fix_str_length( $matches ) {
		
		$string = $matches[2];
		$right_length = strlen( $string );
		return 's:' . $right_length . ':"' . $string . '"';
		
	}
	
	/**
     * Returns the singleton instance of the class.
     *
     * @since 2.3.9.6
     *
     * @return object The Soliloquy_Serialization_Admin object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Soliloquy_Serialization_Admin ) ) {
            self::$instance = new Soliloquy_Serialization_Admin();
        }

        return self::$instance;

    }
	
}

// Load the serialization admin class.
$soliloquy_serialization_admin = Soliloquy_Serialization_Admin::get_instance();