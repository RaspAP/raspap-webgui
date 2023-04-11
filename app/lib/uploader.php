<?php

/**
 * Simple PHP upload class
 *
 * Adapted from aivis/PHP-file-upload-class
 *
 * @description File upload class for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @author      Aivis Silins
 * @link        https://github.com/aivis/PHP-file-upload-class
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Uploader;

class Upload
{

    /**
     * Default directory persmissions (destination)
     */
    protected $default_permissions = 0750;

    /**
     * File post array
     *
     * @var array
     */
    protected $file_post = array();

    /**
     * Destination directory
     *
     * @var string
     */
    protected $destination;

    /**
     * Fileinfo
     *
     * @var object
     */
    protected $finfo;

    /**
     * Data about file
     *
     * @var array
     */
    public $file = array();

    /**
     * Max. file size
     *
     * @var int
     */
    protected $max_file_size;

    /**
     * Allowed mime types
     *
     * @var array
     */
    protected $mimes = array();

    /**
     * Temp path
     *
     * @var string
     */
    protected $tmp_name;

    /**
     * Validation errors
     *
     * @var array
     */
    protected $validation_errors = array();

    /**
     * Filename (new)
     *
     * @var string
     */
    protected $filename;

    /**
     * Internal callbacks (filesize check, mime, etc)
     *
     * @var array
     */
    private $callbacks = array();

    /**
     * Root dir
     *
     * @var string
     */
    protected $root;

    /**
     * Return upload object
     *
     * $destination    = 'path/to/file/destination/';
     *
     * @param  string $destination
     * @param  string $root
     * @return Upload
     */
    public static function factory($destination, $root = false)
    {
        return new Upload($destination, $root);
    }

    /**
     *  Define root constant and set & create destination path
     *
     * @param string $destination
     * @param string $root
     */
    public function __construct($destination, $root = false)
    {
        if ($root) {
            $this->root = $root;
        } else {
            $this->root = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR;
        }

        // set & create destination path
        if (!$this->set_destination($destination)) {
            throw new Exception('Upload: Unable to create destination. '.$this->root . $this->destination);
        }
        //create finfo object
        $this->finfo = new \finfo();
    }

    /**
     * Set target filename
     *
     * @param string $filename
     */
    public function set_filename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Check & Save file
     *
     * Return data about current upload
     *
     * @return array
     */
    public function upload($filename = false)
    {
        if($filename ) {
            $this->set_filename($filename);
        }

        $this->set_filename($filename);
        
        if ($this->check()) {
            $this->save();
        }

        // return state data
        return $this->get_state();
    }

    /**
     * Save file on server
     * Return state data
     *
     * @return array
     */
    public function save()
    {
        $this->save_file();
        return $this->get_state();
    }

    /**
     * Validate file (execute callbacks)
     * Returns TRUE if validation successful
     *
     * @return bool
     */
    public function check()
    {
        //execute callbacks (check filesize, mime, also external callbacks
        $this->validate();

        //add error messages
        $this->file['errors'] = $this->get_errors();

        //change file validation status
        $this->file['status'] = empty($this->validation_errors);

        return $this->file['status'];
    }

    /**
     * Get current state data
     *
     * @return array
     */
    public function get_state()
    {
        return $this->file;
    }

    /**
     * Save file on server
     */
    protected function save_file()
    {
        //create & set new filename
        if(empty($this->filename)) {
            $this->create_new_filename();
        }

        //set filename
        $this->file['filename'] = $this->filename;

        //set full path
        $this->file['full_path'] = $this->root . $this->destination . $this->filename;
            $this->file['path'] = $this->destination . $this->filename;

        $status = move_uploaded_file($this->tmp_name, $this->file['full_path']);

        //checks whether upload successful
        if (!$status) {
            throw new Exception('Upload: Failed to upload file.');
        }

        //done
        $this->file['status'] = true;
    }

    /**
     * Set data about file
     */
    protected function set_file_data()
    {
        $file_size = $this->get_file_size();
        $this->file = array(
        'status'            => false,
        'destination'       => $this->destination,
        'size_in_bytes'     => $file_size,
        'size_in_mb'        => $this->bytes_to_mb($file_size),
        'mime'              => $this->get_file_mime(),
        'filename'          => $this->file_post['name'],
        'tmp_name'          => $this->file_post['tmp_name'],
        'post_data'         => $this->file_post,
        );
    }

    /**
     * Set validation error
     *
     * @param string $message
     */
    public function set_error($message)
    {
        $this->validation_errors[] = $message;
    }

    /**
     * Return validation errors
     *
     * @return array
     */
    public function get_errors()
    {
        return $this->validation_errors;
    }

    /**
     * Set external callback methods
     *
     * @param object $instance_of_callback_object
     * @param array  $callback_methods
     */
    public function callbacks($instance_of_callback_object, $callback_methods)
    {
        if (empty($instance_of_callback_object)) {
            throw new Exception('Upload: $instance_of_callback_object cannot be empty.');

        }

        if (!is_array($callback_methods)) {
            throw new Exception('Upload: $callback_methods data type need to be array.');
        }

        $this->external_callback_object = $instance_of_callback_object;
        $this->external_callback_methods = $callback_methods;
    }

    /**
     * Execute callbacks
     */
    protected function validate()
    {
        //get curent errors
        $errors = $this->get_errors();

        if (empty($errors)) {

            //set data about current file
            $this->set_file_data();

            //execute internal callbacks
            $this->execute_callbacks($this->callbacks, $this);

            //execute external callbacks
            $this->execute_callbacks($this->external_callback_methods, $this->external_callback_object);
        }
    }

    /**
     * Execute callbacks
     */
    protected function execute_callbacks($callbacks, $object)
    {
        foreach($callbacks as $method) {
            $object->$method($this);

        }
    }

    /**
     * File mime type validation callback
     *
     * @param object $object
     */
    protected function check_mime_type($object)
    {
        if (!empty($object->mimes)) {
            if (!in_array($object->file['mime'], $object->mimes)) {
                $object->set_error('MIME type not allowed.');
            }
        }
    }

    /**
     * Set allowed mime types
     *
     * @param array $mimes
     */
    public function set_allowed_mime_types($mimes)
    {
        $this->mimes        = $mimes;
        //if mime types is set -> set callback
        $this->callbacks[]    = 'check_mime_type';
    }

    /**
     * File size validation callback
     *
     * @param object $object
     */
    protected function check_file_size($object)
    {
        if (!empty($object->max_file_size)) {
            $file_size_in_mb = $this->bytes_to_mb($object->file['size_in_bytes']);
            if ($object->max_file_size <= $file_size_in_mb) {
                $object->set_error('File exceeds maximum allowed size.');
            }
        }
    }

    /**
     * Set max file size
     *
     * @param int $size
     */
    public function set_max_file_size($size)
    {
        $this->max_file_size = $size;
        
        //if max file size is set -> set callback
        $this->callbacks[] = 'check_file_size';
    }

    /**
     * Set File array to object
     *
     * @param array $file
     */
    public function file($file)
    {
        $this->set_file_array($file);
    }

    /**
     * Set file array
     *
     * @param array $file
     */
    protected function set_file_array($file)
    {
        //checks whether file array is valid
        if (!$this->check_file_array($file)) {
            //file not selected or some bigger problems (broken files array)
            $this->set_error('Please select file.');
        }

        //set file data
        $this->file_post = $file;

        //set tmp path
        $this->tmp_name  = $file['tmp_name'];
    }

    /**
     * Checks whether Files post array is valid
     *
     * @return bool
     */
    protected function check_file_array($file)
    {
        return isset($file['error'])
        && !empty($file['name'])
        && !empty($file['type'])
        && !empty($file['tmp_name'])
        && !empty($file['size']);
    }

    /**
     * Get file mime type
     *
     * @return string
     */
    protected function get_file_mime()
    {
        return $this->finfo->file($this->tmp_name, FILEINFO_MIME_TYPE);
    }

    /**
     * Get file size
     *
     * @return int
     */
    protected function get_file_size()
    {
        return filesize($this->tmp_name);
    }

    /**
     * Set destination path (return TRUE on success)
     *
     * @param  string $destination
     * @return bool
     */
    protected function set_destination($destination)
    {
        $this->destination = $destination . DIRECTORY_SEPARATOR;
        return $this->destination_exist() ? true : $this->create_destination();
    }

    /**
     * Checks whether destination folder exists
     *
     * @return bool
     */
    protected function destination_exist()
    {
        return is_writable($this->root . $this->destination);
    }

    /**
     * Create path to destination
     *
     * @param  string $dir
     * @return bool
     */
    protected function create_destination()
    {
        return mkdir($this->root . $this->destination, $this->default_permissions, true);
    }

    /**
     * Set unique filename
     *
     * @return string
     */
    protected function create_new_filename()
    {
        $filename = sha1(mt_rand(1, 9999) . $this->destination . uniqid()) . time();
        $this->set_filename($filename);
    }

    /**
     * Convert bytes to MB
     *
     * @param  int $bytes
     * @return int
     */
    protected function bytes_to_mb($bytes)
    {
        return round(($bytes / 1048576), 2);
    }
}

