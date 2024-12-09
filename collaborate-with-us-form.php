<?php
/**
 * Plugin Name: Collaborate With Us Form
 * Description: A plugin that creates a "Collaborate with us" form and stores submissions in a custom table. Also provides an admin page to view entries.
 * Version: 1.0
 * Author: Bakry Abdelsalam
 * Author URI: https://bakry2.vercel.app/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Collaborate_With_Us_Form {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'collaborate_requests';

        // Hook to create table on plugin activation
        register_activation_hook( __FILE__, array( $this, 'create_table' ) );

        // Shortcode to display form
        add_shortcode( 'collaborate_form', array( $this, 'render_form' ) );

        // Handle form submission
        add_action( 'init', array( $this, 'handle_form_submission' ) );

        // Add admin menu page
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
    }

    /**
     * Create the custom database table on activation
     */
    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            job_title varchar(255) NOT NULL,
            company varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            email varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Render the "Collaborate with us" form via shortcode
     */
    public function render_form() {
        // Output buffering so we can return the form markup as a string
        ob_start();
        ?>
        <form action="" method="post">
            <?php wp_nonce_field( 'collaborate_form_submit', 'collaborate_nonce' ); ?>
            <p><strong>I’m a:</strong></p>
            <p>
                <label>
                    <input type="radio" name="collab_type" value="broker" required> Broker
                </label>
            </p>
            <p>
                <label>
                    <input type="radio" name="collab_type" value="developer" required> Developer
                </label>
            </p>

            <p>
                <label for="collab_job_title"><strong>Job title:</strong></label><br/>
                <input type="text" name="collab_job_title" id="collab_job_title" required>
            </p>
            <p>
                <label for="collab_company"><strong>Company:</strong></label><br/>
                <input type="text" name="collab_company" id="collab_company" required>
            </p>
            <p>
                <label for="collab_phone"><strong>Phone:</strong></label><br/>
                <input type="text" name="collab_phone" id="collab_phone" required>
            </p>
            <p>
                <label for="collab_email"><strong>Email:</strong></label><br/>
                <input type="email" name="collab_email" id="collab_email" required>
            </p>

            <p>
                <input type="submit" name="collab_submit" value="Submit">
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle form submission and insert into database
     */
    public function handle_form_submission() {
        if ( isset( $_POST['collab_submit'] ) ) {
            // Check nonce
            if ( ! isset( $_POST['collaborate_nonce'] ) || ! wp_verify_nonce( $_POST['collaborate_nonce'], 'collaborate_form_submit' ) ) {
                return; // Nonce invalid, do nothing
            }

            // Sanitize inputs
            $type     = isset($_POST['collab_type']) ? sanitize_text_field($_POST['collab_type']) : '';
            $job      = isset($_POST['collab_job_title']) ? sanitize_text_field($_POST['collab_job_title']) : '';
            $company  = isset($_POST['collab_company']) ? sanitize_text_field($_POST['collab_company']) : '';
            $phone    = isset($_POST['collab_phone']) ? sanitize_text_field($_POST['collab_phone']) : '';
            $email    = isset($_POST['collab_email']) ? sanitize_email($_POST['collab_email']) : '';

            // Insert into database
            global $wpdb;
            $wpdb->insert(
                $this->table_name,
                array(
                    'type' => $type,
                    'job_title' => $job,
                    'company' => $company,
                    'phone' => $phone,
                    'email' => $email
                ),
                array('%s','%s','%s','%s','%s')
            );

            // Optional: Redirect or show a success message
            // For simplicity, let's just redirect back to the same page
            wp_redirect( add_query_arg( 'collab_success', '1', wp_get_referer() ) );
            exit;
        }
    }

    /**
     * Add admin menu page to view submissions
     */
    public function add_admin_menu_page() {
        add_menu_page(
            'Collaborate Requests',
            'Collaborate Requests',
            'manage_options',
            'collaborate-requests',
            array( $this, 'admin_menu_page_html' ),
            'dashicons-admin-users',
            26
        );
    }

    /**
     * Display the admin page with table entries
     */
    public function admin_menu_page_html() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        global $wpdb;
        $results = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY created_at DESC" );
        ?>
        <div class="wrap">
            <h1>Collaborate Requests</h1>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th class="manage-column">ID</th>
                        <th class="manage-column">Type</th>
                        <th class="manage-column">Job Title</th>
                        <th class="manage-column">Company</th>
                        <th class="manage-column">Phone</th>
                        <th class="manage-column">Email</th>
                        <th class="manage-column">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $results ): ?>
                        <?php foreach ( $results as $row ): ?>
                            <tr>
                                <td><?php echo intval($row->id); ?></td>
                                <td><?php echo esc_html($row->type); ?></td>
                                <td><?php echo esc_html($row->job_title); ?></td>
                                <td><?php echo esc_html($row->company); ?></td>
                                <td><?php echo esc_html($row->phone); ?></td>
                                <td><?php echo esc_html($row->email); ?></td>
                                <td><?php echo esc_html($row->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Initialize the plugin class
new Collaborate_With_Us_Form();
