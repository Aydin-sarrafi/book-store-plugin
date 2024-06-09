<?php
/**
 * Plugin Name: Books Info
 * Description: This is a bookstore plugin.
 * Version: 1.0
 * Author: Aydin Sarrafi
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Load plugin text domain for internationalization
function load_books_info_textdomain() {
    load_plugin_textdomain( 'textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'load_books_info_textdomain' );


/**
 * Function to create the books_info table.
 */
function create_books_info_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'books_info';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        ID bigint(20) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        isbn varchar(255) NOT NULL,
        PRIMARY KEY  (ID),
        KEY post_id (post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta($sql);
}

/**
 * Register the function to run when the plugin is activated.
 */
register_activation_hook(__FILE__, 'create_books_info_table');





/**
 * Function to create custom post type Book.
 */
function register_book_post_type() {
    $labels = array(
        'name'               => _x('Books', 'post type general name', 'textdomain'),
        'singular_name'      => _x('Book', 'post type singular name', 'textdomain'),
        'menu_name'          => _x('Books', 'admin menu', 'textdomain'),
        'name_admin_bar'     => _x('Book', 'add new on admin bar', 'textdomain'),
        'add_new'            => _x('Add New', 'book', 'textdomain'),
        'add_new_item'       => __('Add New Book', 'textdomain'),
        'new_item'           => __('New Book', 'textdomain'),
        'edit_item'          => __('Edit Book', 'textdomain'),
        'view_item'          => __('View Book', 'textdomain'),
        'all_items'          => __('All Books', 'textdomain'),
        'search_items'       => __('Search Books', 'textdomain'),
        'parent_item_colon'  => __('Parent Books:', 'textdomain'),
        'not_found'          => __('No books found.', 'textdomain'),
        'not_found_in_trash' => __('No books found in Trash.', 'textdomain')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'book'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'thumbnail')
    );

    register_post_type('book', $args);
}

add_action('init', 'register_book_post_type');

/**
 * Function to create custom taxonomies for the Book post type.
 */
function create_book_taxonomies() {
    $labels = array(
        'name'              => _x('Publishers', 'taxonomy general name', 'textdomain'),
        'singular_name'     => _x('Publisher', 'taxonomy singular name', 'textdomain'),
        'search_items'      => __('Search Publishers', 'textdomain'),
        'all_items'         => __('All Publishers', 'textdomain'),
        'parent_item'       => __('Parent Publisher', 'textdomain'),
        'parent_item_colon' => __('Parent Publisher:', 'textdomain'),
        'edit_item'         => __('Edit Publisher', 'textdomain'),
        'update_item'       => __('Update Publisher', 'textdomain'),
        'add_new_item'      => __('Add New Publisher', 'textdomain'),
        'new_item_name'     => __('New Publisher Name', 'textdomain'),
        'menu_name'         => __('Publisher', 'textdomain'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'publisher'),
    );

    register_taxonomy('publisher', array('book'), $args);

    $labels = array(
        'name'              => _x('Authors', 'taxonomy general name', 'textdomain'),
        'singular_name'     => _x('Author', 'taxonomy singular name', 'textdomain'),
        'search_items'      => __('Search Authors', 'textdomain'),
        'all_items'         => __('All Authors', 'textdomain'),
        'parent_item'       => __('Parent Author', 'textdomain'),
        'parent_item_colon' => __('Parent Author:', 'textdomain'),
        'edit_item'         => __('Edit Author', 'textdomain'),
        'update_item'       => __('Update Author', 'textdomain'),
        'add_new_item'      => __('Add New Author', 'textdomain'),
        'new_item_name'     => __('New Author Name', 'textdomain'),
        'menu_name'         => __('Author', 'textdomain'),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'author'),
    );

    register_taxonomy('author', array('book'), $args);
}

add_action('init', 'create_book_taxonomies');

/**
 * Add a meta box for ISBN number.
 */
function add_isbn_meta_box() {
    add_meta_box(
        'isbn_meta_box',
        'ISBN Number',
        'isbn_meta_box_html',
        'book',
        'side'
    );
}

add_action('add_meta_boxes', 'add_isbn_meta_box');

/**
 * HTML for the ISBN meta box.
 */
function isbn_meta_box_html($post) {
    $isbn = get_post_meta($post->ID, '_isbn', true);
    ?>
    <label for="isbn">ISBN</label>
    <input type="text" name="isbn" id="isbn" value="<?php echo esc_attr($isbn); ?>" />
    <?php
}

/**
 * Save the ISBN to the books_info table.
 */
function save_isbn_meta_box_data($post_id) {
    if (array_key_exists('isbn', $_POST)) {
        $isbn = sanitize_text_field($_POST['isbn']);
        update_post_meta($post_id, '_isbn', $isbn);

        global $wpdb;
        $table_name = $wpdb->prefix . 'books_info';

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
            $post_id
        ));

        if ($existing) {
            $wpdb->update(
                $table_name,
                array('isbn' => $isbn),
                array('post_id' => $post_id)
            );
        } 
        else {
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'isbn' => $isbn
                )
            );
        }
    }
}

add_action('save_post', 'save_isbn_meta_box_data');









add_action('admin_menu', 'books_info_menu_page');
function books_info_menu_page() {
    add_menu_page(
        'Books Info',
        'Books Info',
        'manage_options',
        'books-info',
        'display_books_info_table'
    );
}

function get_books_info_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'books_info';
    $data = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    return $data;
}

function display_books_info_table() {
    echo '<h1>Books Info</h1>';

    // Include necessary files for WP_List_Table
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

    // Define a custom class that extends WP_List_Table
    class Books_Info_Table extends WP_List_Table {
        function __construct() {
            parent::__construct(array(
                'singular' => 'Book Info',
                'plural'   => 'Books Info',
                'ajax'     => false
            ));
        }

        function column_default($item, $column_name) {
            return $item[$column_name];
        }

        function get_columns() {
            return array(
                'ID' => __('ID'),
                'post_id' => __('Post ID'),
                'isbn' => __('ISBN')
            );
        }

        function prepare_items() {
            $data = get_books_info_data();
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = array();
            $this->_column_headers = array($columns, $hidden, $sortable);
            $this->items = $data;
        }
    }


    $books_info_table = new Books_Info_Table();
    $books_info_table->prepare_items();
    $books_info_table->display();

    echo '<span>All done by aydin Sarrafi</span>';
}











