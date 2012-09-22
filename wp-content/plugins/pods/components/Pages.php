<?php
/**
 * @package Pods\Components\Pages
 */
/**
 * Name: Pages
 *
 * Description: Create advanced URL structures using wildcards, they can exist on-top of any existing WordPress URL rewrites or be entirely custom. Add a path and select the WP Template to use, the rest is up to you!
 *
 * Version: 2.0
 *
 * Menu Page: edit.php?post_type=_pods_page
 * Menu Add Page: post-new.php?post_type=_pods_page
 *
 * @package Pods\Components
 * @subpackage Pages
 */

class Pods_Pages extends PodsComponent {

    /**
     * Current Pod Page
     *
     * @var array
     *
     * @since 2.0.0
     */
    static $exists = null;

    /**
     * Object type
     *
     * @var string
     *
     * @since 2.0.0
     */
    private $object_type = '_pods_page';

    /**
     * Do things like register/enqueue scripts and stylesheets
     *
     * @since 2.0.0
     */
    public function __construct () {
        $args = array(
            'label' => 'Pod Pages',
            'labels' => array( 'singular_name' => 'Pod Page' ),
            'public' => false,
            'can_export' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'query_var' => false,
            'rewrite' => false,
            'has_archive' => false,
            'hierarchical' => false,
            'supports' => array( 'title', 'author', 'revisions' ),
            'menu_icon' => PODS_URL . 'ui/images/icon16.png'
        );

        if ( !is_super_admin() )
            $args[ 'capability_type' ] = 'pods_page';

        $args = PodsInit::object_label_fix( $args, 'post_type' );

        register_post_type( $this->object_type, apply_filters( 'pods_internal_register_post_type_object_page', $args ) );

        if ( !is_admin() )
            add_action( 'load_textdomain', array( $this, 'page_check' ), 12 );
        else {
            add_action( 'dbx_post_advanced', array( $this, 'edit_page_form' ), 10 );

            add_action( 'pods_meta_groups', array( $this, 'add_meta_boxes' ) );
            add_filter( 'get_post_metadata', array( $this, 'get_meta' ), 10, 4 );
            add_filter( 'update_post_metadata', array( $this, 'save_meta' ), 10, 4 );

            add_action( 'pods_meta_save_post__pods_page', array( $this, 'clear_cache' ), 10, 5 );
            add_action( 'delete_post', array( $this, 'clear_cache' ), 10, 1 );
        }
    }

    /**
     * Admin Init
     *
     * @since 2.0.0
     */
    public function admin_init() {

    }

    /**
     * Enqueue styles
     *
     * @since 2.0.0
     */
    public function admin_assets () {
        wp_enqueue_style( 'pods-admin' );
    }

    /**
     * Clear cache on save
     *
     * @since 2.0.0
     */
    public function clear_cache ( $data, $pod = null, $id = null, $groups = null, $post = null ) {
        if ( !is_array( $data ) && 0 < $data ) {
            $post = $data;
            $post = get_post( $post );

            if ( $this->object_type != $post->post_type )
                return;
        }

        delete_transient( 'pods_object_page' );
        delete_transient( 'pods_object_page_' . $post->post_title );
    }

    /**
     * Change post title placeholder text
     *
     * @since 2.0.0
     */
    public function set_title_text ( $text, $post ) {
        return __( 'Enter URL here', 'pods' );
    }

    /**
     * Edit page form
     *
     * @since 2.0.0
     */
    public function edit_page_form () {
        global $post_type;

        if ( $this->object_type != $post_type )
            return;

        add_filter( 'enter_title_here', array( $this, 'set_title_text' ), 10, 2 );
    }

    /**
     * Add meta boxes to the page
     *
     * @since 2.0.0
     */
    public function add_meta_boxes () {
        $pod = array(
            'name' => $this->object_type,
            'type' => 'post_type'
        );

        if ( isset( PodsMeta::$post_types[ $pod[ 'name' ] ] ) )
            return;

        $page_templates = apply_filters( 'pods_page_templates', get_page_templates() );

        $page_templates[ __( '-- Page Template --', 'pods' ) ] = '';

        if ( !in_array( 'pods.php', $page_templates ) && locate_template( array( 'pods.php', false ) ) )
            $page_templates[ 'Pods (Pods Default)' ] = 'pods.php';

        if ( !in_array( 'page.php', $page_templates ) && locate_template( array( 'page.php', false ) ) )
            $page_templates[ 'Page (WP Default)' ] = 'page.php';

        ksort( $page_templates );

        $page_templates = array_flip( $page_templates );

        $fields = array(
            array(
                'name' => 'page_title',
                'label' => __( 'Page Title', 'pods' ),
                'type' => 'text'
            ),
            array(
                'name' => 'code',
                'label' => __( 'Page Code', 'pods' ),
                'type' => 'code'
            ),
            array(
                'name' => 'precode',
                'label' => __( 'Page Precode', 'pods' ),
                'type' => 'code',
                'help' => __( 'Precode will run before your theme outputs the page. It is expected that this value will be a block of PHP. You must open the PHP tag here, as we do not open it for you by default.', 'pods' )
            ),
            array(
                'name' => 'page_template',
                'label' => __( 'Page Template', 'pods' ),
                'type' => 'pick',
                'data' => $page_templates
            )
        );

        pods_group_add( $pod, __( 'Page', 'pods' ), $fields, 'normal', 'high' );
    }

    /**
     * Get the fields
     *
     * @param null $_null
     * @param int $post_ID
     * @param string $meta_key
     * @param bool $single
     *
     * @return array|bool|int|mixed|null|string|void
     */
    public function get_meta ( $_null, $post_ID = null, $meta_key = null, $single = false ) {
        if ( 'code' == $meta_key ) {
            $post = get_post( $post_ID );

            if ( is_object( $post ) && $this->object_type == $post->post_type )
                return $post->post_content;
        }

        return $_null;
    }

    /**
     * Save the fields
     *
     * @param $_null
     * @param int $post_ID
     * @param string $meta_key
     * @param null $meta_value
     *
     * @return bool|int|null
     */
    public function save_meta ( $_null, $post_ID = null, $meta_key = null, $meta_value = null ) {
        if ( 'code' == $meta_key ) {
            $post = get_post( $post_ID );

            if ( is_object( $post ) && $this->object_type == $post->post_type ) {
                $postdata = array(
                    'ID' => $post_ID,
                    'post_content' => $meta_value
                );

                remove_filter( current_filter(), array( $this, __FUNCTION__ ), 10 );

                wp_update_post( $postdata );

                return true;
            }
        }

        return $_null;
    }

    /**
     * Check to see if Pod Page exists and return data
     *
     * $uri not required, if NULL then returns REQUEST_URI matching Pod Page
     *
     * @param string $uri The Pod Page URI to check if exists
     *
     * @return array|bool
     */
    public static function exists( $uri = null ) {
        if ( null === $uri ) {
            $uri = parse_url( get_current_url() );
            $uri = $uri[ 'path' ];
            $home = parse_url( get_bloginfo( 'url' ) );

            if ( !empty( $home ) && isset( $home[ 'path' ] ) && '/' != $home[ 'path' ] )
                $uri = substr( $uri, strlen( $home[ 'path' ] ) );
        }

        $uri = trim( $uri, '/' );
        $uri_depth = count( array_filter( explode( '/', $uri ) ) ) - 1;

        if ( false !== strpos( $uri, 'wp-admin' ) || false !== strpos( $uri, 'wp-includes' ) )
            return false;

        // See if the custom template exists
        $sql = "
                SELECT *
                FROM `@wp_posts`
                WHERE
                    `post_type` = '_pods_page'
                    AND `post_status` = 'publish'
                    AND `post_title` = %s
                LIMIT 1
            ";

        $sql = array( $sql, array( $uri ) );

        $result = pods_query( $sql );

        if ( empty( $result ) ) {
            // Find any wildcards
            $sql = "
                    SELECT *
                    FROM `@wp_posts`
                    WHERE
                        `post_type` = '_pods_page'
                        AND `post_status` = 'publish'
                        AND %s LIKE REPLACE(`post_title`, '*', '%%')
                        AND (LENGTH(`post_title`) - LENGTH(REPLACE(`post_title`, '/', ''))) = %d
                    ORDER BY LENGTH(`post_title`) DESC, `post_title` DESC
                    LIMIT 1
                ";

            $sql = array( $sql, array( $uri, $uri_depth ) );

            $result = pods_query( $sql );
        }

        if ( !empty( $result ) ) {
            $_object = get_object_vars( $result[ 0 ] );

            $object = array(
                'ID' => $_object[ 'ID' ],
                'uri' => $_object[ 'post_title' ],
                'code' => $_object[ 'post_content' ],
                'phpcode' => $_object[ 'post_content' ], // phpcode is deprecated
                'precode' => get_post_meta( $_object[ 'ID' ], 'precode', true ),
                'page_template' => get_post_meta( $_object[ 'ID' ], 'page_template', true ),
                'title' => get_post_meta( $_object[ 'ID' ], 'page_title', true )
            );

            return $object;
        }

        return false;
    }

    /**
     * Check if a Pod Page exists
     */
    public function page_check () {
        global $pods;

        if ( !defined( 'PODS_DISABLE_POD_PAGE_CHECK' ) || !PODS_DISABLE_POD_PAGE_CHECK ) {
            if ( null === self::$exists )
                self::$exists = pod_page_exists();

            if ( false !== self::$exists ) {
                $pods = apply_filters( 'pods_global', $pods, self::$exists );

                if ( ( is_object( $pods ) || 404 != $pods  ) && ( !is_object( $pods ) || !is_wp_error( $pods ) ) ) {
                    add_action( 'template_redirect', array( $this, 'template_redirect' ) );
                    add_filter( 'redirect_canonical', '__return_false' );
                    add_action( 'wp_head', array( $this, 'wp_head' ) );
                    add_filter( 'wp_title', array( $this, 'wp_title' ), 0, 3 );
                    add_filter( 'body_class', array( $this, 'body_class' ), 0, 1 );
                    add_filter( 'status_header', array( $this, 'status_header' ) );
                    add_action( 'after_setup_theme', array( $this, 'precode' ) );
                    add_action( 'wp', array( $this, 'silence_404' ) );
                }
            }
        }
    }

    /**
     * Output Pod Page Content
     *
     * @param bool $return Whether to return or not (default is to echo)
     *
     * @return string
     */
    public static function content ( $return = false ) {
        global $pods, $pod_page_exists;

        $content = false;

        if ( false !== self::$exists ) {
            if ( 0 < strlen( trim( self::$exists[ 'code' ] ) ) )
                $content = self::$exists[ 'code' ];

            ob_start();

            do_action( 'pods_content_pre', self::$exists, $content );

            if ( false !== $content ) {
                if ( !defined( 'PODS_DISABLE_EVAL' ) || !PODS_DISABLE_EVAL ) {
                    pods_deprecated( 'Use WP Page Templates or hook into the pods_content filter instead of using Pod Page PHP code', '2.1.0' );

                    eval( "?>$content" );
                }
                else
                    echo $content;
            }

            do_action( 'pods_content_post', self::$exists, $content );

            $content = ob_get_clean();
        }

        $content = apply_filters( 'pods_content', $content, self::$exists );

        if ( $return )
            return $content;

        echo $content;
    }

    /**
     * Run any precode for current Pod Page
     */
    public function precode () {
        global $pods;

        if ( false !== self::$exists ) {
            $content = false;

            if ( 0 < strlen( trim( self::$exists[ 'precode' ] ) ) )
                $content = self::$exists[ 'precode' ];

            if ( false !== $content && ( !defined( 'PODS_DISABLE_EVAL' ) || !PODS_DISABLE_EVAL ) ) {
                pods_deprecated( 'Use WP Page Templates or hook into the pods_page_precode action instead of using Pod Page Precode', '2.1.0' );

                eval( "?>$content" );
            }

            do_action( 'pods_page_precode', self::$exists, $pods, $content );

            if ( !is_object( $pods ) && ( 404 == $pods || is_wp_error( $pods ) ) ) {
                remove_action( 'template_redirect', array( $this, 'template_redirect' ) );
                remove_action( 'wp_head', array( $this, 'wp_head' ) );
                remove_filter( 'redirect_canonical', '__return_false' );
                remove_filter( 'wp_title', array( $this, 'wp_title' ) );
                remove_filter( 'body_class', array( $this, 'body_class' ) );
                remove_filter( 'status_header', array( $this, 'status_header' ) );
                remove_action( 'wp', array( $this, 'silence_404' ) );
            }
        }
    }

    /**
     *
     */
    public function wp_head () {
        global $pods;

        do_action( 'pods_wp_head' );

        if ( !defined( 'PODS_DISABLE_VERSION_OUTPUT' ) || !PODS_DISABLE_VERSION_OUTPUT ) {
            ?>
        <!-- Pods Framework <?php echo PODS_VERSION; ?> -->
        <?php
        }
        if ( ( !defined( 'PODS_DISABLE_META' ) || !PODS_DISABLE_META ) && is_object( $pods ) && !is_wp_error( $pods ) ) {

            if ( isset( $pods->meta ) && is_array( $pods->meta ) ) {
                foreach ( $pods->meta as $name => $content ) {
                    if ( 'title' == $name )
                        continue;
                    ?>
                <meta name="<?php echo esc_attr( $name ); ?>" content="<?php echo esc_attr( $content ); ?>" />
                <?php
                }
            }

            if ( isset( $pods->meta_properties ) && is_array( $pods->meta_properties ) ) {
                foreach ( $pods->meta_properties as $property => $content ) {
                    ?>
                <meta property="<?php echo esc_attr( $property ); ?>" content="<?php echo esc_attr( $content ); ?>" />
                <?php
                }
            }

            if ( isset( $pods->meta_extra ) && 0 < strlen( $pods->meta_extra ) )
                echo $pods->meta_extra;
        }
    }

    /**
     * @param $title
     * @param $sep
     * @param $seplocation
     *
     * @return mixed|void
     */
    public function wp_title ( $title, $sep, $seplocation ) {
        global $pods;

        $page_title = self::$exists[ 'title' ];

        if ( 0 < strlen( trim( $page_title ) ) ) {
            if ( is_object( $pods ) && !is_wp_error( $pods ) )
                $page_title = preg_replace_callback( "/({@(.*?)})/m", array( $pods, "parse_magic_tags" ), $page_title );

            $title = ( 'right' == $seplocation ) ? "{$page_title} {$sep} " : " {$sep} {$page_title}";
        }
        else {
            $uri = explode( '?', $_SERVER[ 'REQUEST_URI' ] );
            $uri = preg_replace( "@^([/]?)(.*?)([/]?)$@", "$2", $uri[ 0 ] );
            $uri = preg_replace( "@(-|_)@", " ", $uri );
            $uri = explode( '/', $uri );

            $title = '';

            foreach ( $uri as $key => $page_title ) {
                $title .= ( 'right' == $seplocation ) ? ucwords( $page_title ) . " {$sep} " : " {$sep} " . ucwords( $page_title );
            }
        }

        if ( ( !defined( 'PODS_DISABLE_META' ) || !PODS_DISABLE_META ) && is_object( $pods ) && !is_wp_error( $pods ) && isset( $pods->meta ) && is_array( $pods->meta ) && isset( $pods->meta[ 'title' ] ) )
            $title = $pods->meta[ 'title' ];

        return apply_filters( 'pods_title', $title, $sep, $seplocation, self::$exists );
    }

    /**
     * @param $classes
     *
     * @return mixed|void
     */
    public function body_class ( $classes ) {
        global $pods;

        if ( defined( 'PODS_DISABLE_BODY_CLASSES' ) && PODS_DISABLE_BODY_CLASSES )
            return $classes;

        $classes[] = 'pods';

        $uri = explode( '?', self::$exists[ 'uri' ] );
        $uri = explode( '#', $uri[ 0 ] );

        $class = str_replace( array( '*', '/' ), array( '_w_', '-' ), $uri[ 0 ] );
        $class = sanitize_title( $class );
        $class = str_replace( array( '_', '--', '--' ), '-', $class );
        $class = trim( $class, '-' );

        $classes[] = 'pod-page-' . $class;

        if ( is_object( $pods ) && !is_wp_error( $pods ) ) {
            $class = sanitize_title( $pods->pod );
            $class = str_replace( array( '_', '--', '--' ), '-', $class );
            $class = trim( $class, '-' );
            $classes[] = 'pod-' . $class;
        }

        if ( is_object( $pods ) && !is_wp_error( $pods ) && isset( $pods->body_classes ) )
            $classes[] = $pods->body_classes;

        return apply_filters( 'pods_body_class', $classes, $uri );
    }

    /**
     * @return string
     */
    public function status_header () {
        return $_SERVER[ 'SERVER_PROTOCOL' ] . ' 200 OK';
    }

    /**
     *
     */
    public function silence_404 () {
        global $wp_query;

        $wp_query->query_vars[ 'error' ] = '';
        $wp_query->is_404 = false;
    }

    /**
     *
     */
    public function template_redirect () {
        global $pods;

        if ( false !== self::$exists ) {
            /*
             * Create pods.php in your theme directory, and
             * style it to suit your needs. Some helpful functions:
             *
             * get_header()
             * pods_content()
             * get_sidebar()
             * get_footer()
             */
            $template = self::$exists[ 'page_template' ];
            $template = apply_filters( 'pods_page_template', $template, self::$exists );

            $render_function = apply_filters( 'pods_template_redirect', null, $template, self::$exists );

            do_action( 'pods_page', $template, self::$exists );

            if ( null !== $render_function && is_callable( $render_function ) )
                call_user_func( $render_function, $template, self::$exists );
            elseif ( is_object( $pods ) && !is_wp_error( $pods ) && isset( $pods->page_template ) && !empty( $pods->page_template ) && '' != locate_template( array( $pods->page_template ), true ) ) {
                $template = $pods->page_template;
                // found the template and included it, we're good to go!
            }
            elseif ( !empty( self::$exists[ 'page_template' ] ) && '' != locate_template( array( self::$exists[ 'page_template' ] ), true ) ) {
                $template = self::$exists[ 'page_template' ];
                // found the template and included it, we're good to go!
            }
            elseif ( '' != locate_template( apply_filters( 'pods_page_default_templates', array( 'pods.php' ) ), true ) ) {
                $template = 'pods.php';
                // found the template and included it, we're good to go!
            }
            else {
                // templates not found in theme, default output
                do_action( 'pods_page_default', $template, self::$exists );

                get_header();
                pods_content();
                get_sidebar();
                get_footer();
            }

            do_action( 'pods_page_end', $template, self::$exists );

            exit;
        }
    }
}

/**
 * Find out if the current page is a Pod Page
 *
 * @param string $uri The Pod Page URI to check if currently on
 *
 * @return bool
 * @since 1.7.5
 */
function is_pod_page ( $uri = null ) {
    if ( false !== Pods_Pages::$exists && ( null === $uri || $uri == Pods_Pages::$exists[ 'uri' ] ) )
        return true;

    return false;
}

/**
 * Check to see if Pod Page exists and return data
 *
 * $uri not required, if NULL then returns REQUEST_URI matching Pod Page
 *
 * @param string $uri The Pod Page URI to check if exists
 *
 * @return array
 */
function pod_page_exists ( $uri = null ) {
    return Pods_Pages::exists( $uri );
}

/**
 * Output Pod Page Content
 *
 * @param bool $return Whether to return or not (default is to echo)
 *
 * @return string
 */
function pods_content ( $return = false ) {
    return Pods_Pages::content( $return );
}

/*
 * Deprecated global variable
 */
global $pod_page_exists;
$pod_page_exists =& Pods_Pages::$exists;
