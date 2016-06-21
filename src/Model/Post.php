<?php

namespace WordPress\ORM\Model;

use DateTime;
use WordPress\ORM\BaseModel;

/**
 * WordPress post model.
 *
 * @author Brandon Wamboldt <brandon.wamboldt@gmail.com>
 */
class Post extends BaseModel
{
    /**
     * @var integer
     */
    protected $ID;

    /**
     * @var integer
     */
    protected $post_author;

    /**
     * @var DateTime
     */
    protected $post_date;

    /**
     * @var DateTime
     */
    protected $post_date_gmt;

    /**
     * @var string
     */
    protected $post_content;

    /**
     * @var string
     */
    protected $post_title;

    /**
     * @var string
     */
    protected $post_excerpt;

    /**
     * @var string
     */
    protected $post_status;

    /**
     * @var string
     */
    protected $comment_status;

    /**
     * @var string
     */
    protected $ping_status;

    /**
     * @var string
     */
    protected $post_password;

    /**
     * @var string
     */
    protected $post_name;

    /**
     * @var string
     */
    protected $to_ping;

    /**
     * @var string
     */
    protected $pinged;

    /**
     * @var DateTime
     */
    protected $post_modified;

    /**
     * @var DateTime
     */
    protected $post_modified_gmt;

    /**
     * @var string
     */
    protected $post_content_filtered;

    /**
     * @var integer
     */
    protected $post_parent;

    /**
     * @var string
     */
    protected $guid;

    /**
     * @var integer
     */
    protected $menu_order;

    /**
     * @var string
     */
    protected $post_type;

    /**
     * @var string
     */
    protected $post_mime_type;

    /**
     * @var integer
     */
    protected $comment_count;

    /**
     * @var array
     */
    protected $meta = array();

	/**
	 * @var array
	 */
    protected $meta_raw = array();

    /**
     * Override the default constructor so we can type cast certain properties.
     *
     * @param array $properties
     */
    public function __construct(array $properties = array())
    {
        global $wpdb;

        if (isset($properties['ID'])) {
            $metadata = $wpdb->get_results("SELECT * FROM `{$wpdb->postmeta}` WHERE `post_id` = {$properties['ID']}");

            foreach ($metadata as $data) {
                $this->meta[$data->meta_key] = maybe_unserialize($data->meta_value);
                $this->meta_raw[$data->meta_key] = (array)$data;
            }
        }

        if (isset($properties['post_date'])) {
            $properties['post_date'] = new DateTime($properties['post_date']);
        }

        if (isset($properties['post_date_gmt'])) {
            $properties['post_date_gmt'] = new DateTime($properties['post_date_gmt']);
        }

        if (isset($properties['post_modified'])) {
            $properties['post_modified'] = new DateTime($properties['post_modified']);
        }

        if (isset($properties['post_modified_gmt'])) {
            $properties['post_modified_gmt'] = new DateTime($properties['post_modified_gmt']);
        }

        parent::__construct($properties);
    }

    /**
     * Get the post's meta data.
     *
     * @param  string $meta_key
     * @param  mixed  $default
     * @return mixed
     */
    public function get_metadata($meta_key, $default = null)
    {
        return $this->meta[$meta_key];
    }

    /**
     * Update the post's meta data.
     *
     * @param string $meta_key
     * @param mixed  $meta_value
     */
    public function update_metadata($meta_key, $meta_value)
    {
        $this->meta[$meta_key] = $meta_value;

		if( !empty( $this->meta_raw[ $meta_key ] ) ) {
			$cur_meta_value = $this->meta_raw[$meta_key];
		} else {
			$cur_meta_value = array(
				'meta_id' => '',
				'post_id' => $this->{static::get_primary_key()},
				'meta_key' => $meta_key,
				'meta_value' => $meta_value,
				);
		}

		$cur_meta_value['meta_value'] = maybe_serialize( $meta_value );

		$this->meta_raw[$meta_key]['meta_value'] = $cur_meta_value;

        // update_post_meta($this->ID, $meta_key, $meta_value);
    }

    /**
     * Delete the post's meta data.
     *
     * @param string $meta_key
     */
    public function delete_metadata($meta_key)
    {
        unset($this->meta[$meta_key]);
        unset($this->meta_raw[$meta_key]);

        delete_post_meta($this->ID, $meta_key);
    }

    /**
     * Convert complex objects to strings to insert into the database.
     *
     * @param  array $props
     * @return array
     */
    public function flatten_props($props)
    {
        unset($props['meta']);
        unset($props['meta_raw']);

        return parent::flatten_props($props);
    }

    /**
     * Get the model's primary key.
     *
     * @return string
     */
    public static function get_primary_key()
    {
        return 'ID';
    }

    /**
     * Get the table used to store posts.
     *
     * @return string
     */
    public static function get_table()
    {
        global $wpdb;

        return $wpdb->posts;
    }

    /**
     * Get an array of properties to search when doing a search query.
     *
     * @return array
     */
    public static function get_searchable_fields()
    {
        return array('post_title', 'post_content', 'post_excerpt');
    }

	/**
	 * Save postmeta with save
	 */
	public function save() 
	{
		global $wpdb;

		$a = 1 + 1;
		parent::save();

		if ( count( $this->meta_raw ) === 0 ){
			return $this->{static::get_primary_key()};
		}

		$four =  '(%s,%s,%s,%s)';

		$q = 'INSERT INTO ' . _get_meta_table( 'post' ); 
		$q .= ' ( meta_id, post_id, meta_key, meta_value ) ';
		$q .= ' VALUES '; 
		$q .= str_repeat( $four . ",\n", count($this->meta_raw) - 1) . $four;
		$q .= ' ON DUPLICATE KEY UPDATE ';
		$q .= ' meta_id = VALUES( meta_id ),';
		$q .= ' post_id = VALUES( post_id ),';
		$q .= ' meta_key = VALUES( meta_key),';
		$q .= ' meta_value = VALUES( meta_value )';

		$query_vals = array();
		foreach( $this->meta_raw as $qv ) {
			array_splice( $query_vals, 0, 0, $qv );
		}

		// $meta_vals = array_merge( $query_vals, $query_vals );
		$sql = $wpdb->prepare( $q, $query_vals );

		$wpdb->query($sql);

        return $this->{static::get_primary_key()};
	}
}
